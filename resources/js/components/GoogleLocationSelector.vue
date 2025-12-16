<script setup>
/**
 * Google Location Selector Component
 *
 * Zeigt ein Dropdown mit allen verfügbaren Google My Business Locations
 * User wählt die Location aus, für die Reviews synchronisiert werden sollen
 *
 * Flow:
 * 1. Component lädt → API-Call zu /platforms/{id}/locations
 * 2. Google API liefert alle Accounts & Locations des Users
 * 3. User wählt Location aus Dropdown
 * 4. POST zu /platforms/{id}/select-location
 * 5. Location wird in metadata gespeichert
 * 6. User kann jetzt Reviews synchronisieren
 */

import { ref, onMounted, computed } from 'vue';
import { router } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import {
    Select,
    SelectContent,
    SelectGroup,
    SelectItem,
    SelectLabel,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { MapPin, CheckCircle, Loader2 } from 'lucide-vue-next';

const props = defineProps({
    platform: {
        type: Object,
        required: true,
    },
});

// State
const locations = ref([]);
const selectedLocation = ref(null);
const loading = ref(false);
const saving = ref(false);
const error = ref(null);

/**
 * Prüft ob Location bereits ausgewählt wurde
 */
const hasSelectedLocation = computed(() => {
    return props.platform.metadata?.location_name !== undefined;
});

/**
 * Display Name der aktuell ausgewählten Location
 */
const currentLocationName = computed(() => {
    return props.platform.metadata?.location_display_name || 'Unbekannte Location';
});

/**
 * Lädt verfügbare Locations von der API
 */
const loadLocations = async () => {
    loading.value = true;
    error.value = null;

    try {
        const response = await fetch(`/platforms/${props.platform.id}/locations`);

        if (!response.ok) {
            throw new Error('Fehler beim Laden der Locations');
        }

        const data = await response.json();
        locations.value = data;

        // Falls bereits eine Location ausgewählt wurde, setze sie als selected
        if (hasSelectedLocation.value) {
            const current = locations.value.find(
                (loc) => loc.location_name === props.platform.metadata.location_name
            );
            if (current) {
                selectedLocation.value = current.location_name;
            }
        }
    } catch (e) {
        error.value = e.message;
        console.error('Fehler beim Laden der Locations:', e);
    } finally {
        loading.value = false;
    }
};

/**
 * Speichert die ausgewählte Location
 */
const saveLocation = async () => {
    if (!selectedLocation.value) {
        return;
    }

    saving.value = true;
    error.value = null;

    try {
        const location = locations.value.find((loc) => loc.location_name === selectedLocation.value);

        const response = await fetch(`/platforms/${props.platform.id}/select-location`, {
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

        // Reload page um neue metadata zu bekommen
        router.reload({
            only: ['platform'],
        });
    } catch (e) {
        error.value = e.message;
        console.error('Fehler beim Speichern der Location:', e);
    } finally {
        saving.value = false;
    }
};

/**
 * Ermöglicht Änderung der Location
 */
const changeLocation = () => {
    selectedLocation.value = null;
    loadLocations();
};

// Load locations on mount
onMounted(() => {
    if (!hasSelectedLocation.value) {
        loadLocations();
    }
});
</script>

<template>
    <div class="space-y-4">
        <!-- Bereits ausgewählte Location -->
        <div v-if="hasSelectedLocation && !selectedLocation" class="flex items-center gap-3 p-4 rounded-lg border bg-card">
            <div class="flex items-center gap-3 flex-1">
                <div class="h-10 w-10 rounded-full bg-green-100 dark:bg-green-900 flex items-center justify-center">
                    <CheckCircle class="h-5 w-5 text-green-600 dark:text-green-400" />
                </div>
                <div>
                    <p class="text-sm font-medium">Ausgewählter Standort:</p>
                    <p class="text-lg font-semibold">{{ currentLocationName }}</p>
                </div>
            </div>
            <Button @click="changeLocation" variant="outline" size="sm">
                Ändern
            </Button>
        </div>

        <!-- Location Auswahl -->
        <div v-else class="space-y-4">
            <!-- Titel -->
            <div class="flex items-center gap-2">
                <MapPin class="h-5 w-5 text-primary" />
                <h3 class="text-lg font-semibold">Wähle deinen Standort aus</h3>
            </div>

            <!-- Beschreibung -->
            <p class="text-sm text-muted-foreground">
                Wähle den Google My Business Standort aus, für den du Bewertungen verwalten möchtest.
            </p>

            <!-- Loading State -->
            <div v-if="loading" class="flex items-center gap-2 p-4 rounded-lg border">
                <Loader2 class="h-4 w-4 animate-spin" />
                <span class="text-sm">Lade verfügbare Standorte...</span>
            </div>

            <!-- Error State -->
            <div v-else-if="error" class="p-4 rounded-lg border border-red-200 dark:border-red-800 bg-red-50 dark:bg-red-950">
                <p class="text-sm text-red-800 dark:text-red-200">{{ error }}</p>
                <Button @click="loadLocations" variant="outline" size="sm" class="mt-3">
                    Erneut versuchen
                </Button>
            </div>

            <!-- Location Dropdown -->
            <div v-else-if="locations.length > 0" class="space-y-3">
                <Select v-model="selectedLocation">
                    <SelectTrigger class="w-full">
                        <SelectValue placeholder="Standort auswählen..." />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectGroup>
                            <SelectLabel>Verfügbare Standorte</SelectLabel>
                            <SelectItem
                                v-for="location in locations"
                                :key="location.location_name"
                                :value="location.location_name"
                            >
                                <div class="flex flex-col">
                                    <span class="font-medium">{{ location.location_display_name }}</span>
                                    <span v-if="location.address" class="text-xs text-muted-foreground">
                                        {{ location.address.addressLines?.join(', ') || 'Keine Adresse' }}
                                    </span>
                                </div>
                            </SelectItem>
                        </SelectGroup>
                    </SelectContent>
                </Select>

                <!-- Save Button -->
                <Button
                    @click="saveLocation"
                    :disabled="!selectedLocation || saving"
                    class="w-full"
                >
                    <Loader2 v-if="saving" class="mr-2 h-4 w-4 animate-spin" />
                    <MapPin v-else class="mr-2 h-4 w-4" />
                    {{ saving ? 'Wird gespeichert...' : 'Standort speichern' }}
                </Button>
            </div>

            <!-- No Locations Found -->
            <div v-else class="p-4 rounded-lg border border-yellow-200 dark:border-yellow-800 bg-yellow-50 dark:bg-yellow-950">
                <p class="text-sm text-yellow-800 dark:text-yellow-200">
                    Keine Google My Business Standorte gefunden.
                    Stelle sicher, dass du ein Google My Business Profil hast und Owner/Manager bist.
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
    </div>
</template>
