<script setup>
/**
 * Reviews Page
 *
 * Zeigt alle Reviews des Users von verschiedenen Plattformen an
 *
 * Features:
 * - Reviews filtern nach Plattform (via LocationSelector)
 * - Reviews filtern nach Status (pending, responded, archived)
 * - Reviews filtern nach Rating (1-5 Sterne)
 * - Reviews synchronisieren (von API abrufen)
 * - Auf Reviews antworten
 * - Review-Status ändern
 */

import AppLayout from '@/layouts/AppLayout.vue';
import { Head, usePage, router } from '@inertiajs/vue3';
import { computed, ref, onMounted } from 'vue';
import EmptyState from '../components/EmptyState.vue';
import LocationSelector from '../components/LocationSelector.vue';
import ReviewCard from '../components/ReviewCard.vue';
import ReviewCardSkeleton from '../components/ReviewCardSkeleton.vue';
import SimpleTooltip from '@/components/ui/tooltip/SimpleTooltip.vue';
import { Button } from '@/components/ui/button';
import { useToast } from '@/composables/useToast';
import { Star, Link2, RefreshCw } from 'lucide-vue-next';

const { toast } = useToast();

const page = usePage();
const hasPlatformConnected = computed(() => page.props.auth.hasPlatformConnected);

// Flash Messages vom Backend lesen
const flashSuccess = computed(() => page.props.flash?.success);
const flashError = computed(() => page.props.flash?.error);
const flashInfo = computed(() => page.props.flash?.info);

const props = defineProps({
    reviews: {
        type: Object, // Pagination object (data, links, meta)
        default: () => ({ data: [] }),
    },
    connectedPlatforms: {
        type: Array,
        default: () => [],
    },
    selectedLocationIds: {
        type: Array,
        default: () => [],
    },
    filters: {
        type: Object,
        default: () => ({}),
    },
});

// State für Sync-Button
const syncing = ref(false);

// State für Loading (wird true während Navigation/Filter-Änderungen)
const loading = ref(true); // Startet als true für Initial-Loading

// Location-Auswahl ist jetzt im LocationSelector integriert
// Keine separate Warning-Box mehr nötig

/**
 * Initial Loading State
 * Zeigt kurz Skeleton-Animation beim ersten Laden für bessere UX
 */
onMounted(() => {
    if (hasPlatformConnected.value && props.reviews.data?.length > 0) {
        // Kurzes Loading für Skeleton-Animation (nur wenn Reviews vorhanden)
        setTimeout(() => {
            loading.value = false;
        }, 600);
    } else {
        // Kein Loading wenn keine Plattform verbunden oder keine Reviews
        loading.value = false;
    }
});

/**
 * Synchronisiert Reviews von der API
 * (Ruft alle verbundenen Plattformen ab)
 */
const syncReviews = () => {
    if (props.connectedPlatforms.length === 0) {
        toast.warning(
            'Keine Plattformen verbunden',
            'Bitte verbinde zuerst eine Plattform unter Einstellungen → Plattformen.'
        );
        return;
    }

    syncing.value = true;
    loading.value = true; // Zeige Skeleton während Sync

    // Info Toast dass Sync startet
    toast.info('Synchronisierung gestartet', 'Bewertungen werden von Google abgerufen...', 3000);

    // Für jede verbundene Plattform einen Sync-Request senden
    router.post(
        '/reviews/sync',
        {
            connected_platform_id: props.connectedPlatforms[0].id, // Erste Plattform syncen
        },
        {
            preserveState: false, // Reload damit wir neue Reviews sehen
            preserveScroll: true,
            onSuccess: (page) => {
                // Success-Message vom Backend nutzen (falls vorhanden)
                const newCount = page.props.flash?.success?.match(/\d+/)?.[0] || 0;
                if (newCount > 0) {
                    toast.success('Synchronisierung erfolgreich!', `${newCount} neue Bewertungen wurden importiert.`);
                } else {
                    toast.success('Synchronisierung erfolgreich!', 'Keine neuen Bewertungen gefunden.');
                }
            },
            onError: (errors) => {
                const errorMessage = errors.message || 'Die Synchronisierung ist fehlgeschlagen.';
                toast.error('Synchronisierung fehlgeschlagen', errorMessage);
            },
            onFinish: () => {
                syncing.value = false;
            },
        }
    );
};

/**
 * Zeigt die aktuelle Flash Message an (mit Auto-Hide nach 5 Sekunden)
 */
const currentFlashMessage = computed(() => {
    if (flashSuccess.value) return { text: flashSuccess.value, type: 'success' };
    if (flashError.value) return { text: flashError.value, type: 'error' };
    if (flashInfo.value) return { text: flashInfo.value, type: 'info' };
    return null;
});

// Filter functions will be added later when Select component is available
</script>

