<script setup>
/**
 * Landing Page für RatingsHub
 *
 * Eine moderne, professionelle Landing Page mit:
 * - Hero Section mit CTA
 * - Features Section
 * - Pricing Section (Stripe Pricing Table)
 * - Social Proof
 * - FAQ
 * - Footer
 */

import { Head, Link, router } from '@inertiajs/vue3';
import { ref, onMounted, onUnmounted, computed } from 'vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import AppLogo from '@/components/AppLogo.vue';
import {
    Star,
    MessageSquare,
    BarChart3,
    Zap,
    Shield,
    Globe,
    ArrowRight,
    ChevronDown,
    Award,
    TrendingUp,
    MapPin,
    Check,
    Lock
} from 'lucide-vue-next';

const props = defineProps({
    canRegister: Boolean,
    isAuthenticated: Boolean,
    plans: {
        type: Array,
        default: () => [],
    },
    // Stripe Pricing Table
    pricingTableId: String,
    pricingTableIdDark: String,
    stripePublishableKey: String,
});

// Stripe Pricing Table laden
const stripeLoaded = ref(false);
const showLoginOverlay = ref(false);

// Billing Interval Toggle (monthly/yearly)
const billingInterval = ref('monthly');
const isYearly = computed(() => billingInterval.value === 'yearly');

// Dark Mode Erkennung
const isDarkMode = ref(false);
const updateDarkMode = () => {
    isDarkMode.value = document.documentElement.classList.contains('dark');
};

// Aktive Pricing Table ID basierend auf Theme
const activePricingTableId = computed(() => {
    if (isDarkMode.value && props.pricingTableIdDark) {
        return props.pricingTableIdDark;
    }
    return props.pricingTableId;
});

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

    // Stripe Script laden
    if (props.pricingTableId) {
        const script = document.createElement('script');
        script.src = 'https://js.stripe.com/v3/pricing-table.js';
        script.async = true;
        script.onload = () => {
            stripeLoaded.value = true;
        };
        document.head.appendChild(script);
    }
});

onUnmounted(() => {
    if (observer) {
        observer.disconnect();
    }
});

// Klick auf Pricing Table abfangen wenn nicht eingeloggt
const handlePricingClick = () => {
    if (!props.isAuthenticated) {
        showLoginOverlay.value = true;
    }
};

// Zum Login mit Redirect
const goToLogin = () => {
    router.visit('/login?redirect=/subscription');
};

// Zum Registrieren mit Redirect
const goToRegister = () => {
    router.visit('/register?redirect=/subscription');
};

// Preis formatieren
const formatPrice = (price) => {
    if (!price || price == 0) return 'Kostenlos';
    return `${Number(price).toFixed(2).replace('.', ',')} €`;
};

// Plan auswählen - redirect zu Login wenn nicht eingeloggt
// Nach Login geht's direkt zum Stripe Checkout für diesen Plan
const selectPlan = (plan) => {
    console.log('selectPlan called:', plan, 'interval:', billingInterval.value);

    if (!plan.slug) {
        console.error('Plan hat keinen slug!', plan);
        // Fallback: zur allgemeinen Subscription-Seite
        router.visit('/subscription');
        return;
    }

    // Checkout URL mit Billing Interval
    const checkoutUrl = `/subscription/checkout/${plan.slug}?interval=${billingInterval.value}`;
    console.log('Redirect to:', checkoutUrl);

    if (props.isAuthenticated) {
        // Eingeloggt: direkt zum Checkout für diesen Plan
        router.visit(checkoutUrl);
    } else {
        // Nicht eingeloggt: zu Login mit Redirect zum Checkout
        router.visit(`/login?redirect=${encodeURIComponent(checkoutUrl)}`);
    }
};

