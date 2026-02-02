<script setup>
/**
 * ADMIN SUBSCRIPTION MANAGEMENT
 * =============================
 *
 * Übersicht aller Benutzer-Subscriptions mit:
 * - Statistiken (aktive Abos, Umsatz, etc.)
 * - Suche & Filter
 * - Tabellenansicht
 * - Schnelle Aktionen (Plan ändern, kündigen, etc.)
 * - Letzte Aktivitäten
 */

import AppLayout from '@/layouts/AppLayout.vue';
import { Head, router, Link } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Input } from '@/components/ui/input';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select/index';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import {
    Users,
    CreditCard,
    TrendingUp,
    AlertCircle,
    CheckCircle,
    XCircle,
    Search,
    MoreHorizontal,
    RefreshCw,
    Calendar,
    ArrowUpDown,
    Filter,
    X
} from 'lucide-vue-next';
import { ref, computed, watch } from 'vue';
import { useDebounceFn } from '@vueuse/core';

const props = defineProps({
    users: Object,
    plans: Array,
    stats: Object,
    recentActivity: Array,
    filters: Object,
});

// Filter State
const search = ref(props.filters?.search || '');
const planFilter = ref(props.filters?.plan_id || '');
const statusFilter = ref(props.filters?.status || '');

// Dialog State
const showPlanDialog = ref(false);
const selectedUser = ref(null);
const selectedPlanId = ref(null);

// Debounced Suche
const debouncedSearch = useDebounceFn(() => {
    applyFilters();
}, 300);

watch(search, () => {
    debouncedSearch();
});

const applyFilters = () => {
    router.get('/admin/subscriptions', {
        search: search.value || undefined,
        plan_id: planFilter.value || undefined,
        status: statusFilter.value || undefined,
    }, {
        preserveState: true,
        preserveScroll: true,
    });
};

const clearFilters = () => {
    search.value = '';
    planFilter.value = '';
    statusFilter.value = '';
    router.get('/admin/subscriptions');
};

const hasActiveFilters = computed(() => {
    return search.value || planFilter.value || statusFilter.value;
});

// Plan ändern Dialog
const openPlanDialog = (user) => {
    selectedUser.value = user;
    selectedPlanId.value = user.plan_id;
    showPlanDialog.value = true;
};

const confirmPlanChange = () => {
    if (!selectedUser.value || !selectedPlanId.value) return;

    router.post(`/admin/subscriptions/${selectedUser.value.id}/update-plan`, {
        plan_id: selectedPlanId.value,
    }, {
        preserveScroll: true,
        onSuccess: () => {
            showPlanDialog.value = false;
            selectedUser.value = null;
        },
    });
};

const cancelSubscription = (user) => {
    if (!confirm(`Subscription für ${user.name} kündigen? Der Benutzer kann bis zum Ende der Laufzeit weiter nutzen.`)) return;

    router.post(`/admin/subscriptions/${user.id}/cancel`, {}, {
        preserveScroll: true,
    });
};

const cancelSubscriptionNow = (user) => {
    if (!confirm(`Subscription für ${user.name} SOFORT beenden? Dies kann nicht rückgängig gemacht werden.`)) return;

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
        return { text: 'Free', variant: 'secondary', icon: XCircle };
    }

    const sub = user.subscriptions[0];
    if (sub.ends_at) {
        return { text: 'Gekündigt', variant: 'destructive', icon: AlertCircle };
    }

    return { text: 'Aktiv', variant: 'default', icon: CheckCircle };
};

const formatDate = (dateString) => {
    if (!dateString) return '-';
    return new Date(dateString).toLocaleDateString('de-DE', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
    });
};

const formatPrice = (price, interval) => {
    if (!price || price == 0) return 'Kostenlos';
    const suffix = interval === 'yearly' ? '/Jahr' : '/Monat';
    return `${Number(price).toFixed(2).replace('.', ',')} €${suffix}`;
};

const formatCurrency = (amount) => {
    return `${Number(amount || 0).toFixed(2).replace('.', ',')} €`;
};

