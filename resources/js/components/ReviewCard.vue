<script setup>
/**
 * ReviewCard Component
 *
 * Zeigt einen einzelnen Review mit allen Details an:
 * - Rating (1-5 Sterne)
 * - Review-Text
 * - Reviewer-Info (Name, Foto)
 * - Plattform-Badge (Google, Trustpilot, etc.)
 * - Status-Badge (pending, responded, archived)
 * - Antwort-Funktionalität
 * - Status-Änderung
 */

import { ref, computed } from 'vue';
import { router } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent, CardFooter, CardHeader } from '@/components/ui/card';
import { Textarea } from '@/components/ui/textarea';
import { Select, SelectContent, SelectGroup, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { useToast } from '@/composables/useToast';
import SimpleTooltip from '@/components/ui/tooltip/SimpleTooltip.vue';
import {
    Star,
    MessageSquare,
    Archive,
    RotateCcw,
    Send,
    Calendar,
    MapPin,
    User as UserIcon,
    Sparkles,
    Loader2,
} from 'lucide-vue-next';

// Toast für Feedback
const { toast } = useToast();

const props = defineProps({
    review: {
        type: Object,
        required: true,
    },
});

// State für Antwort-Formular
const isReplying = ref(false);
const replyText = ref('');
const submitting = ref(false);

// State für AI-Generierung
const aiGenerating = ref(false);
const selectedStyle = ref('friendly');
const showStyleSelector = ref(false);

// Verfügbare AI-Stile
const aiStyles = {
    professional: { name: 'Professionell', description: 'Formell und geschäftsmäßig' },
    friendly: { name: 'Freundlich', description: 'Warm und persönlich' },
    concise: { name: 'Kurz & Knapp', description: 'Prägnant und auf den Punkt' },
    enthusiastic: { name: 'Enthusiastisch', description: 'Energiegeladen und positiv' },
    empathetic: { name: 'Empathisch', description: 'Verständnisvoll und mitfühlend' },
};

/**
 * Rating als Array von Sternen (für visuelle Darstellung)
 * Beispiel: rating = 4 → [true, true, true, true, false]
 */
const stars = computed(() => {
    return Array.from({ length: 5 }, (_, i) => i < props.review.rating);
});

/**
 * Plattform-Badge Farbe basierend auf Provider
 */
const platformBadgeVariant = computed(() => {
    const variants = {
        google: 'default',
        trustpilot: 'secondary',
        facebook: 'outline',
    };
    return variants[props.review.connected_platform?.provider] || 'outline';
});

/**
 * Status-Badge Farbe
 */
const statusBadgeVariant = computed(() => {
    const variants = {
        pending: 'destructive',
        responded: 'default',
        archived: 'secondary',
    };
    return variants[props.review.status] || 'outline';
});

/**
 * Status-Text auf Deutsch
 */
const statusText = computed(() => {
    const texts = {
        pending: 'Ausstehend',
        responded: 'Beantwortet',
        archived: 'Archiviert',
    };
    return texts[props.review.status] || props.review.status;
});

/**
 * Tooltip-Text für Status-Badge (erklärt was der Status bedeutet)
 */
const statusTooltip = computed(() => {
    const tooltips = {
        pending: 'Diese Bewertung wartet auf eine Antwort von dir',
        responded: 'Du hast bereits auf diese Bewertung geantwortet',
        archived: 'Diese Bewertung wurde archiviert und ist nicht mehr aktiv',
    };
    return tooltips[props.review.status] || '';
});

/**
 * Formatiert Datum auf Deutsch
 * Beispiel: "vor 2 Tagen" oder "15. Jan 2024"
 */
const formattedDate = computed(() => {
    const date = new Date(props.review.review_date);
    const now = new Date();
    const diffInDays = Math.floor((now - date) / (1000 * 60 * 60 * 24));

    if (diffInDays === 0) return 'Heute';
    if (diffInDays === 1) return 'Gestern';
    if (diffInDays < 7) return `vor ${diffInDays} Tagen`;

    // Andernfalls: "15. Jan 2024"
    return date.toLocaleDateString('de-DE', {
        day: 'numeric',
        month: 'short',
        year: 'numeric',
    });
});

/**
 * Sendet Antwort an Backend
 */
const submitReply = () => {
    if (!replyText.value.trim()) {
        toast.warning('Antwort ist leer', 'Bitte gib eine Antwort ein.');
        return;
    }

    submitting.value = true;

    router.post(
        `/reviews/${props.review.id}/respond`,
        {
            response_text: replyText.value,
        },
        {
            onSuccess: () => {
                // Reset Form
                replyText.value = '';
                isReplying.value = false;

                // Success Toast
                toast.success('Antwort gesendet!', 'Deine Antwort wurde erfolgreich veröffentlicht.');
            },
            onError: (errors) => {
                // Error Toast mit Details
                const errorMessage = errors.response_text || 'Die Antwort konnte nicht gesendet werden.';
                toast.error('Fehler beim Senden', errorMessage);
            },
            onFinish: () => {
                submitting.value = false;
            },
        }
    );
};

/**
 * Ändert Review-Status
 */
const updateStatus = (newStatus) => {
    router.patch(
        `/reviews/${props.review.id}/status`,
        {
            status: newStatus,
        },
        {
            onSuccess: () => {
                const statusLabels = {
                    pending: 'ausstehend',
                    responded: 'beantwortet',
                    archived: 'archiviert',
                };
                toast.success('Status geändert', `Review wurde als ${statusLabels[newStatus]} markiert.`);
            },
            onError: () => {
                toast.error('Fehler', 'Status konnte nicht geändert werden.');
            },
        }
    );
};

/**
 * Öffnet/Schließt Antwort-Formular
 */
const toggleReply = () => {
    isReplying.value = !isReplying.value;
};

/**
 * Generiert AI-Antwort mit ausgewähltem Stil
 */
const generateAIResponse = async () => {
    aiGenerating.value = true;

    try {
        const response = await fetch(`/reviews/${props.review.id}/ai-response`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            },
            body: JSON.stringify({
                style: selectedStyle.value,
                context: {
                    location_name: props.review.metadata?.location_name || '',
                },
            }),
        });

        const data = await response.json();

        if (data.success) {
            // Setze generierte Antwort in Textarea
            replyText.value = data.response;
            toast.success('AI-Antwort generiert!', 'Du kannst die Antwort jetzt bearbeiten oder direkt senden.');
        } else {
            toast.error('Fehler bei AI-Generierung', data.error || 'Unbekannter Fehler');
        }
    } catch (error) {
        console.error('AI Generation Error:', error);
        toast.error(
            'Fehler bei AI-Generierung',
            'Stelle sicher, dass OPENAI_API_KEY in .env konfiguriert ist.'
        );
    } finally {
        aiGenerating.value = false;
    }
};
</script>