// Features Liste
const features = [
    {
        icon: Star,
        title: 'Multi-Plattform Reviews',
        description: 'Verwalte Bewertungen von Google, Trustpilot und mehr - alles an einem Ort.'
    },
    {
        icon: MessageSquare,
        title: 'Schnelle Antworten',
        description: 'Antworte direkt auf Bewertungen aus dem Dashboard heraus.'
    },
    {
        icon: BarChart3,
        title: 'Detaillierte Analytics',
        description: 'Erhalte Einblicke in deine Bewertungs-Performance mit übersichtlichen Statistiken.'
    },
    {
        icon: Zap,
        title: 'Automatische Synchronisation',
        description: 'Reviews werden automatisch importiert und aktuell gehalten.'
    },
    {
        icon: Shield,
        title: 'Sicher & DSGVO-konform',
        description: 'Deine Daten sind sicher verschlüsselt und DSGVO-konform gespeichert.'
    },
    {
        icon: Globe,
        title: 'Multi-Location Support',
        description: 'Verwalte mehrere Standorte und Filialen zentral.'
    }
];

// Pricing Plans kommen jetzt als Prop aus der Datenbank

// FAQ
const faqs = [
    {
        question: 'Wie funktioniert die Review-Synchronisation?',
        answer: 'RatingsHub verbindet sich sicher mit deinen Plattformen (Google, Trustpilot, etc.) und synchronisiert automatisch alle neuen Reviews. Du kannst die Synchronisation auch manuell auslösen.'
    },
    {
        question: 'Kann ich mehrere Standorte verwalten?',
        answer: 'Ja! Je nach gewähltem Plan kannst du einen oder mehrere Standorte zentral verwalten. Der Professional Plan unterstützt bis zu 5 Standorte, Enterprise unbegrenzt.'
    },
    {
        question: 'Ist meine Daten sicher?',
        answer: 'Absolut! Alle Daten werden verschlüsselt übertragen und gespeichert. Wir sind vollständig DSGVO-konform und nutzen modernste Sicherheitsstandards.'
    },
    {
        question: 'Kann ich jederzeit kündigen?',
        answer: 'Ja, du kannst monatlich kündigen. Es gibt keine Mindestlaufzeit. Dein Abo läuft bis zum Ende des bezahlten Zeitraums.'
    },
    {
        question: 'Welche Plattformen werden unterstützt?',
        answer: 'Aktuell unterstützen wir Google My Business und Trustpilot. Weitere Plattformen wie Facebook und Yelp sind in Planung.'
    }
];

// Smooth Scroll
const scrollToSection = (id) => {
    document.getElementById(id)?.scrollIntoView({ behavior: 'smooth' });
};

// FAQ Toggle
const openFaq = ref(null);
const toggleFaq = (index) => {
    openFaq.value = openFaq.value === index ? null : index;
};
</script>

