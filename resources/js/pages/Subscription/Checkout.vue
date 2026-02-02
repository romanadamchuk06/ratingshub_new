<script setup>
/**
 * Checkout-Seite mit Monatlich/Jährlich Toggle
 *
 * Features:
 * - Toggle zwischen monatlicher und jährlicher Zahlung
 * - Promo Code Eingabe
 * - Stripe Elements für Kartenzahlung
 * - Kostenlose Aktivierung bei 100% Rabatt
 */

import AppLayout from '@/layouts/AppLayout.vue';
import { Head, router, Link } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardFooter, CardHeader, CardTitle } from '@/components/ui/card';
import { Alert, AlertDescription } from '@/components/ui/alert';
import { Label } from '@/components/ui/label';
import { Separator } from '@/components/ui/separator';
import { Badge } from '@/components/ui/badge';
import { CreditCard, Lock, Check, AlertCircle, CheckCircle, XCircle } from 'lucide-vue-next';
import { ref, onMounted, computed, watch } from 'vue';
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
    intent: {
        type: Object,
        required: true,
    },
});

const loading = ref(false);
const error = ref(null);
const stripe = ref(null);
const cardElement = ref(null);
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
        selectedPlan.value = isYearly.value ? props.siblingPlan : props.plan;
        // Wenn Plan gewechselt wird, Promo Code zurücksetzen
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

