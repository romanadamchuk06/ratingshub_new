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
import { Button } from '@/components/ui/button';
import { Star, TrendingUp, MessageSquare, Award, Link2, ArrowRight, AlertTriangle } from 'lucide-vue-next';

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
        <div class="space-y-6 p-4 md:p-6 lg:p-8">
            <!-- Header -->
            <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                <div>
                    <h1 class="text-2xl font-bold tracking-tight md:text-3xl">Dashboard</h1>
                    <p class="text-muted-foreground">
                        Überblick über deine Bewertungen und Statistiken
                    </p>
                </div>

                <!-- Location Selector (nur anzeigen wenn Plattformen verbunden) -->
                <LocationSelector
                    v-if="hasPlatformConnected && connectedPlatforms.length > 0"
                    :locations="connectedPlatforms"
                    :selected-ids="selectedLocationIds"
                />
            </div>

            <!-- Stats Grid -->
            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <StatsCard
                    title="Gesamtbewertungen"
                    :value="hasPlatformConnected ? stats.totalReviews.toString() : '-'"
                    :icon="Star"
                    :loading="loadingStats"
                    tooltip="Anzahl aller Bewertungen von verbundenen Plattformen"
                />
                <StatsCard
                    title="Durchschnitt"
                    :value="hasPlatformConnected && stats.averageRating ? `${stats.averageRating} ⭐` : '-'"
                    :icon="Award"
                    :loading="loadingStats"
                    tooltip="Durchschnittliche Bewertung (1-5 Sterne) aller Reviews"
                />
                <StatsCard
                    title="Neue diese Woche"
                    :value="hasPlatformConnected ? stats.newThisWeek.toString() : '-'"
                    :icon="TrendingUp"
                    :loading="loadingStats"
                    tooltip="Bewertungen, die in den letzten 7 Tagen abgegeben wurden"
                />
                <StatsCard
                    title="Zu beantworten"
                    :value="hasPlatformConnected ? stats.pendingReviews.toString() : '-'"
                    :icon="MessageSquare"
                    :loading="loadingStats"
                    tooltip="Anzahl unbeantworteter Bewertungen (Status: Ausstehend)"
                />
            </div>

            <!-- Reviews mit Problemen - Handlungsbedarf! -->
            <div
                v-if="hasPlatformConnected && problemReviews.length > 0"
                class="rounded-xl border border-red-200 bg-red-50 dark:border-red-900/30 dark:bg-red-950/20"
            >
                <div class="border-b border-red-200 dark:border-red-900/30 p-6 flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-red-100 dark:bg-red-900/30">
                            <AlertTriangle class="h-5 w-5 text-red-600 dark:text-red-400" />
                        </div>
                        <div>
                            <h2 class="text-lg font-semibold text-red-900 dark:text-red-100">
                                Handlungsbedarf: Reviews mit Problemen
                            </h2>
                            <p class="text-sm text-red-700 dark:text-red-300">
                                {{ stats.reviewsWithProblems }} {{ stats.reviewsWithProblems === 1 ? 'Bewertung enthält' : 'Bewertungen enthalten' }} negative Punkte
                            </p>
                        </div>
                    </div>
                </div>
                <div class="p-6">
                    <div class="space-y-4">
                        <div
                            v-for="review in problemReviews"
                            :key="review.id"
                            class="rounded-lg border border-red-200 dark:border-red-900/30 bg-white dark:bg-gray-900 p-4"
                        >
                            <!-- Review Header -->
                            <div class="flex items-start justify-between gap-4 mb-3">
                                <div class="flex-1">
                                    <div class="flex items-center gap-2 mb-1">
                                        <span class="font-medium text-gray-900 dark:text-white">
                                            {{ review.reviewer_name }}
                                        </span>
                                        <span class="text-sm text-gray-500">·</span>
                                        <div class="flex items-center">
                                            <Star
                                                v-for="n in 5"
                                                :key="n"
                                                class="h-4 w-4"
                                                :class="n <= review.rating ? 'fill-yellow-400 text-yellow-400' : 'fill-gray-200 text-gray-200 dark:fill-gray-700 dark:text-gray-700'"
                                            />
                                        </div>
                                    </div>
                                    <p class="text-sm text-gray-600 dark:text-gray-400 line-clamp-2">
                                        {{ review.text }}
                                    </p>
                                </div>
                            </div>

                            <!-- Problem-Kategorien (nur negative Sentiments) mit Beschreibung -->
                            <div class="mt-3 pt-3 border-t border-red-100 dark:border-red-900/30">
                                <div class="flex flex-wrap gap-2 mb-2">
                                    <span class="text-xs font-medium text-red-700 dark:text-red-300 mr-2">
                                        Probleme:
                                    </span>
                                    <SentimentTag
                                        v-for="sentiment in review.sentiments"
                                        :key="sentiment.id"
                                        :sentiment="sentiment"
                                        size="sm"
                                    />
                                </div>
                                <!-- Problem-Beschreibungen (Text-Ausschnitte) -->
                                <div
                                    v-if="review.sentiments.some(s => s.excerpt)"
                                    class="space-y-1 text-xs text-red-700 dark:text-red-300 pl-4"
                                >
                                    <div
                                        v-for="sentiment in review.sentiments.filter(s => s.excerpt)"
                                        :key="sentiment.id"
                                        class="flex items-start gap-2"
                                    >
                                        <span class="opacity-50">→</span>
                                        <span class="italic">
                                            <strong>{{ categoryNames[sentiment.category] || sentiment.category }}:</strong>
                                            "{{ sentiment.excerpt }}"
                                        </span>
                                    </div>
                                </div>
                            </div>

                            <!-- Action Button -->
                            <div class="mt-4 pt-4 border-t border-red-100 dark:border-red-900/30">
                                <Link :href="`/reviews?problems=true&highlight=${review.id}#review-${review.id}`">
                                    <Button variant="outline" size="sm" class="w-full sm:w-auto">
                                        Review ansehen & reagieren
                                        <ArrowRight class="ml-2 h-4 w-4" />
                                    </Button>
                                </Link>
                            </div>
                        </div>

                        <!-- Alle Problem-Reviews anzeigen -->
                        <div v-if="stats.reviewsWithProblems > problemReviews.length" class="pt-2 text-center">
                            <Link href="/reviews?problems=true">
                                <Button variant="outline" size="sm">
                                    Alle {{ stats.reviewsWithProblems }} Problem-Reviews anzeigen
                                    <ArrowRight class="ml-2 h-4 w-4" />
                                </Button>
                            </Link>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Main Content Area -->
            <div class="rounded-xl border bg-card">
                <div class="border-b p-6 flex items-center justify-between">
                    <h2 class="text-lg font-semibold">Neueste Bewertungen</h2>
                    <Link
                        v-if="hasPlatformConnected && recentReviews.length > 0"
                        href="/reviews"
                        class="text-sm text-primary hover:underline flex items-center gap-1"
                    >
                        Alle anzeigen
                        <ArrowRight class="h-4 w-4" />
                    </Link>
                </div>
                <div class="p-6">
                    <!-- Keine Plattform verbunden -->
                    <EmptyState
                        v-if="!hasPlatformConnected"
                        :icon="Link2"
                        title="Keine Plattform verbunden"
                        description="Verbinde zuerst eine Plattform wie Google My Business, um deine Bewertungen zu sehen."
                        actionText="Plattform verbinden"
                        actionHref="/settings/platforms"
                    />
                    <!-- Plattform verbunden, aber keine Reviews -->
                    <EmptyState
                        v-else-if="recentReviews.length === 0"
                        :icon="Star"
                        title="Noch keine Bewertungen"
                        description="Synchronisiere deine Bewertungen, um sie hier zu sehen."
                        actionText="Jetzt synchronisieren"
                        actionHref="/reviews"
                    />
                    <!-- Reviews Liste -->
                    <div v-else class="space-y-4">
                        <ReviewCard
                            v-for="review in recentReviews"
                            :key="review.id"
                            :review="review"
                        />
                        <div v-if="stats.totalReviews > 5" class="pt-4 border-t text-center">
                            <Link href="/reviews">
                                <Button variant="outline">
                                    Alle {{ stats.totalReviews }} Bewertungen anzeigen
                                    <ArrowRight class="ml-2 h-4 w-4" />
                                </Button>
                            </Link>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
