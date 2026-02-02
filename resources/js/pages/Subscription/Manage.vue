<script setup>
/**
 * SUBSCRIPTION MANAGE PAGE
 * ========================
 *
 * Verwaltungsseite für Subscriptions mit Stripe Integration.
 *
 * Features:
 * - Aktueller Plan mit Abrechnungsintervall (monatlich/jährlich)
 * - Link zum Stripe Billing Portal (Zahlungsmethode ändern)
 * - Rechnungen herunterladen
 * - Subscription kündigen/reaktivieren
 *
 * ZWEI SUBSCRIPTION-TYPEN:
 * 1. Kostenlose Pläne: subscription = null, nur currentPlan vorhanden
 * 2. Bezahlte Pläne: subscription + currentPlan + paymentMethod vorhanden
 */

import AppLayout from '@/layouts/AppLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import {
    CreditCard,
    Calendar,
    Download,
    AlertCircle,
    CheckCircle,
    XCircle,
    Globe,
    Clock,
    ExternalLink
} from 'lucide-vue-next';
import { ref, computed } from 'vue';

const props = defineProps({
    subscription: {
        type: Object,
        default: null,
    },
    currentPlan: {
        type: Object,
        required: true,
    },
    invoices: {
        type: Array,
        default: () => [],
    },
    paymentMethod: {
        type: Object,
        default: null,
    },
    platformsConnected: {
        type: Number,
        default: 0,
    },
    maxPlatforms: {
        type: Number,
        default: 1,
    },
    onTrial: {
        type: Boolean,
        default: false,
    },
    trialEndsAt: {
        type: String,
        default: null,
    },
});

const loading = ref(false);

// Ist der Plan jährlich?
const isYearly = computed(() => props.currentPlan?.billing_interval === 'yearly');

// Formatierter Preis mit Intervall
const formattedPrice = computed(() => {
    const price = Number(props.currentPlan?.price || 0);
    if (price === 0) return 'Kostenlos';
    const interval = isYearly.value ? '/Jahr' : '/Monat';
    return `${price.toFixed(2).replace('.', ',')} €${interval}`;
});

// Monatlicher Äquivalent bei jährlicher Zahlung
const monthlyEquivalent = computed(() => {
    if (!isYearly.value) return null;
    const yearly = Number(props.currentPlan?.price || 0);
    return (yearly / 12).toFixed(2).replace('.', ',');
});

const cancelSubscription = () => {
    if (!confirm('Möchtest du deine Subscription wirklich kündigen? Du kannst den Service bis zum Ende der Laufzeit weiter nutzen.')) return;

    loading.value = true;
    router.post('/subscription/cancel', {}, {
        onFinish: () => loading.value = false,
    });
};

const resumeSubscription = () => {
    loading.value = true;
    router.post('/subscription/resume', {}, {
        onFinish: () => loading.value = false,
    });
};

const formatDate = (dateString) => {
    if (!dateString) return '-';
    return new Date(dateString).toLocaleDateString('de-DE', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
    });
};

// Kreditkarten-Marke formatieren
const formatCardBrand = (brand) => {
    const brands = {
        'visa': 'Visa',
        'mastercard': 'Mastercard',
        'amex': 'American Express',
        'discover': 'Discover',
        'diners': 'Diners Club',
        'jcb': 'JCB',
        'unionpay': 'UnionPay',
    };
    return brands[brand?.toLowerCase()] || brand || 'Karte';
};

/**
 * Subscription-Status Badge
 */
const getSubscriptionStatus = () => {
    if (!props.currentPlan || props.currentPlan.price == 0) {
        return { text: 'Free Plan', variant: 'secondary', icon: CheckCircle };
    }

    if (props.subscription && props.subscription.ends_at) {
        return { text: 'Gekündigt', variant: 'destructive', icon: AlertCircle };
    }

    return { text: 'Aktiv', variant: 'default', icon: CheckCircle };
};

const status = getSubscriptionStatus();
</script>

