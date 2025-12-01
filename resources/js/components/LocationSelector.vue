<script setup>
/**
 * LOCATION SELECTOR (MULTI-SELECT)
 * ==================================
 *
 * Wiederverwendbare Komponente für Standort-Auswahl.
 *
 * Features:
 * - Multi-Select mit Checkboxen
 * - "Alle auswählen" Option
 * - Zeigt Anzahl ausgewählter Standorte
 * - Speichert Auswahl in URL (filter persistence)
 *
 * Verwendung:
 * -----------
 * <LocationSelector
 *   :locations="connectedPlatforms"
 *   @change="handleLocationChange"
 * />
 *
 * WARUM?
 * - User haben oft mehrere Standorte (z.B. Filialen)
 * - Bewertungen sollen pro Standort filterbar sein
 * - Dashboard-Stats sollen pro Standort angezeigt werden
 */

import { ref, computed, watch } from 'vue';
import { router } from '@inertiajs/vue3';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuTrigger,
    DropdownMenuSeparator,
} from '@/components/ui/dropdown-menu';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { MapPin, ChevronDown, Check } from 'lucide-vue-next';

const props = defineProps({
    /**
     * Liste der verbundenen Plattformen/Standorte
     * Format: [{ id: 1, provider: 'google', metadata: { name: 'Standort München' } }]
     */
    locations: {
        type: Array,
        required: true,
    },

    /**
     * Vorausgewählte Location IDs (z.B. aus URL)
     */
    selectedIds: {
        type: Array,
        default: () => [],
    },

    /**
     * Auto-Update URL beim Ändern?
     */
    autoUpdate: {
        type: Boolean,
        default: true,
    },
});

const emit = defineEmits(['change']);

// Aktuell ausgewählte Location IDs
const selected = ref([...props.selectedIds]);

// Wenn keine Vorauswahl, wähle alle
if (selected.value.length === 0 && props.locations.length > 0) {
    selected.value = props.locations.map(loc => loc.id);
}

/**
 * Sind alle Locations ausgewählt?
 */
const allSelected = computed(() => {
    return selected.value.length === props.locations.length;
});

/**
 * Label für den Button
 */
const buttonLabel = computed(() => {
    if (selected.value.length === 0) {
        return 'Keine Standorte';
    }
    if (allSelected.value) {
        return `Alle Standorte (${props.locations.length})`;
    }
    return `${selected.value.length} ${selected.value.length === 1 ? 'Standort' : 'Standorte'}`;
});

/**
 * Standort-Name extrahieren
 */
const getLocationName = (location) => {
    // Versuche aus metadata.name
    if (location.metadata?.name) {
        return location.metadata.name;
    }
    // Fallback: Provider + ID
    return `${location.provider} (${location.id})`;
};

/**
 * Toggle "Alle auswählen"
 */
const toggleAll = () => {
    if (allSelected.value) {
        // Alle abwählen
        selected.value = [];
    } else {
        // Alle auswählen
        selected.value = props.locations.map(loc => loc.id);
    }
    handleChange();
};

/**
 * Toggle einzelne Location
 */
const toggleLocation = (locationId) => {
    const index = selected.value.indexOf(locationId);
    if (index > -1) {
        selected.value.splice(index, 1);
    } else {
        selected.value.push(locationId);
    }
    handleChange();
};

/**
 * Check ob Location ausgewählt ist
 * HINWEIS: Wir verwenden jetzt selected.includes() direkt im Template
 * für bessere Reaktivität
 */

/**
 * Änderung behandeln
 */
const handleChange = () => {
    emit('change', selected.value);

    // Auto-Update URL?
    if (props.autoUpdate) {
        updateUrl();
    }
};

/**
 * URL mit Auswahl aktualisieren
 */
const updateUrl = () => {
    const currentUrl = new URL(window.location.href);
    const params = new URLSearchParams(currentUrl.search);

    if (selected.value.length === 0 || allSelected.value) {
        // Keine oder alle: Parameter entfernen
        params.delete('locations');
    } else {
        // Spezifische Auswahl: Als Komma-separierte Liste
        params.set('locations', selected.value.join(','));
    }

    // Navigate mit preserveState & preserveScroll
    router.get(
        currentUrl.pathname + '?' + params.toString(),
        {},
        {
            preserveState: true,
            preserveScroll: true,
            only: ['stats', 'reviews'], // Nur relevante Props neu laden
        }
    );
};

// Watch für externe Änderungen (z.B. URL-Change)
watch(() => props.selectedIds, (newIds) => {
    if (newIds.length > 0) {
        selected.value = [...newIds];
    }
});
</script>

<template>
    <DropdownMenu>
        <DropdownMenuTrigger as-child>
            <Button variant="outline" class="min-w-[200px] justify-between">
                <div class="flex items-center gap-2">
                    <MapPin class="h-4 w-4" />
                    <span>{{ buttonLabel }}</span>
                </div>
                <ChevronDown class="ml-2 h-4 w-4 opacity-50" />
            </Button>
        </DropdownMenuTrigger>
        <DropdownMenuContent class="w-64 p-0" align="start">
            <!-- Alle auswählen -->
            <!--
                WICHTIG:
                - Verwenden eigene visuelle Checkbox (kein pointer-events Problem)
                - @click.stop verhindert dass Dropdown schließt
            -->
            <div
                class="flex cursor-pointer items-center gap-3 px-3 py-2.5 hover:bg-accent"
                @click.stop="toggleAll"
            >
                <!-- Visuelle Checkbox (kein reka-ui Component) -->
                <div
                    class="size-4 shrink-0 rounded-[4px] border border-input shadow-xs flex items-center justify-center transition-colors"
                    :class="allSelected ? 'bg-primary border-primary text-primary-foreground' : ''"
                >
                    <Check v-if="allSelected" class="size-3.5" />
                </div>
                <span class="flex-1 font-medium">Alle Standorte</span>
                <Badge v-if="locations.length > 0" variant="secondary">
                    {{ locations.length }}
                </Badge>
            </div>

            <DropdownMenuSeparator v-if="locations.length > 0" />

            <!-- Einzelne Locations -->
            <div class="max-h-64 overflow-y-auto">
                <div
                    v-for="location in locations"
                    :key="location.id"
                    class="flex cursor-pointer items-center gap-3 px-3 py-2.5 hover:bg-accent"
                    @click.stop="toggleLocation(location.id)"
                >
                    <!-- Visuelle Checkbox (einfaches div, kein Component) -->
                    <div
                        class="size-4 shrink-0 rounded-[4px] border border-input shadow-xs flex items-center justify-center transition-colors"
                        :class="selected.includes(location.id) ? 'bg-primary border-primary text-primary-foreground' : ''"
                    >
                        <Check v-if="selected.includes(location.id)" class="size-3.5" />
                    </div>
                    <div class="flex flex-col gap-0.5">
                        <span class="text-sm font-medium">{{ getLocationName(location) }}</span>
                        <span class="text-xs text-muted-foreground capitalize">
                            {{ location.provider.replace('_', ' ') }}
                        </span>
                    </div>
                </div>
            </div>

            <!-- Empty State -->
            <div
                v-if="locations.length === 0"
                class="px-3 py-6 text-center text-sm text-muted-foreground"
            >
                Keine Standorte verbunden
            </div>
        </DropdownMenuContent>
    </DropdownMenu>
</template>
