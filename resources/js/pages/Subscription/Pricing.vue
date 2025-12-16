<script setup>
/**
 * Pricing Page mit monatlich/jährlich Toggle
 *
 * Features:
 * - Toggle zwischen monatlicher und jährlicher Abrechnung
 * - Gruppierung von Plänen nach billing_interval
 * - Anzeige der Ersparnis bei jährlicher Zahlung
 * - Free-Plan wird bei beiden Ansichten angezeigt
 */

import AppLayout from '@/layouts/AppLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent, CardDescription, CardFooter, CardHeader, CardTitle } from '@/components/ui/card';
import { Check, Zap, TrendingUp, Rocket } from 'lucide-vue-next';

const props = defineProps({
    plans: {
        type: Array,
        required: true,
    },
    currentPlan: {
        type: Object,
        default: null,
    },
});

// Billing Interval State ('monthly' oder 'yearly')
const selectedInterval = ref('monthly');

/**
 * Filtert Pläne nach ausgewähltem Intervall
 * Free-Plan wird immer angezeigt (ist immer 'monthly')
 */
const filteredPlans = computed(() => {
    return props.plans.filter((plan) => {
        return plan.billing_interval === selectedInterval.value;
    });
});

const getPlanIcon = (slug) => {
    // Slug kann jetzt -monthly oder -yearly Suffix haben, deshalb prüfen wir nur den Anfang
    if (slug.startsWith('free')) return Zap;
    if (slug.startsWith('basic')) return Zap;
    if (slug.startsWith('pro')) return TrendingUp;
    if (slug.startsWith('enterprise')) return Rocket;
    return Zap;
};

const isCurrentPlan = (plan) => {
    return props.currentPlan?.id === plan.id;
};

/**
 * Berechnet die monatliche Ersparnis für jährliche Pläne
 * Beispiel: Jährlich 299.99€ → monatlich 25€ statt 29.99€ → Ersparnis 5€/Monat
 */
const getYearlySavings = (plan) => {
    if (plan.billing_interval !== 'yearly') return null;

    const monthlyEquivalent = Math.round(plan.price / 12);

    // Finde den entsprechenden monatlichen Plan
    const monthlyPlan = props.plans.find(
        (p) => p.name === plan.name && p.billing_interval === 'monthly'
    );

    if (!monthlyPlan) return null;

    const savings = monthlyPlan.price - monthlyEquivalent;
    return savings > 0 ? Math.round(savings) : null;
};
</script>

