<script setup>
/**
 * ADMIN PLAN MANAGEMENT - INDEX
 * ==============================
 *
 * Zeigt alle Subscription-Pläne mit:
 * - Name, Stripe Price ID, Preis, Intervall
 * - Max Plattformen
 * - Anzahl Benutzer pro Plan
 * - Aktiv/Inaktiv Status
 * - "Beliebt"-Badge (wenn is_popular = true)
 * - Aktionen: Edit, Delete, Toggle Active, Toggle Popular
 *
 * STRIPE INTEGRATION:
 * - Jeder bezahlte Plan braucht eine Stripe Price ID
 * - Für monatlich + jährlich: 2 separate Pläne erstellen
 * - Preis und Intervall MÜSSEN mit Stripe übereinstimmen
 */

import AppLayout from '@/layouts/AppLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
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
    Alert,
    AlertDescription,
    AlertTitle,
} from '@/components/ui/alert';
import {
    MoreHorizontal,
    Plus,
    Edit,
    Trash2,
    Power,
    Users,
    Star,
    ExternalLink,
    AlertCircle,
    Package,
    CheckCircle,
    XCircle
} from 'lucide-vue-next';
import { ref, computed } from 'vue';

const props = defineProps({
    plans: {
        type: Array,
        required: true,
    },
});

// Delete Dialog State
const planToDelete = ref(null);
const deleteDialogOpen = ref(false);

// Statistiken berechnen
const stats = computed(() => {
    const total = props.plans.length;
    const active = props.plans.filter(p => p.is_active).length;
    const totalUsers = props.plans.reduce((sum, p) => sum + (p.users_count || 0), 0);
    const withoutStripeId = props.plans.filter(p => parseFloat(p.price) > 0 && !p.stripe_plan_id).length;
    return { total, active, totalUsers, withoutStripeId };
});

const confirmDelete = (plan) => {
    planToDelete.value = plan;
    deleteDialogOpen.value = true;
};

const deletePlan = () => {
    if (!planToDelete.value) return;

    router.delete(`/admin/plans/${planToDelete.value.id}`, {
        onSuccess: () => {
            planToDelete.value = null;
            deleteDialogOpen.value = false;
        },
    });
};

const toggleActive = (plan) => {
    router.post(`/admin/plans/${plan.id}/toggle-active`);
};

const togglePopular = (plan) => {
    router.post(`/admin/plans/${plan.id}/toggle-popular`);
};

/**
 * Formatiere Preis
 */
const formatPrice = (price) => {
    if (parseFloat(price) === 0) {
        return 'Kostenlos';
    }
    return `${parseFloat(price).toFixed(2).replace('.', ',')} €`;
};

const getStatusBadge = (isActive) => {
    return isActive
        ? { variant: 'default', text: 'Aktiv', icon: CheckCircle }
        : { variant: 'secondary', text: 'Inaktiv', icon: XCircle };
};
</script>

