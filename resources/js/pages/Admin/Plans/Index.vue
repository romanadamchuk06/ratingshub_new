<script setup>
/**
 * ADMIN PLAN MANAGEMENT - INDEX
 * ==============================
 *
 * Zeigt alle Subscription-Pläne mit zwei Stripe Price IDs:
 * - stripe_price_id_monthly (z.B. price_xxx für 14,99€/Monat)
 * - stripe_price_id_yearly (z.B. price_yyy für 149,99€/Jahr)
 */

import AppLayout from '@/layouts/AppLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
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
    Alert,
    AlertDescription,
    AlertTitle,
} from '@/components/ui/alert';
import {
    Edit,
    Users,
    ExternalLink,
    AlertCircle,
    Package,
    CheckCircle,
    XCircle
} from 'lucide-vue-next';
import { computed } from 'vue';

const props = defineProps({
    plans: {
        type: Array,
        required: true,
    },
});

// Statistiken
const stats = computed(() => {
    const total = props.plans.length;
    const active = props.plans.filter(p => p.is_active).length;
    const totalUsers = props.plans.reduce((sum, p) => sum + (p.users_count || 0), 0);
    // Prüfe ob bezahlte Pläne ohne Stripe IDs existieren
    const withoutStripeId = props.plans.filter(p =>
        parseFloat(p.price) > 0 &&
        !p.stripe_price_id_monthly &&
        !p.stripe_price_id_yearly &&
        !p.stripe_plan_id // Legacy check
    ).length;
    return { total, active, totalUsers, withoutStripeId };
});

const formatPrice = (price) => {
    if (!price || parseFloat(price) === 0) return 'Kostenlos';
    return `${parseFloat(price).toFixed(2).replace('.', ',')} €`;
};

const getStatusBadge = (isActive) => {
    return isActive
        ? { variant: 'default', text: 'Aktiv', icon: CheckCircle }
        : { variant: 'secondary', text: 'Inaktiv', icon: XCircle };
};

// Kürze Stripe Price ID für Anzeige
const shortenPriceId = (id) => {
    if (!id) return null;
    if (id.length > 25) {
        return id.substring(0, 12) + '...' + id.slice(-8);
    }
    return id;
};
</script>