<template>
    <Head title="Subscription verwalten" />

    <AppLayout :breadcrumbs="[
        { label: 'Subscription', href: '/subscription' },
        { label: 'Verwalten', href: '/subscription/manage' }
    ]">
        <div class="space-y-6 p-4 md:p-6 lg:p-8">
            <!-- Header -->
            <div>
                <h1 class="text-2xl font-bold tracking-tight md:text-3xl">Subscription verwalten</h1>
                <p class="text-muted-foreground">Verwalte dein Abo und deine Zahlungsmethoden</p>
            </div>

            <div class="mx-auto max-w-4xl space-y-6">
                <!-- Current Plan Card -->
                <Card>
                    <CardHeader>
                        <div class="flex items-start justify-between">
                            <div>
                                <CardTitle>Aktueller Plan</CardTitle>
                                <CardDescription>{{ currentPlan.description }}</CardDescription>
                            </div>
                            <Badge :variant="status.variant">
                                <component :is="status.icon" class="mr-1 h-3 w-3" />
                                {{ status.text }}
                            </Badge>
                        </div>
                    </CardHeader>
                    <CardContent class="space-y-4">
                        <!-- Plan Details -->
                        <div class="flex items-center justify-between">
                            <span class="text-sm text-muted-foreground">Plan</span>
                            <div class="text-right">
                                <span class="font-semibold">{{ currentPlan.name }}</span>
                                <Badge v-if="isYearly" variant="secondary" class="ml-2 text-xs">
                                    Jährlich
                                </Badge>
                            </div>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-sm text-muted-foreground">Preis</span>
                            <div class="text-right">
                                <span class="font-semibold">{{ formattedPrice }}</span>
                                <p v-if="monthlyEquivalent" class="text-xs text-muted-foreground">
                                    ({{ monthlyEquivalent }} €/Monat)
                                </p>
                            </div>
                        </div>

                        <!-- Zahlungsdetails: Nur bei Cashier subscriptions -->
                        <div v-if="subscription" class="space-y-2 border-t pt-4">
                            <div class="flex items-center justify-between">
                                <span class="text-sm text-muted-foreground">
                                    <Calendar class="mr-2 inline h-4 w-4" />
                                    Nächste Zahlung
                                </span>
                                <span class="font-medium">{{ formatDate(subscription.current_period_end) }}</span>
                            </div>
                            <div v-if="subscription.ends_at" class="flex items-center justify-between text-destructive">
                                <span class="text-sm">
                                    <AlertCircle class="mr-2 inline h-4 w-4" />
                                    Endet am
                                </span>
                                <span class="font-medium">{{ formatDate(subscription.ends_at) }}</span>
                            </div>
                        </div>

                        <!-- Actions -->
                        <div class="flex flex-wrap gap-3 border-t pt-4">
                            <Link href="/subscription" class="flex-1">
                                <Button variant="outline" class="w-full">
                                    Plan ändern
                                </Button>
                            </Link>

                            <Button
                                v-if="subscription && !subscription.ends_at"
                                variant="destructive"
                                @click="cancelSubscription"
                                :disabled="loading"
                            >
                                Kündigen
                            </Button>

                            <Button
                                v-if="subscription && subscription.ends_at"
                                variant="default"
                                @click="resumeSubscription"
                                :disabled="loading"
                            >
                                Wieder aktivieren
                            </Button>
                        </div>
                    </CardContent>
                </Card>

                <!-- Subscription Details Card -->
                <Card>
                    <CardHeader>
                        <CardTitle>Übersicht</CardTitle>
                        <CardDescription>Details zu deiner Subscription</CardDescription>
                    </CardHeader>
                    <CardContent class="space-y-4">
                        <!-- Platform Usage -->
                        <div class="flex items-center justify-between rounded-lg border p-4">
                            <div class="flex items-center gap-3">
                                <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-primary/10">
                                    <Globe class="h-5 w-5 text-primary" />
                                </div>
                                <div>
                                    <p class="font-medium">Verbundene Plattformen</p>
                                    <p class="text-sm text-muted-foreground">
                                        {{ platformsConnected }} von {{ maxPlatforms === 1000 ? '∞' : maxPlatforms }} genutzt
                                    </p>
                                </div>
                            </div>
                            <div class="text-right">
                                <p class="text-2xl font-bold">{{ platformsConnected }}/{{ maxPlatforms === 1000 ? '∞' : maxPlatforms }}</p>
                            </div>
                        </div>

                        <!-- Trial Info -->
                        <div v-if="onTrial" class="flex items-center gap-3 rounded-lg border border-blue-200 bg-blue-50 p-4 dark:border-blue-800 dark:bg-blue-900/20">
                            <Clock class="h-5 w-5 text-blue-600 dark:text-blue-400" />
                            <div>
                                <p class="font-medium text-blue-900 dark:text-blue-100">Testphase aktiv</p>
                                <p class="text-sm text-blue-700 dark:text-blue-300">
                                    Endet am {{ formatDate(trialEndsAt) }}
                                </p>
                            </div>
                        </div>

                        <!-- Features -->
                        <div class="space-y-2">
                            <p class="text-sm font-medium">Enthaltene Features</p>
                            <div class="space-y-2">
                                <div
                                    v-for="feature in currentPlan.features"
                                    :key="feature"
                                    class="flex items-start gap-2 text-sm"
                                >
                                    <CheckCircle class="mt-0.5 h-4 w-4 flex-shrink-0 text-green-600" />
                                    <span>{{ feature }}</span>
                                </div>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <!-- Payment Method Card -->
                <Card v-if="subscription">
                    <CardHeader>
                        <CardTitle>Zahlungsmethode</CardTitle>
                        <CardDescription>Verwalte deine Zahlungsinformationen über Stripe</CardDescription>
                    </CardHeader>
                    <CardContent>
                        <div class="flex items-center gap-3">
                            <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-muted">
                                <CreditCard class="h-5 w-5" />
                            </div>
                            <div class="flex-1">
                                <p v-if="paymentMethod" class="font-medium">
                                    {{ formatCardBrand(paymentMethod.brand) }} •••• {{ paymentMethod.last4 }}
                                </p>
                                <p v-else class="font-medium text-muted-foreground">
                                    Keine Zahlungsmethode hinterlegt
                                </p>
                                <p v-if="paymentMethod?.exp_month && paymentMethod?.exp_year" class="text-sm text-muted-foreground">
                                    Läuft ab {{ paymentMethod.exp_month }}/{{ paymentMethod.exp_year }}
                                </p>
                            </div>
                            <Link href="/subscription/billing-portal">
                                <Button variant="outline" size="sm">
                                    <ExternalLink class="mr-2 h-4 w-4" />
                                    Verwalten
                                </Button>
                            </Link>
                        </div>
                        <p class="mt-3 text-xs text-muted-foreground">
                            Du wirst zum Stripe Kundenportal weitergeleitet, um deine Zahlungsmethode sicher zu ändern.
                        </p>
                    </CardContent>
                </Card>

                <!-- Invoices Card -->
                <Card v-if="invoices.length > 0">
                    <CardHeader>
                        <div class="flex items-center justify-between">
                            <div>
                                <CardTitle>Rechnungen</CardTitle>
                                <CardDescription>Lade deine bisherigen Rechnungen herunter</CardDescription>
                            </div>
                            <Link href="/subscription/billing-portal">
                                <Button variant="outline" size="sm">
                                    <ExternalLink class="mr-2 h-4 w-4" />
                                    Alle Rechnungen
                                </Button>
                            </Link>
                        </div>
                    </CardHeader>
                    <CardContent>
                        <div class="space-y-3">
                            <div
                                v-for="invoice in invoices.slice(0, 5)"
                                :key="invoice.id"
                                class="flex items-center justify-between border-b pb-3 last:border-0 last:pb-0"
                            >
                                <div>
                                    <p class="font-medium">{{ formatDate(invoice.date) }}</p>
                                    <p class="text-sm text-muted-foreground">
                                        {{ (invoice.total / 100).toFixed(2).replace('.', ',') }} €
                                    </p>
                                </div>
                                <Link :href="`/subscription/invoice/${invoice.id}`">
                                    <Button variant="ghost" size="sm">
                                        <Download class="h-4 w-4" />
                                    </Button>
                                </Link>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <!-- Stripe Portal Hint für bezahlte Pläne -->
                <div v-if="subscription" class="rounded-lg border bg-muted/30 p-4">
                    <div class="flex items-start gap-3">
                        <ExternalLink class="h-5 w-5 text-muted-foreground mt-0.5" />
                        <div>
                            <p class="font-medium">Stripe Kundenportal</p>
                            <p class="text-sm text-muted-foreground mb-3">
                                Im Stripe Kundenportal kannst du alle Abrechnungsdetails verwalten:
                                Zahlungsmethode ändern, Rechnungen einsehen, Subscription kündigen.
                            </p>
                            <Link href="/subscription/billing-portal">
                                <Button variant="outline" size="sm">
                                    <ExternalLink class="mr-2 h-4 w-4" />
                                    Zum Kundenportal
                                </Button>
                            </Link>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
