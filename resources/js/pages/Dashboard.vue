<script setup>
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, usePage, Link } from '@inertiajs/vue3';
import { ref, onMounted, computed } from 'vue';
import ConnectPlatformModal from '../components/ConnectPlatformModal.vue';
import StatsCard from '../components/StatsCard.vue';
import EmptyState from '../components/EmptyState.vue';
import LocationSelector from '../components/LocationSelector.vue';
import ReviewCard from '../components/ReviewCard.vue';
import SentimentTag from '../components/SentimentTag.vue';
import RatingTrendChart from '../components/charts/RatingTrendChart.vue';
import RatingDistributionChart from '../components/charts/RatingDistributionChart.vue';
import { Button } from '@/components/ui/button';
import { Star, TrendingUp, MessageSquare, Award, Link2, ArrowRight, AlertTriangle, BarChart3 } from 'lucide-vue-next';

const props = defineProps({
    connectedPlatforms: {
        type: Array,
        default: () => [],
    },
    selectedLocationIds: {
        type: Array,
        default: () => [],
    },
    stats: {
        type: Object,
        default: () => ({
            totalReviews: 0,
            averageRating: null,
            newThisWeek: 0,
            pendingReviews: 0,
            reviewsWithProblems: 0,
        }),
    },
    recentReviews: {
        type: Array,
        default: () => [],
    },
    problemReviews: {
        type: Array,
        default: () => [],
    },
    chartData: {
        type: Object,
        default: () => ({
            ratingTrend: {
                labels: [],
                values: []
            },
            ratingDistribution: {}
        }),
    },
});

const page = usePage();
const hasPlatformConnected = computed(() => page.props.auth.hasPlatformConnected);

const showModal = ref(false);
const loadingStats = ref(true);

// Kategorie-Namen für Problem-Beschreibungen
const categoryNames = {
    service: 'Service',
    quality: 'Qualität',
    price: 'Preis-Leistung',
    friendliness: 'Freundlichkeit',
    speed: 'Schnelligkeit',
    communication: 'Kommunikation',
    reliability: 'Zuverlässigkeit',
    cleanliness: 'Sauberkeit',
    competence: 'Kompetenz',
    atmosphere: 'Atmosphäre',
    accessibility: 'Erreichbarkeit',
    recommendation: 'Weiterempfehlung',
};

// Show modal on mount if no platforms connected and not dismissed
onMounted(() => {
    const dismissed = sessionStorage.getItem('platformModalDismissed');
    if (!hasPlatformConnected.value && !dismissed) {
        showModal.value = true;
    }

    // Simulate loading for better UX (zeigt Skeleton-Animation kurz an)
    // Nur beim ersten Laden, nicht bei jedem Navigation
    if (hasPlatformConnected.value) {
        setTimeout(() => {
            loadingStats.value = false;
        }, 400);
    } else {
        // Wenn keine Plattform verbunden, kein Loading nötig
        loadingStats.value = false;
    }
});

const closeModal = () => {
    showModal.value = false;
    sessionStorage.setItem('platformModalDismissed', 'true');
};
</script>

