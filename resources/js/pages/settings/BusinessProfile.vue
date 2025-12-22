<script setup>
import { Head, useForm, usePage } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import AppLayout from '@/layouts/AppLayout.vue';
import HeadingSmall from '@/components/HeadingSmall.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Clock } from 'lucide-vue-next';

/**
 * Business Profile Page - Öffnungszeiten Verwaltung
 *
 * Ermöglicht dem User die Verwaltung der Öffnungszeiten
 * Synchronisiert mit Google My Business API
 */

const props = defineProps({
    businessProfile: {
        type: Object,
        required: true,
    },
});

const page = usePage();

// Prüfen ob User eine Google-Plattform verbunden hat
const hasGooglePlatform = computed(() => {
    // Prüfe ob opening_hours existiert (dann wurden sie von Google geladen)
    return props.businessProfile.opening_hours !== null &&
           Object.keys(props.businessProfile.opening_hours).length > 0;
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

// Form initialisieren - NUR mit Öffnungszeiten
const form = useForm({
    opening_hours: props.businessProfile.opening_hours || {},
});

// Stelle sicher, dass alle Tage initialisiert sind (für v-model)
weekdays.forEach(day => {
    if (!form.opening_hours[day.key]) {
        form.opening_hours[day.key] = {
            open: '09:00',
            close: '17:00',
            closed: false
        };
    }
});

// Toggle für Geöffnet/Geschlossen
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
        <Head title="Öffnungszeiten" />

        <div class="p-4 md:p-6 lg:p-8 max-w-4xl mx-auto">
            <form @submit.prevent="submit" class="space-y-8">
                <!-- Öffnungszeiten -->
                <div class="space-y-6">
                    <HeadingSmall
                        title="Öffnungszeiten"
                        description="Verwalte deine Öffnungszeiten und synchronisiere sie mit Google My Business"
                    >
                        <template #icon>
                            <Clock class="h-5 w-5" />
                        </template>
                    </HeadingSmall>

                    <!-- Hinweis falls keine Google-Plattform verbunden -->
                    <div v-if="!hasGooglePlatform" class="rounded-lg border border-amber-200 bg-amber-50 dark:border-amber-900/30 dark:bg-amber-950/20 p-4">
                        <p class="text-sm text-amber-800 dark:text-amber-200">
                            ℹ️ Verbinde deine Google My Business Plattform, um Öffnungszeiten automatisch zu synchronisieren.
                        </p>
                    </div>

                    <div class="space-y-3">
                        <div
                            v-for="day in weekdays"
                            :key="day.key"
                            class="grid gap-4 items-center grid-cols-1 md:grid-cols-[140px_1fr_1fr_140px]"
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

                <!-- Actions -->
                <div class="flex items-center gap-4 pt-4 border-t">
                    <Button
                        type="submit"
                        :disabled="form.processing || !hasGooglePlatform"
                    >
                        Mit Google synchronisieren
                    </Button>

                    <Transition
                        enter-active-class="transition ease-in-out"
                        enter-from-class="opacity-0"
                        leave-active-class="transition ease-in-out"
                        leave-to-class="opacity-0"
                    >
                        <p
                            v-show="form.recentlySuccessful"
                            class="text-sm text-green-600 dark:text-green-400"
                        >
                            ✅ Erfolgreich synchronisiert!
                        </p>
                    </Transition>
                </div>
            </form>
        </div>
    </AppLayout>
</template>
