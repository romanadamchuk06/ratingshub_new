<script setup>
/**
 * LOCATION SELECTOR
 * =================
 *
 * Zeigt Google My Business Locations zum Auswählen an.
 *
 * Features:
 * - Lädt verfügbare Locations von verbundenen Google-Plattformen
 * - Single-Select: User wählt EINE Location aus
 * - Speichert Auswahl in Platform.metadata
 *
 * Flow:
 * 1. Component mounted → Lade Locations via API
 * 2. User wählt Location
 * 3. POST zu /platforms/{id}/select-location
 * 4. Location wird in metadata gespeichert
 */

import { ref, computed, watch, onMounted } from 'vue';
import { router } from '@inertiajs/vue3';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuTrigger,
    DropdownMenuSeparator,
} from '@/components/ui/dropdown-menu';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { MapPin, ChevronDown, Check, Loader2, AlertCircle } from 'lucide-vue-next';

const props = defineProps({
    /**
     * Liste der verbundenen Plattformen
     * Format: [{ id: 1, provider: 'google', metadata: { location_name: '...', location_display_name: '...' } }]
     */
    locations: {
        type: Array,
        required: true,
    },
});

// State
const availableLocations = ref([]); // Alle verfügbaren Google Locations
const loading = ref(false);
const error = ref(null);
const saving = ref(false);

/**
 * Findet die erste Google-Plattform ohne ausgewählte Location
 * oder die erste Google-Plattform generell
 */
const googlePlatform = computed(() => {
    return props.locations.find(loc => loc.provider === 'google');
});

/**
 * Hat die Plattform bereits eine Location ausgewählt?
 */
const hasSelectedLocation = computed(() => {
    return googlePlatform.value?.metadata?.location_name !== undefined;
});

/**
 * Display Name der ausgewählten Location
 */
const selectedLocationName = computed(() => {
    if (!hasSelectedLocation.value) {
        return 'Standort auswählen';
    }
    return googlePlatform.value.metadata.location_display_name || 'Unbekannte Location';
});

/**
 * Button Label
 */
const buttonLabel = computed(() => {
    if (loading.value) {
        return 'Lade...';
    }
    if (error.value) {
        return 'Fehler';
    }
    return selectedLocationName.value;
});

/**
 * Lädt verfügbare Google Locations
 */
const loadLocations = async () => {
    if (!googlePlatform.value) {
        return;
    }

    loading.value = true;
    error.value = null;

    try {
        const response = await fetch(`/platforms/${googlePlatform.value.id}/locations`);

        if (!response.ok) {
            throw new Error('Fehler beim Laden der Locations');
        }

        const data = await response.json();
        availableLocations.value = data;
    } catch (e) {
        error.value = e.message;
        console.error('Fehler beim Laden der Locations:', e);
    } finally {
        loading.value = false;
    }
};

/**
 * Wählt eine Location aus und speichert sie
 */
const selectLocation = async (location) => {
    if (!googlePlatform.value) {
        return;
    }

    saving.value = true;
    error.value = null;

    try {
        const response = await fetch(`/platforms/${googlePlatform.value.id}/select-location`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            },
            body: JSON.stringify({
                account_name: location.account_name,
                location_name: location.location_name,
                location_display_name: location.location_display_name,
            }),
        });

        if (!response.ok) {
            throw new Error('Fehler beim Speichern der Location');
        }

        // Seite neu laden um neue metadata zu bekommen
        router.reload({
            preserveScroll: true,
        });
    } catch (e) {
        error.value = e.message;
        console.error('Fehler beim Speichern der Location:', e);
    } finally {
        saving.value = false;
    }
};

// Load locations on mount (wenn noch keine ausgewählt)
onMounted(() => {
    if (googlePlatform.value && !hasSelectedLocation.value) {
        loadLocations();
    }
});
</script>

<template>
    <DropdownMenu>
        <DropdownMenuTrigger as-child>
            <Button
                variant="outline"
                class="min-w-[200px] justify-between"
                :disabled="!googlePlatform"
            >
                <div class="flex items-center gap-2">
                    <Loader2 v-if="loading || saving" class="h-4 w-4 animate-spin" />
                    <AlertCircle v-else-if="error" class="h-4 w-4 text-destructive" />
                    <MapPin v-else class="h-4 w-4" />
                    <span>{{ buttonLabel }}</span>
                </div>
                <ChevronDown class="ml-2 h-4 w-4 opacity-50" />
            </Button>
        </DropdownMenuTrigger>
        <DropdownMenuContent class="w-80 p-0" align="start">
            <!-- Loading State -->
            <div v-if="loading" class="flex items-center gap-2 px-3 py-6">
                <Loader2 class="h-4 w-4 animate-spin" />
                <span class="text-sm text-muted-foreground">Lade verfügbare Standorte...</span>
            </div>

            <!-- Error State -->
            <div v-else-if="error" class="px-3 py-4">
                <div class="flex items-center gap-2 text-destructive mb-2">
                    <AlertCircle class="h-4 w-4" />
                    <span class="text-sm font-medium">{{ error }}</span>
                </div>
                <Button @click="loadLocations" variant="outline" size="sm" class="w-full">
                    Erneut versuchen
                </Button>
            </div>

            <!-- Locations List -->
            <div v-else-if="availableLocations.length > 0" class="max-h-96 overflow-y-auto">
                <div
                    v-for="location in availableLocations"
                    :key="location.location_name"
                    class="flex cursor-pointer items-start gap-3 px-3 py-3 hover:bg-accent transition-colors"
                    :class="hasSelectedLocation && googlePlatform.metadata.location_name === location.location_name ? 'bg-accent' : ''"
                    @click="selectLocation(location)"
                >
                    <!-- Check Icon wenn ausgewählt -->
                    <div class="flex items-center justify-center w-5 h-5 mt-0.5">
                        <Check
                            v-if="hasSelectedLocation && googlePlatform.metadata.location_name === location.location_name"
                            class="h-4 w-4 text-primary"
                        />
                    </div>

                    <!-- Location Info -->
                    <div class="flex-1 min-w-0">
                        <div class="font-medium text-sm">{{ location.location_display_name }}</div>
                        <div v-if="location.address" class="text-xs text-muted-foreground mt-0.5">
                            {{ location.address.addressLines?.join(', ') || '' }}
                            {{ location.address.postalCode ? location.address.postalCode : '' }}
                            {{ location.address.locality ? location.address.locality : '' }}
                        </div>
                    </div>
                </div>
            </div>

            <!-- Empty State -->
            <div v-else-if="!hasSelectedLocation" class="px-3 py-6">
                <div class="text-center">
                    <MapPin class="h-8 w-8 text-muted-foreground mx-auto mb-2" />
                    <p class="text-sm text-muted-foreground">
                        Keine Google My Business Standorte gefunden.
                    </p>
                    <a
                        href="https://business.google.com"
                        target="_blank"
                        class="inline-block mt-3 text-sm font-medium text-primary hover:underline"
                    >
                        Google My Business öffnen →
                    </a>
                </div>
            </div>

            <!-- Bereits ausgewählt (wenn Dropdown geöffnet und Location gesetzt) -->
            <div v-else class="px-3 py-4">
                <div class="flex items-center gap-2 text-green-600 dark:text-green-400 mb-2">
                    <Check class="h-4 w-4" />
                    <span class="text-sm font-medium">Standort ausgewählt</span>
                </div>
                <Button @click="loadLocations" variant="outline" size="sm" class="w-full">
                    Anderen Standort wählen
                </Button>
            </div>
        </DropdownMenuContent>
    </DropdownMenu>
</template>