<template>
    <Card class="hover:shadow-lg transition-shadow">
        <CardHeader class="space-y-4">
            <!-- Top Row: Platform Badge + Status Badge + Date -->
            <div class="flex items-center justify-between flex-wrap gap-2">
                <div class="flex items-center gap-2">
                    <!-- Platform Badge -->
                    <Badge :variant="platformBadgeVariant" class="capitalize">
                        <MapPin class="mr-1 h-3 w-3" />
                        {{ review.connected_platform?.provider || 'Plattform' }}
                    </Badge>

                    <!-- Status Badge with Tooltip -->
                    <SimpleTooltip :text="statusTooltip">
                        <Badge :variant="statusBadgeVariant" class="cursor-help">
                            {{ statusText }}
                        </Badge>
                    </SimpleTooltip>
                </div>

                <!-- Date -->
                <div class="flex items-center gap-1 text-sm text-muted-foreground">
                    <Calendar class="h-3 w-3" />
                    <span>{{ formattedDate }}</span>
                </div>
            </div>

            <!-- Star Rating -->
            <div class="flex items-center gap-1">
                <Star
                    v-for="(filled, index) in stars"
                    :key="index"
                    :class="[
                        'h-5 w-5',
                        filled
                            ? 'fill-yellow-400 text-yellow-400'
                            : 'text-gray-300 dark:text-gray-600',
                    ]"
                />
                <span class="ml-2 text-sm font-medium">{{ review.rating }}/5</span>
            </div>

            <!-- Reviewer Info -->
            <div class="flex items-center gap-3">
                <div
                    v-if="review.reviewer_photo_url"
                    class="h-10 w-10 rounded-full overflow-hidden bg-muted flex items-center justify-center"
                >
                    <img
                        :src="review.reviewer_photo_url"
                        :alt="review.reviewer_name"
                        class="h-full w-full object-cover"
                    />
                </div>
                <div
                    v-else
                    class="h-10 w-10 rounded-full bg-muted flex items-center justify-center"
                >
                    <UserIcon class="h-5 w-5 text-muted-foreground" />
                </div>
                <div>
                    <p class="font-medium">{{ review.reviewer_name }}</p>
                    <p
                        v-if="review.metadata?.location_name"
                        class="text-sm text-muted-foreground"
                    >
                        {{ review.metadata.location_name }}
                    </p>
                </div>
            </div>
        </CardHeader>

        <CardContent class="space-y-4">
            <!-- Review Text -->
            <div v-if="review.text" class="text-sm leading-relaxed">
                {{ review.text }}
            </div>
            <div v-else class="text-sm text-muted-foreground italic">
                Keine Bewertungstext vorhanden
            </div>

            <!-- Existing Responses -->
            <div
                v-if="review.responses && review.responses.length > 0"
                class="space-y-3 mt-4 pt-4 border-t"
            >
                <div class="flex items-center gap-2 text-sm font-medium">
                    <MessageSquare class="h-4 w-4" />
                    <span>Deine Antwort:</span>
                </div>
                <div
                    v-for="response in review.responses"
                    :key="response.id"
                    class="pl-6 border-l-2 border-primary/30"
                >
                    <p class="text-sm">{{ response.text }}</p>
                    <p class="text-xs text-muted-foreground mt-1">
                        {{
                            response.sent_at
                                ? `Gesendet am ${new Date(response.sent_at).toLocaleDateString('de-DE')}`
                                : 'Noch nicht gesendet'
                        }}
                    </p>
                </div>
            </div>

            <!-- Reply Form -->
            <div v-if="isReplying && review.status !== 'archived'" class="space-y-3 pt-4">
                <!-- AI-Generierung Bereich -->
                <div class="rounded-lg border bg-muted/30 p-3 space-y-3">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <Sparkles class="h-4 w-4 text-primary" />
                            <span class="text-sm font-medium">AI-Assistent</span>
                        </div>
                        <Button
                            @click="showStyleSelector = !showStyleSelector"
                            variant="ghost"
                            size="sm"
                            class="h-7 text-xs"
                        >
                            {{ showStyleSelector ? 'Verbergen' : 'Stil wählen' }}
                        </Button>
                    </div>

                    <!-- Stil-Auswahl -->
                    <div v-if="showStyleSelector" class="space-y-2">
                        <label class="text-xs text-muted-foreground">Antwort-Stil:</label>
                        <Select v-model="selectedStyle">
                            <SelectTrigger class="h-9">
                                <SelectValue :placeholder="aiStyles[selectedStyle].name" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectGroup>
                                    <SelectItem
                                        v-for="(style, key) in aiStyles"
                                        :key="key"
                                        :value="key"
                                    >
                                        <div class="flex flex-col">
                                            <span class="font-medium">{{ style.name }}</span>
                                            <span class="text-xs text-muted-foreground">{{ style.description }}</span>
                                        </div>
                                    </SelectItem>
                                </SelectGroup>
                            </SelectContent>
                        </Select>
                    </div>

                    <!-- AI Generieren Button -->
                    <Button
                        @click="generateAIResponse"
                        :disabled="aiGenerating || submitting"
                        variant="secondary"
                        size="sm"
                        class="w-full"
                    >
                        <Loader2 v-if="aiGenerating" class="mr-2 h-4 w-4 animate-spin" />
                        <Sparkles v-else class="mr-2 h-4 w-4" />
                        {{ aiGenerating ? 'Generiere Antwort...' : 'Antwort mit AI generieren' }}
                    </Button>
                </div>

                <!-- Textarea für Antwort (editierbar) -->
                <Textarea
                    v-model="replyText"
                    placeholder="Schreibe eine Antwort oder generiere eine mit AI..."
                    rows="4"
                    :disabled="submitting || aiGenerating"
                    class="resize-none"
                />

                <!-- Aktions-Buttons -->
                <div class="flex items-center gap-2">
                    <Button
                        @click="submitReply"
                        :disabled="!replyText.trim() || submitting || aiGenerating"
                        size="sm"
                    >
                        <Send class="mr-2 h-4 w-4" />
                        {{ submitting ? 'Wird gesendet...' : 'Antwort senden' }}
                    </Button>
                    <Button
                        @click="toggleReply"
                        variant="outline"
                        size="sm"
                        :disabled="submitting || aiGenerating"
                    >
                        Abbrechen
                    </Button>
                </div>
            </div>
        </CardContent>

        <CardFooter class="flex items-center justify-between flex-wrap gap-2">
            <!-- Left Actions -->
            <div class="flex items-center gap-2">
                <!-- Reply Button (nur wenn noch nicht beantwortet) -->
                <SimpleTooltip
                    v-if="
                        !isReplying &&
                        review.status === 'pending' &&
                        (!review.responses || review.responses.length === 0)
                    "
                    text="Verfasse eine öffentliche Antwort auf diese Bewertung"
                >
                    <Button
                        @click="toggleReply"
                        variant="default"
                        size="sm"
                    >
                        <MessageSquare class="mr-2 h-4 w-4" />
                        Antworten
                    </Button>
                </SimpleTooltip>
            </div>

            <!-- Right Actions -->
            <div class="flex items-center gap-2">
                <!-- Archive Button -->
                <SimpleTooltip
                    v-if="review.status !== 'archived'"
                    text="Bewertung archivieren (aus der aktiven Liste entfernen)"
                >
                    <Button
                        @click="updateStatus('archived')"
                        variant="outline"
                        size="sm"
                    >
                        <Archive class="mr-2 h-4 w-4" />
                        Archivieren
                    </Button>
                </SimpleTooltip>

                <!-- Unarchive Button -->
                <SimpleTooltip
                    v-if="review.status === 'archived'"
                    text="Bewertung wiederherstellen (zurück zur aktiven Liste)"
                >
                    <Button
                        @click="updateStatus('pending')"
                        variant="outline"
                        size="sm"
                    >
                        <RotateCcw class="mr-2 h-4 w-4" />
                        Wiederherstellen
                    </Button>
                </SimpleTooltip>
            </div>
        </CardFooter>
    </Card>
</template>
