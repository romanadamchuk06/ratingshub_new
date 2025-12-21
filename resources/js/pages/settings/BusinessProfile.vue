<script setup>
import { Head, useForm } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import Heading from '@/components/Heading.vue';
import HeadingSmall from '@/components/HeadingSmall.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Alert, AlertDescription } from '@/components/ui/alert';
import { Clock, Info } from 'lucide-vue-next';

/**
 * Business Profile Page
 *
 * Verwaltet Google My Business Öffnungszeiten:
 * - Lädt Öffnungszeiten von Google My Business
 * - Ermöglicht Bearbeitung (Mo-So)
 * - Synchronisiert Änderungen zurück zu Google
 */

const props = defineProps({
    businessProfile: {
        type: Object,
        required: true,
    },
    hasGooglePlatform: {
        type: Boolean,
        default: false,
    },
});

const breadcrumbItems = [
    {
        title: 'Unternehmen',
        href: '/settings/business',
    },
];

// Wochentage
const weekdays = [
    { key: 'monday', label: 'Montag' },
    { key: 'tuesday', label: 'Dienstag' },
    { key: 'wednesday', label: 'Mittwoch' },
    { key: 'thursday', label: 'Donnerstag' },
    { key: 'friday', label: 'Freitag' },
    { key: 'saturday', label: 'Samstag' },
    { key: 'sunday', label: 'Sonntag' },
];

// Form initialisieren - nur Öffnungszeiten (keine Default-Werte, nur von Google)
const form = useForm({
    opening_hours: props.businessProfile.opening_hours || {},
});

// Stelle sicher, dass alle Tage existieren
weekdays.forEach(day => {
    if (!form.opening_hours[day.key]) {
        form.opening_hours[day.key] = {
            open: '09:00',
            close: '17:00',
            closed: false
        };
    }
});

// Toggle Funktion - wechselt zwischen geöffnet und geschlossen
const toggleStatus = (dayKey) => {
    form.opening_hours[dayKey].closed = !form.opening_hours[dayKey].closed;
};

const submit = () => {
    form.put('/settings/business', {
        preserveScroll: true,
    });
};
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbItems">
        <Head title="Unternehmensprofil" />

        <div class="px-4 py-6 max-w-4xl">
            <Heading
                title="Unternehmensprofil"
                description="Verwalte die Öffnungszeiten deines Unternehmens"
            />

            <!-- Info-Banner: Google verbunden -->
            <Alert v-if="hasGooglePlatform" class="mt-6">
                <Info class="h-4 w-4" />
                <AlertDescription>
                    Deine Öffnungszeiten werden automatisch mit Google My Business synchronisiert.
                    Änderungen werden direkt in deinem Google-Profil übernommen.
                </AlertDescription>
            </Alert>

            <!-- Info-Banner: Keine Google-Verbindung -->
            <Alert v-else variant="destructive" class="mt-6">
                <Info class="h-4 w-4" />
                <AlertDescription>
                    Du hast noch keine Google-Plattform verbunden.
                    Verbinde dein Google My Business-Konto unter
                    <a href="/settings/platforms" class="underline font-medium">Einstellungen → Plattformen</a>,
                    um deine Öffnungszeiten zu synchronisieren.
                </AlertDescription>
            </Alert>

            <form @submit.prevent="submit" class="space-y-8 mt-8">
                <!-- Öffnungszeiten -->
                <div class="space-y-6">
                    <HeadingSmall
                        title="Öffnungszeiten"
                        description="Wann ist dein Unternehmen geöffnet?"
                    >
                        <template #icon>
                            <Clock class="h-5 w-5" />
                        </template>
                    </HeadingSmall>

                    <div class="space-y-3">
                        <div
                            v-for="day in weekdays"
                            :key="day.key"
                            class="grid gap-4 items-center md:grid-cols-[120px_1fr_1fr_140px]"
                        >
                            <Label class="font-medium">{{ day.label }}</Label>

                            <div class="space-y-2">
                                <Input
                                    v-model="form.opening_hours[day.key].open"
                                    type="time"
                                    :disabled="!hasGooglePlatform || form.opening_hours[day.key].closed"
                                />
                            </div>

                            <div class="space-y-2">
                                <Input
                                    v-model="form.opening_hours[day.key].close"
                                    type="time"
                                    :disabled="!hasGooglePlatform || form.opening_hours[day.key].closed"
                                />
                            </div>

                            <div class="flex items-center gap-2">
                                <Button
                                    type="button"
                                    @click="toggleStatus(day.key)"
                                    :disabled="!hasGooglePlatform"
                                    :class="[
                                        'min-w-[120px] transition-all',
                                        form.opening_hours[day.key].closed
                                            ? 'bg-neutral-500 hover:bg-neutral-600 text-white'
                                            : 'bg-green-600 hover:bg-green-700 text-white'
                                    ]"
                                >
                                    {{ form.opening_hours[day.key].closed ? 'Geschlossen' : 'Geöffnet' }}
                                </Button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Actions -->
                <div class="flex items-center gap-4 pt-4 border-t">
                    <Button
                        type="submit"
                        :disabled="form.processing"
                    >
                        {{ hasGooglePlatform ? 'In Google speichern' : 'Speichern' }}
                    </Button>

                    <Transition
                        enter-active-class="transition ease-in-out"
                        enter-from-class="opacity-0"
                        leave-active-class="transition ease-in-out"
                        leave-to-class="opacity-0"
                    >
                        <p
                            v-show="form.recentlySuccessful"
                            class="text-sm text-neutral-600"
                        >
                            Gespeichert.
                        </p>
                    </Transition>
                </div>
            </form>
        </div>
    </AppLayout>
</template>