<template>
    <Head title="Preise & Pläne" />

    <AppLayout :breadcrumbs="[
        { label: 'Subscription', href: '/subscription' }
    ]">
        <div class="space-y-8 p-4 md:p-6 lg:p-8">
            <!-- Header -->
            <div class="text-center space-y-6">
                <div>
                    <h1 class="mb-4 text-3xl font-bold tracking-tight md:text-4xl">
                        Einfache, transparente Preise
                    </h1>
                    <p class="mx-auto max-w-2xl text-lg text-muted-foreground">
                        Wähle den Plan, der zu dir passt. Jederzeit kündbar.
                    </p>
                </div>

                <!-- Billing Interval Toggle -->
                <div class="flex items-center justify-center gap-4">
                    <span
                        :class="[
                            'text-sm font-medium transition-colors',
                            selectedInterval === 'monthly' ? 'text-foreground' : 'text-muted-foreground',
                        ]"
                    >
                        Monatlich
                    </span>
                    <button
                        @click="selectedInterval = selectedInterval === 'monthly' ? 'yearly' : 'monthly'"
                        :class="[
                            'relative inline-flex h-6 w-11 items-center rounded-full transition-colors',
                            selectedInterval === 'yearly' ? 'bg-primary' : 'bg-muted',
                        ]"
                    >
                        <span
                            :class="[
                                'inline-block h-4 w-4 transform rounded-full bg-white transition-transform',
                                selectedInterval === 'yearly' ? 'translate-x-6' : 'translate-x-1',
                            ]"
                        />
                    </button>
                    <span
                        :class="[
                            'text-sm font-medium transition-colors',
                            selectedInterval === 'yearly' ? 'text-foreground' : 'text-muted-foreground',
                        ]"
                    >
                        Jährlich
                        <Badge variant="secondary" class="ml-2 text-xs">2 Monate gratis</Badge>
                    </span>
                </div>
            </div>

            <!-- Pricing Cards -->
            <div class="mx-auto grid max-w-5xl gap-6 md:grid-cols-3">
                <Card
                    v-for="plan in filteredPlans"
                    :key="plan.id"
                    :class="[
                        'relative flex flex-col',
                        plan.is_popular ? 'border-primary shadow-lg scale-105' : '',
                    ]"
                >
                    <!-- Popular Badge - Dynamisch gesteuert über is_popular Flag -->
                    <div v-if="plan.is_popular" class="absolute -top-3 left-1/2 -translate-x-1/2">
                        <Badge class="bg-primary px-3 py-1">
                            Beliebt
                        </Badge>
                    </div>

                    <CardHeader class="text-center">
                        <!-- Icon -->
                        <div class="mb-4 flex justify-center">
                            <div
                                :class="[
                                    'flex h-12 w-12 items-center justify-center rounded-lg',
                                    plan.is_popular ? 'bg-primary/10' : 'bg-muted',
                                ]"
                            >
                                <component
                                    :is="getPlanIcon(plan.slug)"
                                    :class="[
                                        'h-6 w-6',
                                        plan.is_popular ? 'text-primary' : 'text-muted-foreground',
                                    ]"
                                />
                            </div>
                        </div>

                        <CardTitle class="text-2xl">{{ plan.name }}</CardTitle>
                        <CardDescription>{{ plan.description }}</CardDescription>

                        <!-- Price -->
                        <div class="mt-4">
                            <!-- Free Plan -->
                            <div v-if="Number(plan.price) === 0" class="flex items-baseline justify-center gap-1">
                                <span class="text-4xl font-bold">
                                    Kostenlos
                                </span>
                            </div>
                            <!-- Paid Plans -->
                            <div v-else class="space-y-2">
                                <div class="flex items-baseline justify-center gap-1">
                                    <span class="text-4xl font-bold">
                                        {{ Math.round(Number(plan.price)) }} €
                                    </span>
                                    <span class="text-muted-foreground">
                                        {{ plan.billing_interval === 'yearly' ? '/Jahr' : '/Monat' }}
                                    </span>
                                </div>
                                <!-- Monatlicher Äquivalent bei jährlichen Plänen -->
                                <div v-if="plan.billing_interval === 'yearly'" class="text-sm text-muted-foreground">
                                    {{ Math.round(plan.price / 12) }}€/Monat
                                </div>
                                <!-- Ersparnis-Badge bei jährlichen Plänen -->
                                <div v-if="getYearlySavings(plan)" class="flex justify-center">
                                    <Badge variant="secondary" class="text-xs">
                                        Spare {{ getYearlySavings(plan) }}€/Monat
                                    </Badge>
                                </div>
                            </div>
                            <p v-if="plan.slug === 'free'" class="mt-1 text-xs text-muted-foreground">
                                30 Tage testen
                            </p>
                        </div>
                    </CardHeader>

                    <CardContent class="flex-1">
                        <!-- Features List -->
                        <ul class="space-y-3">
                            <li
                                v-for="(feature, index) in plan.features"
                                :key="index"
                                class="flex items-start gap-2"
                            >
                                <Check class="mt-0.5 h-5 w-5 flex-shrink-0 text-primary" />
                                <span class="text-sm">{{ feature }}</span>
                            </li>
                        </ul>
                    </CardContent>

                    <CardFooter>
                        <!-- Current Plan -->
                        <Button
                            v-if="isCurrentPlan(plan)"
                            variant="outline"
                            class="w-full"
                            disabled
                        >
                            Aktueller Plan
                        </Button>

                        <!-- Free Plan -->
                        <Link
                            v-else-if="plan.price === 0"
                            :href="`/subscription/checkout/${plan.id}`"
                            class="w-full"
                        >
                            <Button variant="outline" class="w-full">
                                Kostenlos starten
                            </Button>
                        </Link>

                        <!-- Paid Plans -->
                        <Link
                            v-else
                            :href="`/subscription/checkout/${plan.id}`"
                            class="w-full"
                        >
                            <Button
                                :variant="plan.is_popular ? 'default' : 'outline'"
                                class="w-full"
                            >
                                Jetzt starten
                            </Button>
                        </Link>
                    </CardFooter>
                </Card>
            </div>

            <!-- Current Plan Info -->
            <div v-if="currentPlan" class="mx-auto max-w-2xl rounded-lg border bg-muted/50 p-6 text-center">
                <p class="text-sm text-muted-foreground">
                    Du nutzt aktuell den <strong>{{ currentPlan.name }}</strong> Plan.
                    <Link href="/subscription/manage" class="text-primary hover:underline">
                        Subscription verwalten
                    </Link>
                </p>
            </div>

            <!-- Simple Info -->
            <div class="mx-auto max-w-3xl text-center">
                <p class="text-sm text-muted-foreground">
                    Alle Pläne können jederzeit gekündigt werden. Keine versteckten Kosten.
                    Zahlung per Kreditkarte über Stripe.
                </p>
            </div>
        </div>
    </AppLayout>
</template>