<template>
    <Head title="Dashboard" />

    <!-- Connect Platform Modal -->
    <ConnectPlatformModal :show="showModal" @close="closeModal" />

    <AppLayout>
        <div class="max-w-7xl mx-auto p-6 space-y-8">
            <!-- Header - Einfach und klar -->
            <div>
                <h1 class="text-3xl font-bold">Dashboard</h1>
                <p class="text-muted-foreground mt-1">Übersicht deiner Bewertungen</p>
            </div>

            <!-- Keine Plattform verbunden - Große Anzeige -->
            <EmptyState
                v-if="!hasPlatformConnected"
                :icon="Link2"
                title="Keine Plattform verbunden"
                description="Verbinde Google My Business, um loszulegen."
                actionText="Jetzt verbinden"
                actionHref="/settings/platforms"
            />

            <!-- Hauptinhalt - Nur wenn verbunden -->
            <template v-else>
                <!-- Statistiken - 2x2 Grid statt 1x4 -->
                <div class="grid gap-6 sm:grid-cols-2">
                    <div class="bg-card rounded-xl border p-6">
                        <div class="flex items-center gap-3">
                            <div class="p-3 rounded-lg bg-primary/10">
                                <Star class="h-6 w-6 text-primary" />
                            </div>
                            <div>
                                <p class="text-sm text-muted-foreground">Bewertungen</p>
                                <p class="text-3xl font-bold">{{ stats.totalReviews }}</p>
                            </div>
                        </div>
                    </div>

                    <div class="bg-card rounded-xl border p-6">
                        <div class="flex items-center gap-3">
                            <div class="p-3 rounded-lg bg-green-500/10">
                                <Award class="h-6 w-6 text-green-600" />
                            </div>
                            <div>
                                <p class="text-sm text-muted-foreground">Durchschnitt</p>
                                <p class="text-3xl font-bold">{{ stats.averageRating || '-' }} ⭐</p>
                            </div>
                        </div>
                    </div>

                    <div class="bg-card rounded-xl border p-6">
                        <div class="flex items-center gap-3">
                            <div class="p-3 rounded-lg bg-blue-500/10">
                                <TrendingUp class="h-6 w-6 text-blue-600" />
                            </div>
                            <div>
                                <p class="text-sm text-muted-foreground">Diese Woche</p>
                                <p class="text-3xl font-bold">{{ stats.newThisWeek }}</p>
                            </div>
                        </div>
                    </div>

                    <div class="bg-card rounded-xl border p-6">
                        <div class="flex items-center gap-3">
                            <div class="p-3 rounded-lg bg-orange-500/10">
                                <MessageSquare class="h-6 w-6 text-orange-600" />
                            </div>
                            <div>
                                <p class="text-sm text-muted-foreground">Zu beantworten</p>
                                <p class="text-3xl font-bold">{{ stats.pendingReviews }}</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Probleme - Hervorgehoben wenn vorhanden -->
                <div
                    v-if="problemReviews.length > 0"
                    class="bg-red-50 dark:bg-red-950/20 border-2 border-red-200 dark:border-red-900 rounded-xl p-6"
                >
                    <div class="flex items-center justify-between mb-4">
                        <div class="flex items-center gap-3">
                            <AlertTriangle class="h-6 w-6 text-red-600" />
                            <div>
                                <h2 class="text-lg font-bold text-red-900 dark:text-red-100">Wichtig!</h2>
                                <p class="text-sm text-red-700 dark:text-red-300">
                                    {{ stats.reviewsWithProblems }} Bewertungen brauchen deine Aufmerksamkeit
                                </p>
                            </div>
                        </div>
                        <Link href="/reviews?problems=true">
                            <Button variant="destructive" size="sm">
                                Ansehen
                            </Button>
                        </Link>
                    </div>
                </div>

                <!-- Grafiken - Nebeneinander -->
                <div v-if="stats.totalReviews > 0" class="grid gap-6 lg:grid-cols-2">
                    <div class="bg-card rounded-xl border p-6">
                        <h3 class="font-semibold mb-4">Verlauf (30 Tage)</h3>
                        <RatingTrendChart :data="chartData.ratingTrend" />
                    </div>

                    <div class="bg-card rounded-xl border p-6">
                        <h3 class="font-semibold mb-4">Verteilung</h3>
                        <RatingDistributionChart :data="chartData.ratingDistribution" />
                    </div>
                </div>

                <!-- Neueste Reviews - Klar und einfach -->
                <div class="bg-card rounded-xl border">
                    <div class="p-6 border-b flex items-center justify-between">
                        <h2 class="text-lg font-semibold">Neueste Bewertungen</h2>
                        <Link href="/reviews" class="text-sm text-primary hover:underline">
                            Alle ansehen →
                        </Link>
                    </div>
                    <div class="p-6">
                        <EmptyState
                            v-if="recentReviews.length === 0"
                            :icon="Star"
                            title="Noch keine Bewertungen"
                            description="Synchronisiere deine Bewertungen."
                            actionText="Jetzt synchronisieren"
                            actionHref="/reviews"
                        />
                        <div v-else class="space-y-4">
                            <ReviewCard
                                v-for="review in recentReviews.slice(0, 3)"
                                :key="review.id"
                                :review="review"
                            />
                        </div>
                    </div>
                </div>
            </template>
        </div>
    </AppLayout>
</template>
