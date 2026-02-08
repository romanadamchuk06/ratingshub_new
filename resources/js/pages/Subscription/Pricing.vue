<script setup>
/**
 * STRIPE PRICING TABLE
 * ====================
 *
 * Verwendet Stripe Pricing Table für die Plan-Auswahl.
 * Stripe handhabt automatisch:
 * - Monatlich/Jährlich Toggle
 * - Checkout
 * - Zahlungsabwicklung
 *
 * DARK/LIGHT MODE:
 * - Zwei separate Pricing Tables in Stripe (eine für Light, eine für Dark)
 * - Automatische Umschaltung basierend auf App Theme
 *
 * SETUP in Stripe Dashboard:
 * 1. Produkte → Pricing Tables → Zwei erstellen (Light + Dark)
 * 2. Light: Hintergrund #ffffff, Button #171717
 * 3. Dark: Hintergrund #0A0A0A, Button #FAFAFA
 * 4. Beide IDs in .env eintragen:
 *    - STRIPE_PRICING_TABLE_ID (Light)
 *    - STRIPE_PRICING_TABLE_ID_DARK (Dark)
 */

import AppLayout from '@/layouts/AppLayout.vue';
import { Head, Link, usePage } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import { Alert, AlertDescription } from '@/components/ui/alert';
import { AlertCircle } from 'lucide-vue-next';
import { onMounted, onUnmounted, ref, computed } from 'vue';

// Flash Messages aus Session (z.B. "Abo nicht mehr aktiv")
const page = usePage();
const flashError = computed(() => page.props.flash?.error);
const flashSuccess = computed(() => page.props.flash?.success);

const props = defineProps({
    // Light Mode Pricing Table ID
    pricingTableId: {
        type: String,
        required: true,
    },
    // Dark Mode Pricing Table ID
    pricingTableIdDark: {
        type: String,
        default: null,
    },
    publishableKey: {
        type: String,
        required: true,
    },
    currentPlan: {
        type: Object,
        default: null,
    },
    customerSessionClientSecret: {
        type: String,
        default: null,
    },
});

const isLoading = ref(true);

// Dark Mode Erkennung
const isDarkMode = ref(false);

const updateDarkMode = () => {
    isDarkMode.value = document.documentElement.classList.contains('dark');
};

// Aktive Pricing Table ID basierend auf Theme
// Wenn Dark Mode aktiv UND Dark Table vorhanden → Dark Table, sonst Light Table
const activePricingTableId = computed(() => {
    if (isDarkMode.value && props.pricingTableIdDark) {
        return props.pricingTableIdDark;
    }
    return props.pricingTableId;
});

// MutationObserver um Theme-Wechsel zu erkennen
let observer = null;

onMounted(() => {
    updateDarkMode();

    // Observer für Theme-Änderungen
    observer = new MutationObserver((mutations) => {
        mutations.forEach((mutation) => {
            if (mutation.attributeName === 'class') {
                updateDarkMode();
            }
        });
    });

    observer.observe(document.documentElement, {
        attributes: true,
        attributeFilter: ['class'],
    });

    // Stripe Pricing Table Script laden
    const script = document.createElement('script');
    script.src = 'https://js.stripe.com/v3/pricing-table.js';
    script.async = true;
    script.onload = () => {
        isLoading.value = false;
    };
    document.head.appendChild(script);
});

onUnmounted(() => {
    if (observer) {
        observer.disconnect();
    }
});
</script>

<template>
    <Head title="Preise & Pläne" />

    <AppLayout :breadcrumbs="[
        { label: 'Subscription', href: '/subscription' }
    ]">
        <div class="space-y-8 p-4 md:p-6 lg:p-8">
            <!-- Flash Message: Abo nicht aktiv -->
            <Alert v-if="flashError" variant="destructive" class="mx-auto max-w-2xl">
                <AlertCircle class="h-4 w-4" />
                <AlertDescription>{{ flashError }}</AlertDescription>
            </Alert>

            <!-- Flash Message: Erfolg -->
            <Alert v-if="flashSuccess" class="mx-auto max-w-2xl border-green-500 bg-green-50 text-green-700 dark:bg-green-950 dark:text-green-300">
                <AlertDescription>{{ flashSuccess }}</AlertDescription>
            </Alert>

            <!-- Header -->
            <div class="text-center">
                <h1 class="mb-4 text-3xl font-bold tracking-tight md:text-4xl">
                    Einfache, transparente Preise
                </h1>
                <p class="mx-auto max-w-2xl text-lg text-muted-foreground">
                    Wähle den Plan, der zu dir passt. Jederzeit kündbar.
                </p>
            </div>

            <!-- Stripe Pricing Table -->
            <div class="mx-auto max-w-5xl">
                <!-- Loading State -->
                <div v-if="isLoading" class="flex items-center justify-center py-20">
                    <div class="h-8 w-8 animate-spin rounded-full border-4 border-primary border-t-transparent"></div>
                </div>

                <!-- Stripe Pricing Table Component -->
                <!-- Wechselt automatisch zwischen Light/Dark Table basierend auf App Theme -->
                <stripe-pricing-table
                    v-show="!isLoading"
                    :key="activePricingTableId"
                    :pricing-table-id="activePricingTableId"
                    :publishable-key="publishableKey"
                    :customer-session-client-secret="customerSessionClientSecret"
                />
            </div>

            <!-- Current Plan Info -->
            <div v-if="currentPlan" class="mx-auto max-w-2xl rounded-lg border bg-muted/50 p-6 text-center">
                <p class="text-sm text-muted-foreground">
                    Du nutzt aktuell den <strong>{{ currentPlan.name }}</strong> Plan.
                    <a href="/subscription/billing-portal" class="text-primary hover:underline">
                        Subscription verwalten
                    </a>
                </p>
            </div>

            <!-- Info -->
            <div class="mx-auto max-w-3xl text-center">
                <p class="text-sm text-muted-foreground">
                    Alle Pläne können jederzeit gekündigt werden. Keine versteckten Kosten.
                    Sichere Zahlung über Stripe.
                </p>
            </div>
        </div>
    </AppLayout>
</template>