<template>
    <Head title="Bewertungen" />

    <AppLayout>
        <div class="space-y-6 p-4 md:p-6 lg:p-8">
            <!-- Header -->
            <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                <div>
                    <h1 class="text-2xl font-bold tracking-tight md:text-3xl">Bewertungen</h1>
                    <p class="text-muted-foreground">
                        Verwalte und beantworte deine Bewertungen
                    </p>
                </div>

                <!-- Location Selector + Sync Button -->
                <div class="flex items-center gap-3">
                    <LocationSelector
                        v-if="hasPlatformConnected && connectedPlatforms.length > 0"
                        :locations="connectedPlatforms"
                        :selected-ids="selectedLocationIds"
                    />
                    <SimpleTooltip
                        v-if="hasPlatformConnected && connectedPlatforms.length > 0"
                        text="Neue Bewertungen von Google My Business abrufen"
                    >
                        <Button
                            @click="syncReviews"
                            :disabled="syncing"
                            variant="outline"
                            size="sm"
                        >
                            <RefreshCw :class="['mr-2 h-4 w-4', syncing && 'animate-spin']" />
                            {{ syncing ? 'Synchronisiere...' : 'Synchronisieren' }}
                        </Button>
                    </SimpleTooltip>
                </div>
            </div>

            <!-- Flash Message (Success, Error, Info) -->
            <div
                v-if="currentFlashMessage"
                :class="[
                    'p-4 rounded-lg border animate-in slide-in-from-top-2 transition-all',
                    currentFlashMessage.type === 'success'
                        ? 'bg-green-50 dark:bg-green-950 border-green-200 dark:border-green-800 text-green-800 dark:text-green-200'
                        : currentFlashMessage.type === 'error'
                        ? 'bg-red-50 dark:bg-red-950 border-red-200 dark:border-red-800 text-red-800 dark:text-red-200'
                        : 'bg-blue-50 dark:bg-blue-950 border-blue-200 dark:border-blue-800 text-blue-800 dark:text-blue-200',
                ]"
            >
                <div class="flex items-center justify-between gap-3">
                    <span class="text-sm font-medium">{{ currentFlashMessage.text }}</span>
                </div>
            </div>

            <!-- Filter Bar (temporarily disabled - Select component will be added later) -->
            <div
                v-if="false && hasPlatformConnected && (reviews.data?.length > 0 || filters.status || filters.rating)"
                class="flex flex-wrap items-center gap-3 p-4 rounded-lg border bg-card"
            >
                <div class="flex items-center gap-2">
                    <span class="text-sm font-medium">Filter:</span>
                </div>
                <!-- Filters will be added when Select component is available -->
            </div>

            <!-- Reviews List -->
            <div class="rounded-xl border bg-card">
                <div class="border-b p-6">
                    <div class="flex items-center justify-between">
                        <h2 class="text-lg font-semibold">Alle Bewertungen</h2>
                        <span
                            v-if="hasPlatformConnected && reviews.data?.length > 0"
                            class="text-sm text-muted-foreground"
                        >
                            {{ reviews.total || reviews.data.length }}
                            {{ reviews.total === 1 ? 'Bewertung' : 'Bewertungen' }}
                        </span>
                    </div>
                </div>
                <div class="p-6">
                    <!-- Empty States -->
                    <EmptyState
                        v-if="!hasPlatformConnected"
                        :icon="Link2"
                        title="Keine Plattform verbunden"
                        description="Verbinde zuerst eine Plattform wie Google My Business, um deine Bewertungen zu sehen."
                        actionText="Plattform verbinden"
                        actionHref="/settings/platforms"
                    />
                    <EmptyState
                        v-else-if="!reviews.data || reviews.data.length === 0"
                        :icon="Star"
                        title="Noch keine Bewertungen"
                        description="Sobald du Bewertungen erhältst, werden sie hier angezeigt und du kannst darauf antworten."
                    />

                    <!-- Loading Skeletons -->
                    <div v-else-if="loading" class="space-y-4">
                        <ReviewCardSkeleton v-for="i in 3" :key="i" />
                    </div>

                    <!-- Reviews Grid -->
                    <div v-else class="space-y-4">
                        <ReviewCard
                            v-for="review in reviews.data"
                            :key="review.id"
                            :review="review"
                        />
                    </div>

                    <!-- Pagination -->
                    <div
                        v-if="reviews.data?.length > 0 && reviews.links"
                        class="mt-6 flex items-center justify-center gap-2"
                    >
                        <Button
                            v-for="link in reviews.links"
                            :key="link.label"
                            @click="router.get(link.url)"
                            :disabled="!link.url || link.active"
                            :variant="link.active ? 'default' : 'outline'"
                            size="sm"
                            v-html="link.label"
                        />
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