const formatActivityAction = (action) => {
    const actions = {
        'subscribed': 'Abo abgeschlossen',
        'cancelled': 'Abo gekündigt',
        'resumed': 'Abo reaktiviert',
        'admin_plan_changed': 'Plan geändert (Admin)',
        'payment_method_updated': 'Zahlungsmethode aktualisiert',
    };
    return actions[action] || action;
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
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold tracking-tight md:text-3xl">Subscription Management</h1>
                    <p class="text-muted-foreground">Verwalte alle Benutzer-Subscriptions</p>
                </div>
                <Link href="/admin/plans">
                    <Button variant="outline">
                        <CreditCard class="mr-2 h-4 w-4" />
                        Pläne verwalten
                    </Button>
                </Link>
            </div>

            <!-- Stats Cards -->
            <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
                <Card>
                    <CardHeader class="flex flex-row items-center justify-between pb-2">
                        <CardTitle class="text-sm font-medium">Gesamt Benutzer</CardTitle>
                        <Users class="h-4 w-4 text-muted-foreground" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold">{{ stats.totalUsers }}</div>
                    </CardContent>
                </Card>
                <Card>
                    <CardHeader class="flex flex-row items-center justify-between pb-2">
                        <CardTitle class="text-sm font-medium">Aktive Abos</CardTitle>
                        <CheckCircle class="h-4 w-4 text-green-600" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold text-green-600">{{ stats.activeSubscriptions }}</div>
                        <p class="text-xs text-muted-foreground">
                            {{ stats.cancelledSubscriptions }} gekündigt
                        </p>
                    </CardContent>
                </Card>
                <Card>
                    <CardHeader class="flex flex-row items-center justify-between pb-2">
                        <CardTitle class="text-sm font-medium">Monatlicher Umsatz</CardTitle>
                        <TrendingUp class="h-4 w-4 text-muted-foreground" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold">{{ formatCurrency(stats.monthlyRevenue) }}</div>
                        <p class="text-xs text-muted-foreground">
                            + {{ formatCurrency(stats.yearlyRevenue / 12) }}/Monat (jährlich)
                        </p>
                    </CardContent>
                </Card>
                <Card>
                    <CardHeader class="flex flex-row items-center justify-between pb-2">
                        <CardTitle class="text-sm font-medium">Free Users</CardTitle>
                        <Users class="h-4 w-4 text-muted-foreground" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold">{{ stats.freeUsers }}</div>
                        <p class="text-xs text-muted-foreground">
                            ohne bezahltes Abo
                        </p>
                    </CardContent>
                </Card>
            </div>

            <!-- Filters -->
            <Card>
                <CardContent class="pt-6">
                    <div class="flex flex-col gap-4 md:flex-row md:items-center">
                        <!-- Search -->
                        <div class="relative flex-1">
                            <Search class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                            <Input
                                v-model="search"
                                placeholder="Suche nach Name oder E-Mail..."
                                class="pl-10"
                            />
                        </div>

                        <!-- Plan Filter -->
                        <Select v-model="planFilter" @update:model-value="applyFilters">
                            <SelectTrigger class="w-[180px]">
                                <SelectValue placeholder="Alle Pläne" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="">Alle Pläne</SelectItem>
                                <SelectItem value="none">Kein Plan</SelectItem>
                                <SelectItem v-for="plan in plans" :key="plan.id" :value="String(plan.id)">
                                    {{ plan.name }}
                                </SelectItem>
                            </SelectContent>
                        </Select>

                        <!-- Status Filter -->
                        <Select v-model="statusFilter" @update:model-value="applyFilters">
                            <SelectTrigger class="w-[180px]">
                                <SelectValue placeholder="Alle Status" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="">Alle Status</SelectItem>
                                <SelectItem value="active">Aktiv</SelectItem>
                                <SelectItem value="cancelled">Gekündigt</SelectItem>
                                <SelectItem value="free">Free (kein Abo)</SelectItem>
                            </SelectContent>
                        </Select>

                        <!-- Clear Filters -->
                        <Button
                            v-if="hasActiveFilters"
                            variant="ghost"
                            size="icon"
                            @click="clearFilters"
                        >
                            <X class="h-4 w-4" />
                        </Button>
                    </div>
                </CardContent>
            </Card>

            <!-- Users Table -->
            <Card>
                <CardHeader>
                    <CardTitle>Benutzer ({{ users.total }})</CardTitle>
                    <CardDescription>
                        Seite {{ users.current_page }} von {{ users.last_page }}
                    </CardDescription>
                </CardHeader>
                <CardContent>
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead>Benutzer</TableHead>
                                <TableHead>Plan</TableHead>
                                <TableHead>Preis</TableHead>
                                <TableHead>Status</TableHead>
                                <TableHead>Registriert</TableHead>
                                <TableHead class="text-right">Aktionen</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            <TableRow v-for="user in users.data" :key="user.id">
                                <!-- User Info -->
                                <TableCell>
                                    <div>
                                        <p class="font-medium">{{ user.name }}</p>
                                        <p class="text-sm text-muted-foreground">{{ user.email }}</p>
                                    </div>
                                </TableCell>

                                <!-- Plan -->
                                <TableCell>
                                    <div class="flex items-center gap-2">
                                        <Badge variant="outline">
                                            {{ user.plan?.name || 'Kein Plan' }}
                                        </Badge>
                                        <Badge v-if="user.plan?.billing_interval === 'yearly'" variant="secondary" class="text-xs">
                                            Jährlich
                                        </Badge>
                                    </div>
                                </TableCell>

                                <!-- Price -->
                                <TableCell>
                                    {{ formatPrice(user.plan?.price, user.plan?.billing_interval) }}
                                </TableCell>

                                <!-- Status -->
                                <TableCell>
                                    <Badge :variant="getSubscriptionStatus(user).variant">
                                        <component :is="getSubscriptionStatus(user).icon" class="mr-1 h-3 w-3" />
                                        {{ getSubscriptionStatus(user).text }}
                                    </Badge>
                                </TableCell>

                                <!-- Registered -->
                                <TableCell>
                                    {{ formatDate(user.created_at) }}
                                </TableCell>

                                <!-- Actions -->
                                <TableCell class="text-right">
                                    <DropdownMenu>
                                        <DropdownMenuTrigger as-child>
                                            <Button variant="ghost" size="icon">
                                                <MoreHorizontal class="h-4 w-4" />
                                            </Button>
                                        </DropdownMenuTrigger>
                                        <DropdownMenuContent align="end">
                                            <DropdownMenuItem @click="openPlanDialog(user)">
                                                <CreditCard class="mr-2 h-4 w-4" />
                                                Plan ändern
                                            </DropdownMenuItem>

                                            <DropdownMenuSeparator />

                                            <template v-if="user.subscriptions && user.subscriptions.length > 0">
                                                <DropdownMenuItem
                                                    v-if="!user.subscriptions[0].ends_at"
                                                    @click="cancelSubscription(user)"
                                                >
                                                    <Calendar class="mr-2 h-4 w-4" />
                                                    Zum Periodenende kündigen
                                                </DropdownMenuItem>
                                                <DropdownMenuItem
                                                    v-else
                                                    @click="resumeSubscription(user)"
                                                >
                                                    <RefreshCw class="mr-2 h-4 w-4" />
                                                    Reaktivieren
                                                </DropdownMenuItem>

                                                <DropdownMenuItem
                                                    class="text-destructive"
                                                    @click="cancelSubscriptionNow(user)"
                                                >
                                                    <XCircle class="mr-2 h-4 w-4" />
                                                    Sofort beenden
                                                </DropdownMenuItem>
                                            </template>
                                        </DropdownMenuContent>
                                    </DropdownMenu>
                                </TableCell>
                            </TableRow>

                            <TableRow v-if="users.data.length === 0">
                                <TableCell colspan="6" class="text-center py-8">
                                    <p class="text-muted-foreground">Keine Benutzer gefunden.</p>
                                </TableCell>
                            </TableRow>
                        </TableBody>
                    </Table>

                    <!-- Pagination -->
                    <div v-if="users.last_page > 1" class="mt-4 flex items-center justify-between">
                        <p class="text-sm text-muted-foreground">
                            {{ users.from }} - {{ users.to }} von {{ users.total }} Benutzern
                        </p>
                        <div class="flex gap-2">
                            <Link
                                v-if="users.prev_page_url"
                                :href="users.prev_page_url"
                                preserve-scroll
                            >
                                <Button variant="outline" size="sm">Zurück</Button>
                            </Link>
                            <Link
                                v-if="users.next_page_url"
                                :href="users.next_page_url"
                                preserve-scroll
                            >
                                <Button variant="outline" size="sm">Weiter</Button>
                            </Link>
                        </div>
                    </div>
                </CardContent>
            </Card>

            <!-- Recent Activity -->
            <Card v-if="recentActivity && recentActivity.length > 0">
                <CardHeader>
                    <CardTitle>Letzte Aktivitäten</CardTitle>
                    <CardDescription>Die letzten Subscription-Änderungen</CardDescription>
                </CardHeader>
                <CardContent>
                    <div class="space-y-4">
                        <div
                            v-for="activity in recentActivity"
                            :key="activity.id"
                            class="flex items-start gap-3 text-sm"
                        >
                            <div class="flex h-8 w-8 items-center justify-center rounded-full bg-muted">
                                <CreditCard class="h-4 w-4" />
                            </div>
                            <div class="flex-1">
                                <p>
                                    <span class="font-medium">{{ activity.target_user?.name || 'Benutzer' }}</span>
                                    - {{ formatActivityAction(activity.action) }}
                                </p>
                                <p class="text-muted-foreground">
                                    {{ activity.description }}
                                </p>
                                <p class="text-xs text-muted-foreground">
                                    {{ formatDate(activity.created_at) }}
                                    <span v-if="activity.performed_by">
                                        von {{ activity.performed_by.name }}
                                    </span>
                                </p>
                            </div>
                        </div>
                    </div>
                </CardContent>
            </Card>
        </div>

        <!-- Plan Change Dialog -->
        <Dialog v-model:open="showPlanDialog">
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>Plan ändern</DialogTitle>
                    <DialogDescription>
                        Wähle einen neuen Plan für {{ selectedUser?.name }}
                    </DialogDescription>
                </DialogHeader>

                <div class="py-4">
                    <Select v-model="selectedPlanId">
                        <SelectTrigger>
                            <SelectValue placeholder="Plan auswählen" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem v-for="plan in plans" :key="plan.id" :value="plan.id">
                                <div class="flex items-center gap-2">
                                    {{ plan.name }}
                                    <span class="text-muted-foreground">
                                        ({{ formatPrice(plan.price, plan.billing_interval) }})
                                    </span>
                                </div>
                            </SelectItem>
                        </SelectContent>
                    </Select>
                </div>

                <DialogFooter>
                    <Button variant="outline" @click="showPlanDialog = false">
                        Abbrechen
                    </Button>
                    <Button
                        @click="confirmPlanChange"
                        :disabled="!selectedPlanId || selectedPlanId === selectedUser?.plan_id"
                    >
                        Plan ändern
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    </AppLayout>
</template>
