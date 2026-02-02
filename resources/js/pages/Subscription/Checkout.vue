<script setup>
/**
 * Checkout-Seite mit Stripe Checkout Redirect
 *
 * NEUER FLOW:
 * 1. User sieht Plan-Zusammenfassung
 * 2. Kann zwischen Monatlich/Jährlich wechseln
 * 3. Kann Promo-Code eingeben
 * 4. Klickt "Weiter zur Zahlung"
 * 5. Wird zu Stripe Checkout weitergeleitet
 * 6. Stripe wickelt Zahlung ab (Karte, Apple Pay, Google Pay, SEPA, etc.)
 * 7. Redirect zurück zur Success-Seite
 *
 * VORTEILE gegenüber Stripe Elements:
 * - Alle Zahlungsmethoden automatisch
 * - PCI Compliance einfacher
 * - Payment Links funktionieren
 * - Weniger Frontend-Code
 */

import AppLayout from '@/layouts/AppLayout.vue';
import { Head, router, Link } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardFooter, CardHeader, CardTitle } from '@/components/ui/card';
import { Alert, AlertDescription } from '@/components/ui/alert';
import { Label } from '@/components/ui/label';
import { Separator } from '@/components/ui/separator';
import { Badge } from '@/components/ui/badge';
import { CreditCard, Lock, Check, AlertCircle, CheckCircle, XCircle, ExternalLink } from 'lucide-vue-next';
import { ref, computed } from 'vue';
import axios from 'axios';

const props = defineProps({
    plan: {
        type: Object,
        required: true,
    },
    siblingPlan: {
        type: Object,
        default: null,
    },
});

const loading = ref(false);
const error = ref(null);
const promoCode = ref('');
const promoCodeApplied = ref(null);
const promoCodeError = ref(null);
const validatingPromoCode = ref(false);

// Aktuell ausgewählter Plan (kann durch Toggle gewechselt werden)
const selectedPlan = ref(props.plan);

// Berechne ob jährlich ausgewählt ist
const isYearly = computed(() => selectedPlan.value.billing_interval === 'yearly');

// Toggle zwischen monatlich und jährlich
const toggleInterval = () => {
    if (props.siblingPlan) {
        // Bestimme welcher Plan monatlich und welcher jährlich ist
        const monthlyPlan = props.plan.billing_interval === 'monthly' ? props.plan : props.siblingPlan;
        const yearlyPlan = props.plan.billing_interval === 'yearly' ? props.plan : props.siblingPlan;

        // Wechsle zum anderen Plan
        selectedPlan.value = isYearly.value ? monthlyPlan : yearlyPlan;

        // Promo Code zurücksetzen bei Plan-Wechsel
        if (promoCodeApplied.value) {
            removePromoCode();
        }
    }
};

// Berechne Ersparnis bei jährlicher Zahlung
const yearlySavings = computed(() => {
    if (!props.siblingPlan) return null;

    const monthlyPlan = props.plan.billing_interval === 'monthly' ? props.plan : props.siblingPlan;
    const yearlyPlan = props.plan.billing_interval === 'yearly' ? props.plan : props.siblingPlan;

    if (!monthlyPlan || !yearlyPlan) return null;

    const yearlyMonthlyEquivalent = yearlyPlan.price / 12;
    const savings = monthlyPlan.price - yearlyMonthlyEquivalent;

    return savings > 0 ? Math.round(savings * 12) : null;
});

const validatePromoCode = async () => {
    if (!promoCode.value) return;

    validatingPromoCode.value = true;
    promoCodeError.value = null;

    try {
        const { data } = await axios.post('/subscription/validate-promo-code', {
            code: promoCode.value,
            plan_id: selectedPlan.value.id,
        });

        if (data.valid) {
            promoCodeApplied.value = data;
            promoCodeError.value = null;
        } else {
            promoCodeError.value = data.message || 'Ungültiger Promo Code';
            promoCodeApplied.value = null;
        }
    } catch (err) {
        if (err.response && err.response.data) {
            promoCodeError.value = err.response.data.message || 'Ungültiger Promo Code';
        } else {
            promoCodeError.value = 'Fehler beim Validieren des Promo Codes';
        }
        promoCodeApplied.value = null;
    } finally {
        validatingPromoCode.value = false;
    }
};