onMounted(async () => {
    if (window.Stripe) {
        stripe.value = window.Stripe(import.meta.env.VITE_STRIPE_KEY);

        const elements = stripe.value.elements();
        cardElement.value = elements.create('card', {
            style: {
                base: {
                    fontSize: '16px',
                    color: 'hsl(var(--foreground))',
                    fontFamily: 'system-ui, sans-serif',
                    '::placeholder': {
                        color: 'hsl(var(--muted-foreground))',
                    },
                },
                invalid: {
                    color: 'hsl(var(--destructive))',
                },
            },
        });

        cardElement.value.mount('#card-element');

        cardElement.value.on('change', (event) => {
            if (event.error) {
                error.value = event.error.message;
            } else {
                error.value = null;
            }
        });
    } else {
        error.value = 'Stripe konnte nicht geladen werden. Bitte lade die Seite neu.';
    }
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
    } catch (error) {
        if (error.response && error.response.data) {
            promoCodeError.value = error.response.data.message || 'Ungültiger Promo Code';
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

// Braucht Zahlungsmethode?
const requiresPaymentMethod = computed(() => {
    return finalPrice.value > 0;
});

const handleSubmit = async () => {
    if (loading.value) return;

    loading.value = true;
    error.value = null;

    try {
        let paymentMethod = null;

        if (requiresPaymentMethod.value) {
            const { setupIntent, error: stripeError } = await stripe.value.confirmCardSetup(
                props.intent.client_secret,
                {
                    payment_method: {
                        card: cardElement.value,
                    },
                }
            );

            if (stripeError) {
                error.value = stripeError.message;
                loading.value = false;
                return;
            }

            paymentMethod = setupIntent.payment_method;
        }

        router.post(`/subscription/subscribe/${selectedPlan.value.id}`, {
            payment_method: paymentMethod,
            promo_code: promoCodeApplied.value ? promoCode.value : null,
        }, {
            onSuccess: () => {},
            onError: (errors) => {
                error.value = errors.message || 'Ein Fehler ist aufgetreten. Bitte versuche es erneut.';
                loading.value = false;
            },
        });
    } catch (e) {
        error.value = 'Ein unerwarteter Fehler ist aufgetreten. Bitte versuche es erneut.';
        loading.value = false;
    }
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
                <p class="text-muted-foreground">Schließe deine Bestellung ab</p>
            </div>

            <div class="grid gap-6 lg:grid-cols-3">
                <!-- Payment Form -->
                <div class="lg:col-span-2">
                    <Card>
                        <CardHeader>
                            <CardTitle>Zahlungsinformationen</CardTitle>
                            <CardDescription>
                                Deine Zahlungsinformationen werden sicher über Stripe verarbeitet
                            </CardDescription>
                        </CardHeader>
                        <CardContent class="space-y-4">
                            <!-- Billing Interval Toggle (nur wenn Schwester-Plan existiert) -->
                            <div v-if="siblingPlan" class="space-y-2">
                                <Label>Abrechnungszeitraum</Label>
                                <div class="flex items-center gap-4 p-3 rounded-lg border bg-muted/30">
                                    <span
                                        :class="[
                                            'text-sm font-medium transition-colors',
                                            !isYearly ? 'text-foreground' : 'text-muted-foreground',
                                        ]"
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
                                            'text-sm font-medium transition-colors',
                                            isYearly ? 'text-foreground' : 'text-muted-foreground',
                                        ]"
                                    >
                                        Jährlich
                                    </span>
                                    <Badge v-if="yearlySavings" variant="secondary" class="ml-auto text-xs">
                                        Spare {{ yearlySavings }}€/Jahr
                                    </Badge>
                                </div>
                            </div>

                            <!-- Card Element (nur wenn Zahlung erforderlich) -->
                            <div v-if="requiresPaymentMethod" class="space-y-2">
                                <Label for="card-element">Kreditkarte</Label>
                                <div
                                    id="card-element"
                                    class="rounded-md border border-input bg-background px-3 py-2"
                                ></div>
                            </div>

                            <!-- Kostenlos-Hinweis -->
                            <Alert v-else class="bg-green-50 border-green-200 dark:bg-green-900/20 dark:border-green-800">
                                <CheckCircle class="h-4 w-4 text-green-600 dark:text-green-500" />
                                <AlertDescription class="text-green-800 dark:text-green-400">
                                    Dieser Plan ist durch deinen Promo Code kostenlos! Keine Zahlungsinformationen erforderlich.
                                </AlertDescription>
                            </Alert>

                            <!-- Promo Code -->
                            <div class="space-y-2">
                                <Label for="promo-code">Promo Code (optional)</Label>
                                <div class="flex gap-2">
                                    <input
                                        v-model="promoCode"
                                        type="text"
                                        id="promo-code"
                                        placeholder="z.B. WILLKOMMEN20"
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
                                <div v-if="promoCodeApplied" class="rounded-lg bg-green-100 p-3 text-sm text-green-800 dark:bg-green-900/20 dark:text-green-500">
                                    <CheckCircle class="mr-2 inline h-4 w-4" />
                                    {{ promoCodeApplied.message }} - Rabatt: {{ promoCodeApplied.discount.toFixed(2).replace('.', ',') }} €
                                </div>

                                <!-- Promo Code Error -->
                                <div v-if="promoCodeError" class="rounded-lg bg-red-100 p-3 text-sm text-red-800 dark:bg-red-900/20 dark:text-red-500">
                                    <XCircle class="mr-2 inline h-4 w-4" />
                                    {{ promoCodeError }}
                                </div>
                            </div>

                            <!-- Error Alert -->
                            <Alert v-if="error" variant="destructive">
                                <AlertCircle class="h-4 w-4" />
                                <AlertDescription>{{ error }}</AlertDescription>
                            </Alert>

                            <!-- Security Notice -->
                            <div class="flex items-start gap-2 rounded-lg bg-muted p-3 text-sm">
                                <Lock class="h-4 w-4 flex-shrink-0 text-muted-foreground" />
                                <p class="text-muted-foreground">
                                    Deine Zahlungsinformationen werden verschlüsselt übertragen und sicher gespeichert.
                                    Wir speichern keine Kreditkartendaten auf unseren Servern.
                                </p>
                            </div>
                        </CardContent>
                        <CardFooter>
                            <Button
                                @click="handleSubmit"
                                :disabled="loading"
                                class="w-full"
                                size="lg"
                            >
                                <CreditCard v-if="requiresPaymentMethod" class="mr-2 h-4 w-4" />
                                <CheckCircle v-else class="mr-2 h-4 w-4" />
                                <template v-if="loading">
                                    Verarbeitung...
                                </template>
                                <template v-else-if="finalPrice === 0">
                                    Jetzt kostenlos starten
                                </template>
                                <template v-else>
                                    Jetzt für {{ finalPrice.toFixed(2).replace('.', ',') }} €{{ isYearly ? '/Jahr' : '/Monat' }} starten
                                </template>
                            </Button>
                        </CardFooter>
                    </Card>
                </div>

                <!-- Order Summary -->
                <div class="lg:col-span-1">
                    <Card>
                        <CardHeader>
                            <CardTitle>Bestellübersicht</CardTitle>
                        </CardHeader>
                        <CardContent class="space-y-4">
                            <!-- Plan Details -->
                            <div>
                                <p class="font-semibold">{{ selectedPlan.name }}</p>
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
