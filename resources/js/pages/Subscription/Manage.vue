<script setup>
/**
 * SUBSCRIPTION MANAGE PAGE - HYBRID-SYSTEM
 * ==========================================
 *
 * Diese Seite funktioniert mit ZWEI verschiedenen Subscription-Typen:
 *
 * 1. KOSTENLOSE PLÄNE (Free, 100% Promo):
 *    - props.subscription = null (keine Cashier subscription in DB)
 *    - props.currentPlan = Plan-Objekt (über user.plan_id)
 *    - Zeigt nur: Plan-Details, "Plan ändern" Button
 *    - KEIN Cancel/Resume, keine Zahlungsmethode, keine Rechnungen
 *
 * 2. BEZAHLTE PLÄNE (mit Stripe-Billing):
 *    - props.subscription = Cashier subscription Objekt
 *    - props.currentPlan = Plan-Objekt
 *    - Zeigt alles: Plan-Details, Cancel/Resume, Zahlungsmethode, Rechnungen
 *
 * Die Komponente prüft, ob subscription existiert, und zeigt Features entsprechend.
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
    Clock
} from 'lucide-vue-next';
import { ref } from 'vue';

const props = defineProps({
    // Cashier subscription (null bei kostenlosen Plänen)
    subscription: {
        type: Object,
        default: null,
    },
    // Plan-Details (immer vorhanden über user.plan_id)
    currentPlan: {
        type: Object,
        required: true,
    },
    // Rechnungen (nur bei Cashier subscriptions)
    invoices: {
        type: Array,
        default: () => [],
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

const cancelSubscription = () => {
    if (!confirm('Möchtest du deine Subscription wirklich kündigen?')) return;

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

/**
 * Berechnet den Subscription-Status Badge
 *
 * WICHTIG: Prüft currentPlan, NICHT subscription!
 * Warum? Weil kostenlose Pläne keine Cashier subscription haben,
 * aber trotzdem einen aktiven Plan (über plan_id).
 *
 * Logik:
 * 1. Kein Plan oder Free Plan → "Kein Abo" (grau)
 * 2. Hat Plan + subscription.ends_at → "Gekündigt" (rot)
 * 3. Hat Plan → "Aktiv" (grün)
 */
const getSubscriptionStatus = () => {
    // Fall 1: Kein Plan oder Free Plan
    if (!props.currentPlan || props.currentPlan.name === 'Free') {
        return { text: 'Kein Abo', variant: 'secondary', icon: XCircle };
    }

    // Fall 2: Plan vorhanden, aber Cashier subscription wurde gekündigt
    if (props.subscription && props.subscription.ends_at) {
        return { text: 'Gekündigt', variant: 'destructive', icon: AlertCircle };
    }

    // Fall 3: Aktiver Plan (egal ob mit oder ohne Cashier subscription)
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
                            <span class="font-semibold">{{ currentPlan.name }}</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-sm text-muted-foreground">Preis</span>
                            <span class="font-semibold">{{ Number(currentPlan.price).toFixed(2).replace('.', ',') }} € / Monat</span>
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
                        <div class="flex gap-3 border-t pt-4">
                            <!-- "Plan ändern" immer verfügbar -->
                            <Link href="/subscription" class="flex-1">
                                <Button variant="outline" class="w-full">
                                    Plan ändern
                                </Button>
                            </Link>

                            <!-- Cancel/Resume Buttons: NUR bei Cashier subscriptions -->
                            <!-- Grund: Kostenlose Pläne haben keine subscription zum kündigen -->
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
                                        {{ platformsConnected }} von {{ maxPlatforms }} genutzt
                                    </p>
                                </div>
                            </div>
                            <div class="text-right">
                                <p class="text-2xl font-bold">{{ platformsConnected }}/{{ maxPlatforms }}</p>
                            </div>
                        </div>

                        <!-- Trial Info -->
                        <div v-if="onTrial" class="flex items-center gap-3 rounded-lg border border-blue-200 bg-blue-50 p-4">
                            <Clock class="h-5 w-5 text-blue-600" />
                            <div>
                                <p class="font-medium text-blue-900">Testphase aktiv</p>
                                <p class="text-sm text-blue-700">
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
                <!-- Nur bei Cashier subscriptions: Kostenlose Pläne haben keine Zahlungsmethode -->
                <Card v-if="subscription">
                    <CardHeader>
                        <CardTitle>Zahlungsmethode</CardTitle>
                        <CardDescription>Verwalte deine Zahlungsinformationen</CardDescription>
                    </CardHeader>
                    <CardContent>
                        <div class="flex items-center gap-3">
                            <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-muted">
                                <CreditCard class="h-5 w-5" />
                            </div>
                            <div class="flex-1">
                                <p class="font-medium">•••• •••• •••• 4242</p>
                                <p class="text-sm text-muted-foreground">Läuft ab 12/2025</p>
                            </div>
                            <Button variant="outline" size="sm">
                                Aktualisieren
                            </Button>
                        </div>
                    </CardContent>
                </Card>

                <!-- Invoices Card -->
                <!-- Nur bei Cashier subscriptions: Kostenlose Pläne haben keine Rechnungen -->
                <Card v-if="invoices.length > 0">
                    <CardHeader>
                        <CardTitle>Rechnungen</CardTitle>
                        <CardDescription>Lade deine bisherigen Rechnungen herunter</CardDescription>
                    </CardHeader>
                    <CardContent>
                        <div class="space-y-3">
                            <div
                                v-for="invoice in invoices"
                                :key="invoice.id"
                                class="flex items-center justify-between border-b pb-3 last:border-0 last:pb-0"
                            >
                                <div>
                                    <p class="font-medium">{{ formatDate(invoice.date) }}</p>
                                    <p class="text-sm text-muted-foreground">
                                        {{ (invoice.total / 100).toFixed(2) }} €
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
            </div>
        </div>
    </AppLayout>
</template>
