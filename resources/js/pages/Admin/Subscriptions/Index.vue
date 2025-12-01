<script setup>
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select/index';
import { AlertCircle, CheckCircle, XCircle } from 'lucide-vue-next';
import { ref } from 'vue';

const props = defineProps({
    users: {
        type: Object,
        required: true,
    },
    plans: {
        type: Array,
        required: true,
    },
});

const changePlan = (user, planId) => {
    if (!confirm(`Plan für ${user.name} ändern?`)) return;

    router.post(`/admin/subscriptions/${user.id}/update-plan`, {
        plan_id: planId,
    }, {
        preserveScroll: true,
    });
};

const cancelSubscription = (user) => {
    if (!confirm(`Subscription für ${user.name} kündigen?`)) return;

    router.post(`/admin/subscriptions/${user.id}/cancel`, {}, {
        preserveScroll: true,
    });
};

const cancelSubscriptionNow = (user) => {
    if (!confirm(`Subscription für ${user.name} SOFORT beenden?`)) return;

    router.post(`/admin/subscriptions/${user.id}/cancel-now`, {}, {
        preserveScroll: true,
    });
};

const resumeSubscription = (user) => {
    router.post(`/admin/subscriptions/${user.id}/resume`, {}, {
        preserveScroll: true,
    });
};

const getSubscriptionStatus = (user) => {
    if (!user.subscriptions || user.subscriptions.length === 0) {
        return { text: 'Kein Abo', variant: 'secondary', icon: XCircle };
    }

    const sub = user.subscriptions[0];
    if (sub.ends_at) {
        return { text: 'Gekündigt', variant: 'destructive', icon: AlertCircle };
    }

    return { text: 'Aktiv', variant: 'default', icon: CheckCircle };
};

const formatDate = (dateString) => {
    if (!dateString) return '-';
    return new Date(dateString).toLocaleDateString('de-DE');
};
</script>

<template>
    <Head title="Subscriptions verwalten" />

    <AppLayout :breadcrumbs="[
        { label: 'Admin', href: '/admin' },
        { label: 'Subscriptions', href: '/admin/subscriptions' }
    ]">
        <div class="space-y-6 p-4 md:p-6 lg:p-8">
            <!-- Header -->
            <div>
                <h1 class="text-2xl font-bold tracking-tight md:text-3xl">Subscription Management</h1>
                <p class="text-muted-foreground">Verwalte Benutzer-Subscriptions</p>
            </div>

            <!-- Users List -->
            <div class="grid gap-4">
                <Card v-for="user in users.data" :key="user.id">
                    <CardHeader>
                        <div class="flex items-start justify-between">
                            <div>
                                <CardTitle>{{ user.name }}</CardTitle>
                                <p class="text-sm text-muted-foreground">{{ user.email }}</p>
                            </div>
                            <Badge
                                v-if="user.subscriptions && user.subscriptions.length > 0"
                                :variant="getSubscriptionStatus(user).variant"
                            >
                                <component
                                    :is="getSubscriptionStatus(user).icon"
                                    class="mr-1 h-3 w-3"
                                />
                                {{ getSubscriptionStatus(user).text }}
                            </Badge>
                        </div>
                    </CardHeader>
                    <CardContent>
                        <div class="grid gap-4 md:grid-cols-2">
                            <!-- Current Plan -->
                            <div class="space-y-2">
                                <p class="text-sm font-medium">Aktueller Plan</p>
                                <div class="flex items-center gap-2">
                                    <Badge>{{ user.plan?.name || 'Kein Plan' }}</Badge>
                                    <span class="text-sm text-muted-foreground">
                                        {{ user.plan?.price ? Number(user.plan.price).toFixed(2) : '0.00' }} € / Monat
                                    </span>
                                </div>

                                <p class="text-xs text-muted-foreground">
                                    Max. Plattformen: {{ user.plan?.max_platforms || 0 }}
                                </p>
                            </div>

                            <!-- Subscription Info -->
                            <div class="space-y-2">
                                <p class="text-sm font-medium">Subscription Info</p>
                                <div v-if="user.subscriptions && user.subscriptions.length > 0">
                                    <p class="text-sm">
                                        Endet: {{ formatDate(user.subscriptions[0].ends_at) }}
                                    </p>
                                    <p class="text-sm">
                                        Periode: {{ formatDate(user.subscriptions[0].current_period_end) }}
                                    </p>
                                </div>
                                <p v-else class="text-sm text-muted-foreground">Keine aktive Subscription</p>
                            </div>
                        </div>

                        <!-- Actions -->
                        <div class="mt-4 flex flex-wrap gap-2">
                            <!-- Change Plan -->
                            <Select @update:model-value="(value) => changePlan(user, value)">
                                <SelectTrigger class="w-[180px]">
                                    <SelectValue placeholder="Plan ändern" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem
                                        v-for="plan in plans"
                                        :key="plan.id"
                                        :value="plan.id"
                                        :disabled="user.plan_id === plan.id"
                                    >
                                        {{ plan.name }} ({{ Number(plan.price).toFixed(2) }} €)
                                    </SelectItem>
                                </SelectContent>
                            </Select>

                            <!-- Cancel/Resume -->
                            <template v-if="user.subscriptions && user.subscriptions.length > 0">
                                <Button
                                    v-if="!user.subscriptions[0].ends_at"
                                    variant="outline"
                                    size="sm"
                                    @click="cancelSubscription(user)"
                                >
                                    Kündigen
                                </Button>
                                <Button
                                    v-else
                                    variant="outline"
                                    size="sm"
                                    @click="resumeSubscription(user)"
                                >
                                    Reaktivieren
                                </Button>

                                <Button
                                    variant="destructive"
                                    size="sm"
                                    @click="cancelSubscriptionNow(user)"
                                >
                                    Sofort beenden
                                </Button>
                            </template>
                        </div>
                    </CardContent>
                </Card>

                <div v-if="users.data.length === 0" class="text-center py-12">
                    <p class="text-muted-foreground">Keine Benutzer gefunden.</p>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