<template>
    <Head title="Plan-Verwaltung" />

    <AppLayout :breadcrumbs="[
        { label: 'Admin', href: '/admin' },
        { label: 'Pläne', href: '/admin/plans' }
    ]">
        <div class="space-y-6 p-4 md:p-6 lg:p-8">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold tracking-tight md:text-3xl">Plan-Verwaltung</h1>
                    <p class="text-muted-foreground">
                        Erstelle und verwalte Subscription-Pläne
                    </p>
                </div>
                <div class="flex gap-2">
                    <a
                        href="https://dashboard.stripe.com/products"
                        target="_blank"
                    >
                        <Button variant="outline">
                            <ExternalLink class="mr-2 h-4 w-4" />
                            Stripe Dashboard
                        </Button>
                    </a>
                    <Link href="/admin/plans/create">
                        <Button>
                            <Plus class="mr-2 h-4 w-4" />
                            Neuer Plan
                        </Button>
                    </Link>
                </div>
            </div>

            <!-- Stats Cards -->
            <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
                <Card>
                    <CardHeader class="flex flex-row items-center justify-between pb-2">
                        <CardTitle class="text-sm font-medium">Gesamt Pläne</CardTitle>
                        <Package class="h-4 w-4 text-muted-foreground" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold">{{ stats.total }}</div>
                    </CardContent>
                </Card>
                <Card>
                    <CardHeader class="flex flex-row items-center justify-between pb-2">
                        <CardTitle class="text-sm font-medium">Aktive Pläne</CardTitle>
                        <CheckCircle class="h-4 w-4 text-green-600" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold text-green-600">{{ stats.active }}</div>
                        <p class="text-xs text-muted-foreground">
                            {{ stats.total - stats.active }} inaktiv
                        </p>
                    </CardContent>
                </Card>
                <Card>
                    <CardHeader class="flex flex-row items-center justify-between pb-2">
                        <CardTitle class="text-sm font-medium">Benutzer mit Plan</CardTitle>
                        <Users class="h-4 w-4 text-muted-foreground" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold">{{ stats.totalUsers }}</div>
                    </CardContent>
                </Card>
                <Card>
                    <CardHeader class="flex flex-row items-center justify-between pb-2">
                        <CardTitle class="text-sm font-medium">Ohne Stripe ID</CardTitle>
                        <AlertCircle class="h-4 w-4" :class="stats.withoutStripeId > 0 ? 'text-destructive' : 'text-muted-foreground'" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold" :class="stats.withoutStripeId > 0 ? 'text-destructive' : ''">
                            {{ stats.withoutStripeId }}
                        </div>
                        <p v-if="stats.withoutStripeId > 0" class="text-xs text-destructive">
                            Bezahlte Pläne ohne Stripe!
                        </p>
                    </CardContent>
                </Card>
            </div>

            <!-- Warning Alert -->
            <Alert v-if="stats.withoutStripeId > 0" variant="destructive">
                <AlertCircle class="h-4 w-4" />
                <AlertTitle>Stripe-Konfiguration fehlt</AlertTitle>
                <AlertDescription>
                    {{ stats.withoutStripeId }} bezahlte Plan(e) haben keine Stripe Price ID.
                    Ohne Price ID können keine Zahlungen verarbeitet werden.
                </AlertDescription>
            </Alert>

            <!-- Plans Table -->
            <Card>
                <CardHeader>
                    <CardTitle>Alle Pläne</CardTitle>
                    <CardDescription>
                        Stripe Checkout ermöglicht die Auswahl zwischen monatlicher und jährlicher Zahlung
                    </CardDescription>
                </CardHeader>
                <CardContent>
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead>Plan</TableHead>
                                <TableHead>Stripe Price ID</TableHead>
                                <TableHead>Preis</TableHead>
                                <TableHead>Max Plattformen</TableHead>
                                <TableHead>Status</TableHead>
                                <TableHead>Benutzer</TableHead>
                                <TableHead class="text-right">Aktionen</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            <TableRow v-for="plan in plans" :key="plan.id">
                                <!-- Plan Name mit Badges -->
                                <TableCell>
                                    <div class="flex flex-col gap-1">
                                        <div class="flex items-center gap-2">
                                            <span class="font-medium">{{ plan.name }}</span>
                                            <Badge v-if="plan.is_popular" variant="default" class="text-xs">
                                                Beliebt
                                            </Badge>
                                        </div>
                                        <code class="text-xs text-muted-foreground">{{ plan.slug }}</code>
                                    </div>
                                </TableCell>

                                <!-- Stripe Price ID -->
                                <TableCell>
                                    <div v-if="plan.stripe_plan_id" class="flex items-center gap-1">
                                        <code class="rounded bg-muted px-2 py-1 text-xs">
                                            {{ plan.stripe_plan_id.substring(0, 20) }}...
                                        </code>
                                    </div>
                                    <div v-else-if="parseFloat(plan.price) > 0" class="flex items-center gap-1 text-destructive">
                                        <AlertCircle class="h-3 w-3" />
                                        <span class="text-xs">Fehlt!</span>
                                    </div>
                                    <span v-else class="text-xs text-muted-foreground">-</span>
                                </TableCell>

                                <!-- Preis -->
                                <TableCell>
                                    {{ formatPrice(plan.price) }}
                                </TableCell>

                                <!-- Max Plattformen -->
                                <TableCell>
                                    {{ plan.max_platforms === 1000 ? 'Unbegrenzt' : plan.max_platforms }}
                                </TableCell>

                                <!-- Status Badge -->
                                <TableCell>
                                    <Badge :variant="getStatusBadge(plan.is_active).variant">
                                        <component :is="getStatusBadge(plan.is_active).icon" class="mr-1 h-3 w-3" />
                                        {{ getStatusBadge(plan.is_active).text }}
                                    </Badge>
                                </TableCell>

                                <!-- User Count -->
                                <TableCell>
                                    <div class="flex items-center gap-2">
                                        <Users class="h-4 w-4 text-muted-foreground" />
                                        <span>{{ plan.users_count }}</span>
                                    </div>
                                </TableCell>

                                <!-- Actions Dropdown -->
                                <TableCell class="text-right">
                                    <DropdownMenu>
                                        <DropdownMenuTrigger as-child>
                                            <Button variant="ghost" size="icon">
                                                <MoreHorizontal class="h-4 w-4" />
                                            </Button>
                                        </DropdownMenuTrigger>
                                        <DropdownMenuContent align="end">
                                            <DropdownMenuItem as-child>
                                                <Link :href="`/admin/plans/${plan.id}/edit`">
                                                    <Edit class="mr-2 h-4 w-4" />
                                                    Bearbeiten
                                                </Link>
                                            </DropdownMenuItem>

                                            <DropdownMenuItem @click="toggleActive(plan)">
                                                <Power class="mr-2 h-4 w-4" />
                                                {{ plan.is_active ? 'Deaktivieren' : 'Aktivieren' }}
                                            </DropdownMenuItem>

                                            <DropdownMenuItem @click="togglePopular(plan)">
                                                <Star class="mr-2 h-4 w-4" />
                                                {{ plan.is_popular ? 'Von "Beliebt" entfernen' : 'Als "Beliebt" markieren' }}
                                            </DropdownMenuItem>

                                            <DropdownMenuSeparator />

                                            <DropdownMenuItem
                                                @click="confirmDelete(plan)"
                                                class="text-destructive"
                                            >
                                                <Trash2 class="mr-2 h-4 w-4" />
                                                Löschen
                                            </DropdownMenuItem>
                                        </DropdownMenuContent>
                                    </DropdownMenu>
                                </TableCell>
                            </TableRow>

                            <!-- Empty State -->
                            <TableRow v-if="plans.length === 0">
                                <TableCell colspan="7" class="py-8 text-center">
                                    <div class="flex flex-col items-center gap-2">
                                        <Package class="h-8 w-8 text-muted-foreground" />
                                        <p class="text-muted-foreground">Keine Pläne vorhanden.</p>
                                        <Link href="/admin/plans/create">
                                            <Button variant="outline" size="sm">
                                                <Plus class="mr-2 h-4 w-4" />
                                                Ersten Plan erstellen
                                            </Button>
                                        </Link>
                                    </div>
                                </TableCell>
                            </TableRow>
                        </TableBody>
                    </Table>
                </CardContent>
            </Card>
        </div>

        <!-- Delete Confirmation Dialog -->
        <Dialog v-model:open="deleteDialogOpen">
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>Plan löschen?</DialogTitle>
                    <DialogDescription>
                        Möchtest du den Plan "{{ planToDelete?.name }}" wirklich löschen?
                        <span v-if="planToDelete?.users_count > 0" class="mt-2 block font-semibold text-destructive">
                            Achtung: Dieser Plan kann nicht gelöscht werden,
                            da {{ planToDelete.users_count }} Benutzer diesen Plan nutzen.
                        </span>
                    </DialogDescription>
                </DialogHeader>
                <DialogFooter>
                    <Button variant="outline" @click="deleteDialogOpen = false">
                        Abbrechen
                    </Button>
                    <Button
                        @click="deletePlan"
                        variant="destructive"
                        :disabled="planToDelete?.users_count > 0"
                    >
                        Löschen
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    </AppLayout>
</template>