<template>
    <Head title="Bewertungen zentral verwalten" />

    <div class="min-h-screen bg-gradient-to-b from-background via-background to-muted/20">
        <!-- Navigation -->
        <nav class="sticky top-0 z-50 border-b bg-background/95 backdrop-blur supports-[backdrop-filter]:bg-background/60">
            <div class="container mx-auto px-4">
                <div class="flex h-16 items-center justify-between">
                    <!-- Logo -->
                    <Link href="/" class="flex items-center">
                        <AppLogo />
                    </Link>

                    <!-- Navigation Links -->
                    <div class="hidden md:flex items-center gap-8">
                        <button @click="scrollToSection('features')" class="text-sm font-medium hover:text-primary transition-colors">
                            Features
                        </button>
                        <button @click="scrollToSection('pricing')" class="text-sm font-medium hover:text-primary transition-colors">
                            Preise
                        </button>
                        <button @click="scrollToSection('faq')" class="text-sm font-medium hover:text-primary transition-colors">
                            FAQ
                        </button>
                    </div>

                    <!-- Auth Buttons -->
                    <div class="flex items-center gap-3">
                        <!-- Eingeloggt: Dashboard-Button -->
                        <template v-if="isAuthenticated">
                            <Link href="/dashboard">
                                <Button size="sm">
                                    Zum Dashboard
                                </Button>
                            </Link>
                        </template>
                        <!-- Nicht eingeloggt: Login & Register -->
                        <template v-else>
                            <Link href="/login">
                                <Button variant="ghost" size="sm">
                                    Anmelden
                                </Button>
                            </Link>
                            <Link v-if="canRegister" href="/register">
                                <Button size="sm">
                                    Kostenlos starten
                                </Button>
                            </Link>
                        </template>
                    </div>
                </div>
            </div>
        </nav>

        <!-- Hero Section -->
        <section class="container mx-auto px-4 py-20 md:py-32">
            <div class="grid lg:grid-cols-2 gap-12 items-center">
                <!-- Left: Text Content -->
                <div class="space-y-8">
                    <div class="inline-block">
                        <Badge variant="secondary" class="px-4 py-1.5">
                            <Zap class="mr-2 h-3 w-3" />
                            Neu: AI-gestützte Antworten
                        </Badge>
                    </div>

                    <h1 class="text-4xl md:text-5xl lg:text-6xl font-bold tracking-tight">
                        Alle deine<br />
                        <span class="text-primary">Bewertungen</span><br />
                        an einem Ort
                    </h1>

                    <p class="text-lg md:text-xl text-muted-foreground max-w-2xl">
                        Verwalte Bewertungen von Google, Trustpilot und mehr zentral.
                        Antworte schneller, behalte den Überblick und verbessere deine Online-Reputation.
                    </p>

                    <div class="flex flex-col sm:flex-row gap-4">
                        <Button @click="scrollToSection('pricing')" size="lg" class="w-full sm:w-auto">
                            Kostenlos testen
                            <ArrowRight class="ml-2 h-4 w-4" />
                        </Button>
                        <Button @click="scrollToSection('features')" size="lg" variant="outline" class="w-full sm:w-auto">
                            Features ansehen
                        </Button>
                    </div>

                    <!-- Social Proof -->
                    <div class="flex items-center gap-4 pt-4">
                        <div class="flex -space-x-2">
                            <div class="w-10 h-10 rounded-full bg-gradient-to-br from-blue-500 to-purple-500 ring-2 ring-background"></div>
                            <div class="w-10 h-10 rounded-full bg-gradient-to-br from-green-500 to-teal-500 ring-2 ring-background"></div>
                            <div class="w-10 h-10 rounded-full bg-gradient-to-br from-orange-500 to-red-500 ring-2 ring-background"></div>
                        </div>
                        <div class="text-sm">
                            <div class="font-semibold">Über 500+ Unternehmen</div>
                            <div class="text-muted-foreground">vertrauen auf RatingsHub</div>
                        </div>
                    </div>
                </div>

                <!-- Right: Visual/Screenshot - Dashboard Mockup (ohne Sidebar) -->
                <div class="relative">
                    <div class="relative rounded-2xl border bg-background shadow-2xl overflow-hidden">
                        <!-- Mock Dashboard Content (Full Width, keine Sidebar) -->
                        <div class="aspect-[16/10] bg-background flex flex-col">
                            <!-- Header -->
                            <div class="px-6 py-4 border-b bg-background">
                                <div class="flex items-center justify-between">
                                    <div>
                                        <h2 class="text-lg font-bold">Dashboard</h2>
                                        <p class="text-xs text-muted-foreground">Überblick über deine Bewertungen und Statistiken</p>
                                    </div>
                                    <!-- Location Selector Mockup -->
                                    <div class="flex items-center gap-2 text-xs px-3 py-1.5 border rounded-md bg-background">
                                        <MapPin class="h-3.5 w-3.5" />
                                        <span>Alle Standorte (1)</span>
                                    </div>
                                </div>
                            </div>

                            <!-- Content Area -->
                            <div class="p-6 space-y-4 flex-1 overflow-auto">
                                <!-- Stats Grid -->
                                <div class="grid grid-cols-4 gap-3">
                                    <!-- Stat Card 1: Gesamtbewertungen -->
                                    <div class="rounded-xl border bg-card p-4 shadow-sm">
                                        <div class="flex items-center justify-between">
                                            <div class="flex-1">
                                                <p class="text-xs font-medium text-muted-foreground">
                                                    Gesamtbewertungen
                                                </p>
                                                <h3 class="text-2xl font-bold mt-2">127</h3>
                                            </div>
                                            <div class="rounded-lg bg-primary/10 p-2">
                                                <Star class="h-5 w-5 text-primary" />
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Stat Card 2: Durchschnitt -->
                                    <div class="rounded-xl border bg-card p-4 shadow-sm">
                                        <div class="flex items-center justify-between">
                                            <div class="flex-1">
                                                <p class="text-xs font-medium text-muted-foreground">
                                                    Durchschnitt
                                                </p>
                                                <h3 class="text-2xl font-bold mt-2">4.8</h3>
                                            </div>
                                            <div class="rounded-lg bg-primary/10 p-2">
                                                <Award class="h-5 w-5 text-primary" />
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Stat Card 3: Neue diese Woche -->
                                    <div class="rounded-xl border bg-card p-4 shadow-sm">
                                        <div class="flex items-center justify-between">
                                            <div class="flex-1">
                                                <p class="text-xs font-medium text-muted-foreground">
                                                    Neue diese Woche
                                                </p>
                                                <div class="flex items-baseline gap-2 mt-2">
                                                    <h3 class="text-2xl font-bold">12</h3>
                                                    <span class="text-xs font-medium text-green-600 dark:text-green-400">+8%</span>
                                                </div>
                                            </div>
                                            <div class="rounded-lg bg-primary/10 p-2">
                                                <TrendingUp class="h-5 w-5 text-primary" />
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Stat Card 4: Zu beantworten -->
                                    <div class="rounded-xl border bg-card p-4 shadow-sm">
                                        <div class="flex items-center justify-between">
                                            <div class="flex-1">
                                                <p class="text-xs font-medium text-muted-foreground">
                                                    Zu beantworten
                                                </p>
                                                <h3 class="text-2xl font-bold mt-2">3</h3>
                                            </div>
                                            <div class="rounded-lg bg-primary/10 p-2">
                                                <MessageSquare class="h-5 w-5 text-primary" />
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Reviews Section mit Fake-Daten -->
                                <div class="rounded-xl border bg-card shadow-sm">
                                    <div class="border-b px-4 py-3">
                                        <h3 class="text-sm font-semibold">Neueste Bewertungen</h3>
                                    </div>
                                    <div class="divide-y">
                                        <!-- Review 1 -->
                                        <div class="p-4 hover:bg-muted/30 transition-colors">
                                            <div class="flex items-start gap-3">
                                                <div class="w-8 h-8 rounded-full bg-gradient-to-br from-blue-500 to-purple-500 flex items-center justify-center text-white text-xs font-bold shrink-0">
                                                    SK
                                                </div>
                                                <div class="flex-1 min-w-0">
                                                    <div class="flex items-start justify-between gap-2">
                                                        <div>
                                                            <p class="text-sm font-medium">Sarah Klein</p>
                                                            <div class="flex items-center gap-1 mt-0.5">
                                                                <Star class="h-3 w-3 fill-yellow-500 text-yellow-500" />
                                                                <Star class="h-3 w-3 fill-yellow-500 text-yellow-500" />
                                                                <Star class="h-3 w-3 fill-yellow-500 text-yellow-500" />
                                                                <Star class="h-3 w-3 fill-yellow-500 text-yellow-500" />
                                                                <Star class="h-3 w-3 fill-yellow-500 text-yellow-500" />
                                                            </div>
                                                        </div>
                                                        <span class="text-xs text-muted-foreground shrink-0">vor 2 Std.</span>
                                                    </div>
                                                    <p class="text-xs text-muted-foreground mt-2 line-clamp-2">
                                                        Hervorragender Service! Das Team war sehr freundlich und hat alle meine Fragen beantwortet.
                                                    </p>
                                                    <div class="flex items-center gap-2 mt-2">
                                                        <span class="text-xs px-2 py-0.5 rounded-full bg-primary/10 text-primary">Google</span>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Review 2 -->
                                        <div class="p-4 hover:bg-muted/30 transition-colors">
                                            <div class="flex items-start gap-3">
                                                <div class="w-8 h-8 rounded-full bg-gradient-to-br from-green-500 to-teal-500 flex items-center justify-center text-white text-xs font-bold shrink-0">
                                                    MH
                                                </div>
                                                <div class="flex-1 min-w-0">
                                                    <div class="flex items-start justify-between gap-2">
                                                        <div>
                                                            <p class="text-sm font-medium">Max Hoffmann</p>
                                                            <div class="flex items-center gap-1 mt-0.5">
                                                                <Star class="h-3 w-3 fill-yellow-500 text-yellow-500" />
                                                                <Star class="h-3 w-3 fill-yellow-500 text-yellow-500" />
                                                                <Star class="h-3 w-3 fill-yellow-500 text-yellow-500" />
                                                                <Star class="h-3 w-3 fill-yellow-500 text-yellow-500" />
                                                                <Star class="h-3 w-3 text-muted-foreground" />
                                                            </div>
                                                        </div>
                                                        <span class="text-xs text-muted-foreground shrink-0">vor 5 Std.</span>
                                                    </div>
                                                    <p class="text-xs text-muted-foreground mt-2 line-clamp-2">
                                                        Gute Qualität und faire Preise. Lieferung war pünktlich.
                                                    </p>
                                                    <div class="flex items-center gap-2 mt-2">
                                                        <span class="text-xs px-2 py-0.5 rounded-full bg-blue-500/10 text-blue-500">Trustpilot</span>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Review 3 -->
                                        <div class="p-4 hover:bg-muted/30 transition-colors">
                                            <div class="flex items-start gap-3">
                                                <div class="w-8 h-8 rounded-full bg-gradient-to-br from-orange-500 to-red-500 flex items-center justify-center text-white text-xs font-bold shrink-0">
                                                    LM
                                                </div>
                                                <div class="flex-1 min-w-0">
                                                    <div class="flex items-start justify-between gap-2">
                                                        <div>
                                                            <p class="text-sm font-medium">Lisa Müller</p>
                                                            <div class="flex items-center gap-1 mt-0.5">
                                                                <Star class="h-3 w-3 fill-yellow-500 text-yellow-500" />
                                                                <Star class="h-3 w-3 fill-yellow-500 text-yellow-500" />
                                                                <Star class="h-3 w-3 fill-yellow-500 text-yellow-500" />
                                                                <Star class="h-3 w-3 fill-yellow-500 text-yellow-500" />
                                                                <Star class="h-3 w-3 fill-yellow-500 text-yellow-500" />
                                                            </div>
                                                        </div>
                                                        <span class="text-xs text-muted-foreground shrink-0">gestern</span>
                                                    </div>
                                                    <p class="text-xs text-muted-foreground mt-2 line-clamp-2">
                                                        Kann ich nur weiterempfehlen! Sehr professionell und schnell.
                                                    </p>
                                                    <div class="flex items-center gap-2 mt-2">
                                                        <span class="text-xs px-2 py-0.5 rounded-full bg-primary/10 text-primary">Google</span>
                                                        <span class="text-xs px-2 py-0.5 rounded-full bg-green-500/10 text-green-600">Beantwortet</span>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- Decorative Elements -->
                    <div class="absolute -top-4 -right-4 w-72 h-72 bg-primary/20 rounded-full blur-3xl -z-10"></div>
                    <div class="absolute -bottom-8 -left-8 w-72 h-72 bg-secondary/20 rounded-full blur-3xl -z-10"></div>
                </div>
            </div>
        </section>

        <!-- Features Section -->
        <section id="features" class="container mx-auto px-4 py-20 md:py-32">
            <div class="text-center space-y-4 mb-16">
                <Badge variant="secondary">Features</Badge>
                <h2 class="text-3xl md:text-4xl font-bold">
                    Alles was du brauchst
                </h2>
                <p class="text-lg text-muted-foreground max-w-2xl mx-auto">
                    RatingsHub bietet alle Tools, um deine Online-Bewertungen effektiv zu managen
                </p>
            </div>

            <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-8">
                <Card v-for="feature in features" :key="feature.title" class="relative overflow-hidden group hover:shadow-lg transition-all">
                    <CardHeader>
                        <div class="w-12 h-12 rounded-lg bg-primary/10 flex items-center justify-center mb-4 group-hover:bg-primary/20 transition-colors">
                            <component :is="feature.icon" class="h-6 w-6 text-primary" />
                        </div>
                        <CardTitle class="text-xl">{{ feature.title }}</CardTitle>
                        <CardDescription class="text-base">
                            {{ feature.description }}
                        </CardDescription>
                    </CardHeader>
                    <!-- Subtle gradient on hover -->
                    <div class="absolute inset-0 bg-gradient-to-br from-primary/0 to-primary/0 group-hover:from-primary/5 group-hover:to-transparent transition-all -z-10"></div>
                </Card>
            </div>
        </section>

        <!-- Pricing Section mit Stripe Pricing Table -->
        <section id="pricing" class="container mx-auto px-4 py-20 md:py-32">
            <div class="text-center space-y-4 mb-16">
                <Badge variant="secondary">Preise</Badge>
                <h2 class="text-3xl md:text-4xl font-bold">
                    Transparent & Fair
                </h2>
                <p class="text-lg text-muted-foreground max-w-2xl mx-auto">
                    Wähle den Plan, der zu deinem Unternehmen passt. Jederzeit kündbar.
                </p>
            </div>

            <!-- Stripe Pricing Table für alle -->
            <div class="mx-auto max-w-5xl relative">

                <!-- Stripe Pricing Table -->
                <template v-if="pricingTableId">
                    <!-- Loading State -->
                    <div v-if="!stripeLoaded" class="flex items-center justify-center py-20">
                        <div class="h-8 w-8 animate-spin rounded-full border-4 border-primary border-t-transparent"></div>
                    </div>

                    <div class="relative">
                        <stripe-pricing-table
                            v-show="stripeLoaded"
                            :key="activePricingTableId"
                            :pricing-table-id="activePricingTableId"
                            :publishable-key="stripePublishableKey"
                        />

                        <!-- Overlay NUR auf Button-Bereich (untere 25%) wenn NICHT eingeloggt -->
                        <div
                            v-if="!isAuthenticated && stripeLoaded"
                            @click="handlePricingClick"
                            class="absolute bottom-0 left-0 right-0 z-10 cursor-pointer"
                            style="height: 80px; background: transparent;"
                        ></div>
                    </div>
                </template>

                <!-- Fallback: Custom Cards wenn keine Stripe Table ID -->
                <div v-else class="grid md:grid-cols-3 gap-8">
                    <Card
                        v-for="plan in plans"
                        :key="plan.id"
                        :class="[
                            'relative flex flex-col',
                            plan.is_popular ? 'border-primary shadow-lg scale-105' : ''
                        ]"
                    >
                        <div v-if="plan.is_popular" class="absolute -top-3 left-1/2 -translate-x-1/2">
                            <Badge class="bg-primary text-primary-foreground">Beliebt</Badge>
                        </div>
                        <CardHeader class="text-center pb-2">
                            <CardTitle class="text-xl">{{ plan.name }}</CardTitle>
                            <CardDescription>{{ plan.description }}</CardDescription>
                        </CardHeader>
                        <CardContent class="flex-1 flex flex-col">
                            <!-- Preis: Monatlich oder Jährlich -->
                            <div class="text-center mb-6">
                                <template v-if="isYearly && plan.price_yearly">
                                    <span class="text-4xl font-bold">{{ formatPrice(plan.price_yearly) }}</span>
                                    <span class="text-muted-foreground">/Jahr</span>
                                    <div class="text-sm text-green-600 dark:text-green-400 mt-1">
                                        Spare {{ Math.round((1 - plan.price_yearly / (plan.price * 12)) * 100) }}%
                                    </div>
                                </template>
                                <template v-else>
                                    <span class="text-4xl font-bold">{{ formatPrice(plan.price) }}</span>
                                    <span v-if="plan.price > 0" class="text-muted-foreground">/Monat</span>
                                </template>
                            </div>
                            <ul class="space-y-3 mb-6 flex-1">
                                <li class="flex items-start gap-2">
                                    <Check class="h-5 w-5 text-green-500 shrink-0 mt-0.5" />
                                    <span>{{ plan.max_platforms }} {{ plan.max_platforms === 1 ? 'Standort' : 'Standorte' }}</span>
                                </li>
                                <li v-for="feature in (plan.features || [])" :key="feature" class="flex items-start gap-2">
                                    <Check class="h-5 w-5 text-green-500 shrink-0 mt-0.5" />
                                    <span>{{ feature }}</span>
                                </li>
                            </ul>
                            <Button
                                @click="selectPlan(plan)"
                                :variant="plan.is_popular ? 'default' : 'outline'"
                                class="w-full"
                            >
                                {{ plan.price > 0 ? 'Plan wählen' : 'Kostenlos starten' }}
                                <ArrowRight class="ml-2 h-4 w-4" />
                            </Button>
                        </CardContent>
                    </Card>
                </div>
            </div>

            <p class="text-center text-sm text-muted-foreground mt-8">
                Alle Preise zzgl. MwSt. • Jederzeit kündbar
            </p>
        </section>

        <!-- Login Overlay Modal -->
        <Teleport to="body">
            <div
                v-if="showLoginOverlay"
                class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm"
                @click.self="showLoginOverlay = false"
            >
                <Card class="w-full max-w-md mx-4 shadow-2xl">
                    <CardHeader class="text-center">
                        <div class="mx-auto w-12 h-12 rounded-full bg-primary/10 flex items-center justify-center mb-4">
                            <Lock class="h-6 w-6 text-primary" />
                        </div>
                        <CardTitle class="text-xl">Anmeldung erforderlich</CardTitle>
                        <CardDescription>
                            Um einen Plan auszuwählen, melde dich bitte an oder erstelle ein kostenloses Konto.
                        </CardDescription>
                    </CardHeader>
                    <CardContent class="space-y-4">
                        <Button @click="goToLogin" class="w-full" size="lg">
                            Anmelden
                            <ArrowRight class="ml-2 h-4 w-4" />
                        </Button>
                        <Button @click="goToRegister" variant="outline" class="w-full" size="lg">
                            Kostenloses Konto erstellen
                        </Button>
                        <button
                            @click="showLoginOverlay = false"
                            class="w-full text-sm text-muted-foreground hover:text-foreground transition-colors"
                        >
                            Abbrechen
                        </button>
                    </CardContent>
                </Card>
            </div>
        </Teleport>

        <!-- FAQ Section -->
        <section id="faq" class="container mx-auto px-4 py-20 md:py-32">
            <div class="text-center space-y-4 mb-16">
                <Badge variant="secondary">FAQ</Badge>
                <h2 class="text-3xl md:text-4xl font-bold">
                    Häufig gestellte Fragen
                </h2>
            </div>

            <div class="max-w-3xl mx-auto space-y-4">
                <div
                    v-for="(faq, index) in faqs"
                    :key="index"
                    class="border rounded-lg overflow-hidden"
                >
                    <button
                        @click="toggleFaq(index)"
                        class="w-full flex items-center justify-between p-6 text-left hover:bg-muted/50 transition-colors"
                    >
                        <span class="font-semibold">{{ faq.question }}</span>
                        <ChevronDown
                            :class="[
                                'h-5 w-5 transition-transform',
                                openFaq === index ? 'rotate-180' : ''
                            ]"
                        />
                    </button>
                    <div
                        v-show="openFaq === index"
                        class="px-6 pb-6 text-muted-foreground"
                    >
                        {{ faq.answer }}
                    </div>
                </div>
            </div>
        </section>

        <!-- CTA Section -->
        <section class="container mx-auto px-4 py-20">
            <Card class="relative overflow-hidden bg-gradient-to-br from-primary/10 via-background to-secondary/10">
                <CardContent class="p-12 md:p-16 text-center">
                    <h2 class="text-3xl md:text-4xl font-bold mb-4">
                        Bereit durchzustarten?
                    </h2>
                    <p class="text-lg text-muted-foreground mb-8 max-w-2xl mx-auto">
                        Starte jetzt kostenlos und verbessere deine Online-Reputation
                    </p>
                    <div class="flex flex-col sm:flex-row gap-4 justify-center">
                        <Button @click="scrollToSection('pricing')" size="lg">
                            Tarife ansehen
                            <ArrowRight class="ml-2 h-4 w-4" />
                        </Button>
                    </div>
                </CardContent>
                <!-- Decorative gradient -->
                <div class="absolute -top-24 -right-24 w-96 h-96 bg-primary/20 rounded-full blur-3xl -z-10"></div>
            </Card>
        </section>

        <!-- Footer -->
        <footer class="border-t bg-muted/30">
            <div class="container mx-auto px-4 py-12">
                <div class="grid md:grid-cols-4 gap-8">
                    <!-- Brand -->
                    <div>
                        <div class="flex items-center gap-2 font-bold text-xl mb-4">
                            <Star class="h-6 w-6 text-primary" />
                            <span>RatingsHub</span>
                        </div>
                        <p class="text-sm text-muted-foreground">
                            Deine zentrale Plattform für Review-Management
                        </p>
                    </div>

                    <!-- Product -->
                    <div>
                        <h3 class="font-semibold mb-4">Produkt</h3>
                        <ul class="space-y-2 text-sm text-muted-foreground">
                            <li><button @click="scrollToSection('features')" class="hover:text-foreground transition-colors">Features</button></li>
                            <li><button @click="scrollToSection('pricing')" class="hover:text-foreground transition-colors">Preise</button></li>
                            <li><Link href="/login" class="hover:text-foreground transition-colors">Login</Link></li>
                            <li><Link href="/register" class="hover:text-foreground transition-colors">Registrieren</Link></li>
                        </ul>
                    </div>

                    <!-- Support -->
                    <div>
                        <h3 class="font-semibold mb-4">Support</h3>
                        <ul class="space-y-2 text-sm text-muted-foreground">
                            <li><button @click="scrollToSection('faq')" class="hover:text-foreground transition-colors">FAQ</button></li>
                            <li><Link href="/bug-reports/create" class="hover:text-foreground transition-colors">Bug melden</Link></li>
                            <li><a href="mailto:support@ratingshub.com" class="hover:text-foreground transition-colors">Kontakt</a></li>
                        </ul>
                    </div>

                    <!-- Legal -->
                    <div>
                        <h3 class="font-semibold mb-4">Rechtliches</h3>
                        <ul class="space-y-2 text-sm text-muted-foreground">
                            <li><Link href="/impressum" class="hover:text-foreground transition-colors">Impressum</Link></li>
                            <li><Link href="/datenschutz" class="hover:text-foreground transition-colors">Datenschutz</Link></li>
                            <li><Link href="/agb" class="hover:text-foreground transition-colors">AGB</Link></li>
                            <li><Link href="/widerruf" class="hover:text-foreground transition-colors">Widerrufsbelehrung</Link></li>
                        </ul>
                    </div>
                </div>

                <div class="border-t mt-12 pt-8 text-center text-sm text-muted-foreground">
                    <p>© {{ new Date().getFullYear() }} RatingsHub. Alle Rechte vorbehalten.</p>
                </div>
            </div>
        </footer>
    </div>
</template>