<template>
    <Head title="Tarife & Stripe" />

    <AppLayout :breadcrumbs="[
        { label: 'Admin', href: '/admin' },
        { label: 'Tarife', href: '/admin/plans' }
    ]">
        <div class="space-y-6 p-4 md:p-6 lg:p-8">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold tracking-tight md:text-3xl">Tarife & Stripe</h1>
                    <p class="text-muted-foreground">
                        Konfiguriere Stripe Price IDs für deine Tarife
                    </p>
                </div>
                <a
                    href="https://dashboard.stripe.com/products"
                    target="_blank"
                >
                    <Button variant="outline">
                        <ExternalLink class="mr-2 h-4 w-4" />
                        Stripe Dashboard
                    </Button>
                </a>
            </div>

            <!-- Stats Cards -->
            <div class="grid gap-4 md:grid-cols-4">
                <Card>
                    <CardHeader class="flex flex-row items-center justify-between pb-2">
                        <CardTitle class="text-sm font-medium">Tarife</CardTitle>
                        <Package class="h-4 w-4 text-muted-foreground" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold">{{ stats.total }}</div>
                    </CardContent>
                </Card>
                <Card>
                    <CardHeader class="flex flex-row items-center justify-between pb-2">
                        <CardTitle class="text-sm font-medium">Aktiv</CardTitle>
                        <CheckCircle class="h-4 w-4 text-green-600" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold text-green-600">{{ stats.active }}</div>
                    </CardContent>
                </Card>
                <Card>
                    <CardHeader class="flex flex-row items-center justify-between pb-2">
                        <CardTitle class="text-sm font-medium">Benutzer</CardTitle>
                        <Users class="h-4 w-4 text-muted-foreground" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold">{{ stats.totalUsers }}</div>
                    </CardContent>
                </Card>
                <Card>
                    <CardHeader class="flex flex-row items-center justify-between pb-2">
                        <CardTitle class="text-sm font-medium">Ohne Stripe</CardTitle>
                        <AlertCircle class="h-4 w-4" :class="stats.withoutStripeId > 0 ? 'text-destructive' : 'text-muted-foreground'" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold" :class="stats.withoutStripeId > 0 ? 'text-destructive' : ''">
                            {{ stats.withoutStripeId }}
                        </div>
                    </CardContent>
                </Card>
            </div>

            <!-- Warning Alert -->
            <Alert v-if="stats.withoutStripeId > 0" variant="destructive">
                <AlertCircle class="h-4 w-4" />
                <AlertTitle>Stripe-Konfiguration fehlt</AlertTitle>
                <AlertDescription>
                    {{ stats.withoutStripeId }} bezahlte(r) Tarif(e) haben keine Stripe Price ID.
                    Konfiguriere die IDs damit Zahlungen funktionieren.
                </AlertDescription>
            </Alert>

            <!-- Plans Table -->
            <Card>
                <CardHeader>
                    <CardTitle>Alle Tarife</CardTitle>
                    <CardDescription>
                        Jeder Tarif hat zwei Stripe Price IDs: eine für monatliche, eine für jährliche Zahlung
                    </CardDescription>
                </CardHeader>
                <CardContent>
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead>Tarif</TableHead>
                                <TableHead>Preis Monatlich</TableHead>
                                <TableHead>Preis Jährlich</TableHead>
                                <TableHead>Max Plattformen</TableHead>
                                <TableHead>Benutzer</TableHead>
                                <TableHead>Status</TableHead>
                                <TableHead class="text-right">Aktion</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            <TableRow v-for="plan in plans" :key="plan.id">
                                <!-- Plan Name -->
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

                                <!-- Monatlicher Preis + Stripe ID -->
                                <TableCell>
                                    <div class="flex flex-col gap-1">
                                        <span class="font-medium">{{ formatPrice(plan.price) }}</span>
                                        <code v-if="plan.stripe_price_id_monthly" class="rounded bg-muted px-1.5 py-0.5 text-[10px]">
                                            {{ shortenPriceId(plan.stripe_price_id_monthly) }}
                                        </code>
                                        <span v-else-if="parseFloat(plan.price) > 0" class="flex items-center gap-1 text-xs text-destructive">
                                            <AlertCircle class="h-3 w-3" />
                                            ID fehlt
                                        </span>
                                        <span v-else class="text-xs text-muted-foreground">-</span>
                                    </div>
                                </TableCell>

                                <!-- Jährlicher Preis + Stripe ID -->
                                <TableCell>
                                    <div class="flex flex-col gap-1">
                                        <span class="font-medium">{{ formatPrice(plan.price_yearly) }}</span>
                                        <code v-if="plan.stripe_price_id_yearly" class="rounded bg-muted px-1.5 py-0.5 text-[10px]">
                                            {{ shortenPriceId(plan.stripe_price_id_yearly) }}
                                        </code>
                                        <span v-else-if="parseFloat(plan.price_yearly) > 0" class="flex items-center gap-1 text-xs text-destructive">
                                            <AlertCircle class="h-3 w-3" />
                                            ID fehlt
                                        </span>
                                        <span v-else class="text-xs text-muted-foreground">-</span>
                                    </div>
                                </TableCell>

                                <!-- Max Plattformen -->
                                <TableCell>
                                    {{ plan.max_platforms === 1000 ? 'Unbegrenzt' : plan.max_platforms }}
                                </TableCell>

                                <!-- User Count -->
                                <TableCell>
                                    <div class="flex items-center gap-2">
                                        <Users class="h-4 w-4 text-muted-foreground" />
                                        <span>{{ plan.users_count }}</span>
                                    </div>
                                </TableCell>

                                <!-- Status Badge -->
                                <TableCell>
                                    <Badge :variant="getStatusBadge(plan.is_active).variant">
                                        <component :is="getStatusBadge(plan.is_active).icon" class="mr-1 h-3 w-3" />
                                        {{ getStatusBadge(plan.is_active).text }}
                                    </Badge>
                                </TableCell>

                                <!-- Edit Button -->
                                <TableCell class="text-right">
                                    <Link :href="`/admin/plans/${plan.id}/edit`">
                                        <Button variant="outline" size="sm">
                                            <Edit class="mr-2 h-4 w-4" />
                                            Bearbeiten
                                        </Button>
                                    </Link>
                                </TableCell>
                            </TableRow>

                            <!-- Empty State -->
                            <TableRow v-if="plans.length === 0">
                                <TableCell colspan="7" class="py-8 text-center">
                                    <div class="flex flex-col items-center gap-2">
                                        <Package class="h-8 w-8 text-muted-foreground" />
                                        <p class="text-muted-foreground">Keine Tarife vorhanden.</p>
                                    </div>
                                </TableCell>
                            </TableRow>
                        </TableBody>
                    </Table>
                </CardContent>
            </Card>

            <!-- Info Box -->
            <Card>
                <CardHeader>
                    <CardTitle class="text-base">So funktioniert's</CardTitle>
                </CardHeader>
                <CardContent class="text-sm text-muted-foreground space-y-2">
                    <p>1. Erstelle Produkte und Preise in <a href="https://dashboard.stripe.com/products" target="_blank" class="text-primary underline">Stripe Dashboard</a></p>
                    <p>2. Kopiere die <strong>Price IDs</strong> (beginnen mit <code class="bg-muted px-1 rounded">price_</code>)</p>
                    <p>3. Trage sie hier ein (monatlich + jährlich)</p>
                    <p>4. Die Stripe Pricing Table zeigt automatisch beide Optionen</p>
                </CardContent>
            </Card>
        </div>
    </AppLayout>
</template>
