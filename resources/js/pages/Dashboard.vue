<script setup>
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, usePage, Link } from '@inertiajs/vue3';
import { ref, onMounted, computed } from 'vue';
import ConnectPlatformModal from '../components/ConnectPlatformModal.vue';
import StatsCard from '../components/StatsCard.vue';
import EmptyState from '../components/EmptyState.vue';
import LocationSelector from '../components/LocationSelector.vue';
import ReviewCard from '../components/ReviewCard.vue';
import { Button } from '@/components/ui/button';
import { Star, TrendingUp, MessageSquare, Award, Link2, ArrowRight } from 'lucide-vue-next';

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
        }),
    },
    recentReviews: {
        type: Array,
        default: () => [],
    },
});

const page = usePage();
const hasPlatformConnected = computed(() => page.props.auth.hasPlatformConnected);

const showModal = ref(false);
const loadingStats = ref(true);

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