const removePromoCode = () => {
    promoCode.value = '';
    promoCodeApplied.value = null;
    promoCodeError.value = null;
};

// Endpreis nach Rabatt
const finalPrice = computed(() => {
    if (promoCodeApplied.value) {
        return Number(promoCodeApplied.value.final_price);
    }
    return Number(selectedPlan.value.price);
});

// Submit -> Redirect zu Stripe Checkout (oder direkte Aktivierung bei 100% Rabatt)
const handleSubmit = () => {
    if (loading.value) return;

    loading.value = true;
    error.value = null;

    // POST an Backend
    // Promo Code wird nur mitgesendet wenn 100% Rabatt (kostenlose Aktivierung)
    // Für Teil-Rabatte muss der Code bei Stripe eingegeben werden
    router.post(`/subscription/subscribe/${selectedPlan.value.id}`, {
        promo_code: (promoCodeApplied.value && finalPrice.value === 0) ? promoCode.value : null,
    }, {
        onError: (errors) => {
            error.value = errors.error || 'Ein Fehler ist aufgetreten. Bitte versuche es erneut.';
            loading.value = false;
        },
    });
};
</script>

<template>
    <Head :title="`Checkout - ${selectedPlan.name}`" />

    <AppLayout :breadcrumbs="[
        { label: 'Subscription', href: '/subscription' },
        { label: 'Checkout', href: `/subscription/checkout/${plan.id}` }
    ]">
        <div class="mx-auto max-w-4xl space-y-6 p-4 md:p-6 lg:p-8">
            <!-- Header -->
            <div>
                <h1 class="text-2xl font-bold tracking-tight md:text-3xl">Checkout</h1>
                <p class="text-muted-foreground">Wähle deine Optionen und fahre zur Zahlung fort</p>
            </div>

            <div class="grid gap-6 lg:grid-cols-3">
                <!-- Options -->
                <div class="lg:col-span-2 space-y-6">
                    <!-- Billing Interval Card -->
                    <Card v-if="siblingPlan">
                        <CardHeader>
                            <CardTitle>Abrechnungszeitraum</CardTitle>
                            <CardDescription>
                                Wähle zwischen monatlicher und jährlicher Abrechnung
                            </CardDescription>
                        </CardHeader>
                        <CardContent>
                            <div class="flex items-center gap-4 p-4 rounded-lg border bg-muted/30">
                                <span
                                    :class="[
                                        'text-sm font-medium transition-colors cursor-pointer',
                                        !isYearly ? 'text-foreground' : 'text-muted-foreground',
                                    ]"
                                    @click="!isYearly || toggleInterval()"
                                >
                                    Monatlich
                                </span>
                                <button
                                    type="button"
                                    @click="toggleInterval"
                                    :class="[
                                        'relative inline-flex h-6 w-11 items-center rounded-full transition-colors',
                                        isYearly ? 'bg-primary' : 'bg-muted-foreground/30',
                                    ]"
                                >
                                    <span
                                        :class="[
                                            'inline-block h-4 w-4 transform rounded-full bg-white transition-transform',
                                            isYearly ? 'translate-x-6' : 'translate-x-1',
                                        ]"
                                    />
                                </button>
                                <span
                                    :class="[
                                        'text-sm font-medium transition-colors cursor-pointer',
                                        isYearly ? 'text-foreground' : 'text-muted-foreground',
                                    ]"
                                    @click="isYearly || toggleInterval()"
                                >
                                    Jährlich
                                </span>
                                <Badge v-if="yearlySavings" variant="secondary" class="ml-auto text-xs">
                                    Spare {{ yearlySavings }}€/Jahr
                                </Badge>
                            </div>
                        </CardContent>
                    </Card>

                    <!-- Promo Code Card (nur für 100% Rabattcodes) -->
                    <Card>
                        <CardHeader>
                            <CardTitle>Gutscheincode</CardTitle>
                            <CardDescription>
                                Hast du einen Gutscheincode für kostenlosen Zugang?
                            </CardDescription>
                        </CardHeader>
                        <CardContent class="space-y-4">
                            <div class="flex gap-2">
                                <input
                                    v-model="promoCode"
                                    type="text"
                                    placeholder="z.B. GRATIS100"
                                    class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                                    :disabled="!!promoCodeApplied"
                                    @keyup.enter="validatePromoCode"
                                />
                                <Button
                                    v-if="!promoCodeApplied"
                                    type="button"
                                    variant="outline"
                                    @click="validatePromoCode"
                                    :disabled="!promoCode || validatingPromoCode"
                                >
                                    {{ validatingPromoCode ? 'Prüfe...' : 'Anwenden' }}
                                </Button>
                                <Button
                                    v-else
                                    type="button"
                                    variant="outline"
                                    @click="removePromoCode"
                                >
                                    Entfernen
                                </Button>
                            </div>

                            <!-- Promo Code Success -->
                            <div v-if="promoCodeApplied && finalPrice === 0" class="rounded-lg bg-green-100 p-3 text-sm text-green-800 dark:bg-green-900/20 dark:text-green-500">
                                <CheckCircle class="mr-2 inline h-4 w-4" />
                                {{ promoCodeApplied.message }} - 100% Rabatt!
                            </div>

                            <!-- Promo Code mit Teil-Rabatt: Hinweis auf Stripe -->
                            <div v-else-if="promoCodeApplied && finalPrice > 0" class="rounded-lg bg-blue-100 p-3 text-sm text-blue-800 dark:bg-blue-900/20 dark:text-blue-500">
                                <AlertCircle class="mr-2 inline h-4 w-4" />
                                Dieser Code gibt {{ promoCodeApplied.discount.toFixed(2).replace('.', ',') }} € Rabatt.
                                Gib den Code bei der Zahlung auf Stripe ein.
                            </div>

                            <!-- Promo Code Error -->
                            <div v-if="promoCodeError" class="rounded-lg bg-red-100 p-3 text-sm text-red-800 dark:bg-red-900/20 dark:text-red-500">
                                <XCircle class="mr-2 inline h-4 w-4" />
                                {{ promoCodeError }}
                            </div>

                            <p class="text-xs text-muted-foreground">
                                Rabattcodes kannst du auch direkt bei Stripe während der Zahlung eingeben.
                            </p>
                        </CardContent>
                    </Card>

                    <!-- Payment Info Card -->
                    <Card>
                        <CardHeader>
                            <CardTitle>Zahlung</CardTitle>
                            <CardDescription>
                                Die Zahlung wird sicher über Stripe abgewickelt
                            </CardDescription>
                        </CardHeader>
                        <CardContent class="space-y-4">
                            <!-- Kostenlos-Hinweis -->
                            <Alert v-if="finalPrice === 0" class="bg-green-50 border-green-200 dark:bg-green-900/20 dark:border-green-800">
                                <CheckCircle class="h-4 w-4 text-green-600 dark:text-green-500" />
                                <AlertDescription class="text-green-800 dark:text-green-400">
                                    Durch deinen Promo Code ist dieser Plan kostenlos! Keine Zahlungsinformationen erforderlich.
                                </AlertDescription>
                            </Alert>

                            <!-- Stripe Info -->
                            <div v-else class="space-y-3">
                                <div class="flex items-center gap-3 p-4 rounded-lg bg-muted/50">
                                    <CreditCard class="h-8 w-8 text-muted-foreground" />
                                    <div>
                                        <p class="font-medium">Sichere Zahlung mit Stripe</p>
                                        <p class="text-sm text-muted-foreground">
                                            Kreditkarte, SEPA-Lastschrift, Apple Pay, Google Pay und mehr
                                        </p>
                                    </div>
                                </div>

                                <div class="flex items-start gap-2 rounded-lg bg-muted p-3 text-sm">
                                    <Lock class="h-4 w-4 flex-shrink-0 text-muted-foreground mt-0.5" />
                                    <p class="text-muted-foreground">
                                        Du wirst zu Stripe weitergeleitet, um die Zahlung sicher abzuschließen.
                                        Wir speichern keine Zahlungsdaten auf unseren Servern.
                                    </p>
                                </div>
                            </div>

                            <!-- Error Alert -->
                            <Alert v-if="error" variant="destructive">
                                <AlertCircle class="h-4 w-4" />
                                <AlertDescription>{{ error }}</AlertDescription>
                            </Alert>
                        </CardContent>
                        <CardFooter>
                            <Button
                                @click="handleSubmit"
                                :disabled="loading"
                                class="w-full"
                                size="lg"
                            >
                                <template v-if="loading">
                                    Weiterleitung...
                                </template>
                                <template v-else-if="finalPrice === 0">
                                    <CheckCircle class="mr-2 h-4 w-4" />
                                    Jetzt kostenlos aktivieren
                                </template>
                                <template v-else>
                                    <ExternalLink class="mr-2 h-4 w-4" />
                                    Weiter zur Zahlung ({{ finalPrice.toFixed(2).replace('.', ',') }} €{{ isYearly ? '/Jahr' : '/Monat' }})
                                </template>
                            </Button>
                        </CardFooter>
                    </Card>
                </div>

                <!-- Order Summary -->
                <div class="lg:col-span-1">
                    <Card class="sticky top-4">
                        <CardHeader>
                            <CardTitle>Bestellübersicht</CardTitle>
                        </CardHeader>
                        <CardContent class="space-y-4">
                            <!-- Plan Details -->
                            <div>
                                <p class="font-semibold text-lg">{{ selectedPlan.name }}</p>
                                <p class="text-sm text-muted-foreground">{{ selectedPlan.description }}</p>
                                <Badge v-if="isYearly" variant="secondary" class="mt-2">
                                    Jährliche Abrechnung
                                </Badge>
                            </div>

                            <Separator />

                            <!-- Features -->
                            <div class="space-y-2">
                                <p class="text-sm font-medium">Enthaltene Features:</p>
                                <ul class="space-y-2">
                                    <li
                                        v-for="(feature, index) in selectedPlan.features"
                                        :key="index"
                                        class="flex items-start gap-2 text-sm"
                                    >
                                        <Check class="mt-0.5 h-4 w-4 flex-shrink-0 text-primary" />
                                        <span>{{ feature }}</span>
                                    </li>
                                </ul>
                            </div>

                            <Separator />

                            <!-- Price Breakdown -->
                            <div class="space-y-2">
                                <div class="flex justify-between text-sm">
                                    <span class="text-muted-foreground">
                                        {{ isYearly ? 'Jährlicher Preis' : 'Monatlicher Preis' }}
                                    </span>
                                    <span>{{ Number(selectedPlan.price).toFixed(2).replace('.', ',') }} €</span>
                                </div>

                                <!-- Monatlicher Äquivalent bei jährlich -->
                                <div v-if="isYearly" class="flex justify-between text-sm text-muted-foreground">
                                    <span>Entspricht pro Monat</span>
                                    <span>{{ (selectedPlan.price / 12).toFixed(2).replace('.', ',') }} €</span>
                                </div>

                                <!-- Promo Code Discount -->
                                <div v-if="promoCodeApplied" class="flex justify-between text-sm text-green-600 dark:text-green-500">
                                    <span>Rabatt ({{ promoCodeApplied.promo_code.code }})</span>
                                    <span>-{{ Number(promoCodeApplied.discount).toFixed(2).replace('.', ',') }} €</span>
                                </div>

                                <Separator />
                                <div class="flex justify-between font-semibold text-lg">
                                    <span>Gesamt</span>
                                    <span :class="finalPrice === 0 ? 'text-green-600 dark:text-green-500' : ''">
                                        {{ finalPrice === 0 ? 'Kostenlos' : `${finalPrice.toFixed(2).replace('.', ',')} €` }}
                                    </span>
                                </div>
                                <p v-if="finalPrice > 0" class="text-xs text-muted-foreground">
                                    {{ isYearly ? 'Jährliche Abrechnung' : 'Monatliche Abrechnung' }} inkl. MwSt.
                                </p>
                            </div>

                            <!-- Info Notice -->
                            <Alert>
                                <AlertDescription class="text-sm">
                                    Du kannst jederzeit kündigen. Keine Mindestlaufzeit.
                                </AlertDescription>
                            </Alert>
                        </CardContent>
                    </Card>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
