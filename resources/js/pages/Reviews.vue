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
import { computed, ref, onMounted, nextTick } from 'vue';
import EmptyState from '../components/EmptyState.vue';
import LocationSelector from '../components/LocationSelector.vue';
import ReviewCard from '../components/ReviewCard.vue';
import ReviewCardSkeleton from '../components/ReviewCardSkeleton.vue';
import SimpleTooltip from '@/components/ui/tooltip/SimpleTooltip.vue';
import { Button } from '@/components/ui/button';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { useToast } from '@/composables/useToast';
import { Star, Link2, RefreshCw, X } from 'lucide-vue-next';

const { toast } = useToast();

const page = usePage();
const hasPlatformConnected = computed(() => page.props.auth.hasPlatformConnected);

// Highlight-Funktion: Zeigt einen bestimmten Review hervorgehoben an
const highlightedReviewId = ref(null);

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

// Filter State
const selectedStatus = ref(props.filters?.status || null);
const selectedRating = ref(props.filters?.rating || null);
const showOnlyProblems = ref(props.filters?.problems === 'true' || props.filters?.problems === '1');

// Location-Auswahl ist jetzt im LocationSelector integriert
// Keine separate Warning-Box mehr nötig

/**
 * Filter anwenden - navigiert zur gefilterten Seite
 */
const applyFilters = () => {
    const params = new URLSearchParams();

    // Location-Filter (vom LocationSelector)
    if (props.selectedLocationIds.length > 0) {
        params.append('locations', props.selectedLocationIds.join(','));
    }

    // Status-Filter
    if (selectedStatus.value) {
        params.append('status', selectedStatus.value);
    }

    // Rating-Filter
    if (selectedRating.value) {
        params.append('rating', selectedRating.value);
    }

    // Problem-Filter
    if (showOnlyProblems.value) {
        params.append('problems', 'true');
    }

    // Highlight-Parameter beibehalten (falls vorhanden)
    const currentParams = new URLSearchParams(window.location.search);
    const highlightId = currentParams.get('highlight');
    if (highlightId) {
        params.append('highlight', highlightId);
    }

    // Navigate mit Filtern (Hash beibehalten)
    const queryString = params.toString();
    const hash = window.location.hash;
    router.get((queryString ? `/reviews?${queryString}` : '/reviews') + hash);
};

/**
 * Einzelnen Filter zurücksetzen
 */
const clearFilter = (filterName) => {
    if (filterName === 'status') selectedStatus.value = null;
    if (filterName === 'rating') selectedRating.value = null;
    if (filterName === 'problems') showOnlyProblems.value = false;
    applyFilters();
};

/**
 * Alle Filter zurücksetzen
 */
const clearAllFilters = () => {
    selectedStatus.value = null;
    selectedRating.value = null;
    showOnlyProblems.value = false;
    router.get('/reviews');
};

/**
 * Scrollt zu einem bestimmten Review und hebt ihn hervor
 * Wird verwendet wenn User von Dashboard auf einen Problem-Review klickt
 */
const scrollToReview = (reviewId) => {
    nextTick(() => {
        const element = document.getElementById(`review-${reviewId}`);
        if (element) {
            // Scroll mit etwas Offset für bessere Sichtbarkeit
            const yOffset = -100;
            const y = element.getBoundingClientRect().top + window.pageYOffset + yOffset;
            window.scrollTo({ top: y, behavior: 'smooth' });

            // Highlight-Effekt anzeigen
            highlightedReviewId.value = reviewId;

            // Info-Toast dass wir zum Review gescrollt haben
            toast.info('Review gefunden', 'Wir haben dich zum gewünschten Review gebracht.', 3000);

            // Highlight nach 5 Sekunden entfernen
            setTimeout(() => {
                highlightedReviewId.value = null;
            }, 5000);
        }
    });
};

/**
 * Initial Loading State
 * Zeigt kurz Skeleton-Animation beim ersten Laden für bessere UX
 * Prüft auch ob ein Review hervorgehoben werden soll (via Query-Parameter oder Hash-Anker)
 */
onMounted(() => {
    // Prüfe ob ein Review hervorgehoben werden soll
    // Priorität 1: Query-Parameter (?highlight=457)
    const urlParams = new URLSearchParams(window.location.search);
    let highlightId = urlParams.get('highlight');

    // Priorität 2: Hash-Anker (#review-457)
    if (!highlightId) {
        const hash = window.location.hash; // z.B. "#review-457"
        const reviewIdMatch = hash.match(/#review-(\d+)/);
        highlightId = reviewIdMatch ? reviewIdMatch[1] : null;
    }

    if (hasPlatformConnected.value && props.reviews.data?.length > 0) {
        // Kurzes Loading für Skeleton-Animation (nur wenn Reviews vorhanden)
        setTimeout(() => {
            loading.value = false;

            // Nach dem Laden zum Review scrollen (falls highlight vorhanden)
            if (highlightId) {
                setTimeout(() => {
                    scrollToReview(parseInt(highlightId));
                }, 300); // Kurze Verzögerung damit Skeleton-Animation fertig ist
            }
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

            <!-- Filter Bar -->
            <div
                v-if="hasPlatformConnected"
                class="flex flex-wrap items-center gap-3 p-4 rounded-lg border bg-card"
            >
                <span class="text-sm font-medium mr-2">Filter:</span>

                <!-- Problem-Reviews Toggle -->
                <Button
                    @click="() => { showOnlyProblems = !showOnlyProblems; applyFilters(); }"
                    :variant="showOnlyProblems ? 'default' : 'outline'"
                    size="sm"
                >
                    🚨 Nur Problem-Reviews
                </Button>

                <!-- Status Filter -->
                <Select :model-value="selectedStatus" @update:model-value="(val) => { selectedStatus = val; applyFilters(); }">
                    <SelectTrigger class="w-[180px]">
                        <SelectValue placeholder="Status auswählen" />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem value="pending">Ausstehend</SelectItem>
                        <SelectItem value="responded">Beantwortet</SelectItem>
                        <SelectItem value="archived">Archiviert</SelectItem>
                    </SelectContent>
                </Select>

                <!-- Rating Filter -->
                <Select :model-value="selectedRating" @update:model-value="(val) => { selectedRating = val; applyFilters(); }">
                    <SelectTrigger class="w-[180px]">
                        <SelectValue placeholder="Bewertung" />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem value="5">⭐⭐⭐⭐⭐ (5 Sterne)</SelectItem>
                        <SelectItem value="4">⭐⭐⭐⭐ (4 Sterne)</SelectItem>
                        <SelectItem value="3">⭐⭐⭐ (3 Sterne)</SelectItem>
                        <SelectItem value="2">⭐⭐ (2 Sterne)</SelectItem>
                        <SelectItem value="1">⭐ (1 Stern)</SelectItem>
                    </SelectContent>
                </Select>

                <!-- Filter zurücksetzen Button (nur anzeigen wenn Filter aktiv) -->
                <Button
                    v-if="selectedStatus || selectedRating || showOnlyProblems"
                    @click="clearAllFilters"
                    variant="ghost"
                    size="sm"
                >
                    <X class="mr-2 h-4 w-4" />
                    Alle Filter zurücksetzen
                </Button>
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
                        <div
                            v-for="review in reviews.data"
                            :key="review.id"
                            :id="`review-${review.id}`"
                        >
                            <ReviewCard
                                :review="review"
                                :highlighted="highlightedReviewId === review.id"
                            />
                        </div>
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
