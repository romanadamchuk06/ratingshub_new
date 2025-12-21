<script setup>
import { Head, useForm, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import AppLayout from '@/layouts/AppLayout.vue';
import SettingsLayout from '@/layouts/settings/Layout.vue';
import HeadingSmall from '@/components/HeadingSmall.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Switch } from '@/components/ui/switch';
import { Building2, MapPin, Clock, Globe, Phone, Mail } from 'lucide-vue-next';

/**
 * Business Profile Page
 *
 * Ermöglicht dem User die Verwaltung seines Unternehmensprofils:
 * - Firmendaten (Name, Beschreibung, Branche)
 * - Kontaktdaten (Telefon, E-Mail, Website)
 * - Adresse
 * - Öffnungszeiten (Mo-So)
 * - Social Media Links
 */

const props = defineProps({
    businessProfile: {
        type: Object,
        required: true,
    },
});

const breadcrumbItems = [
    {
        title: 'Unternehmen',
        href: '/settings/business',
    },
];

// Branchen-Optionen
const industries = [
    'Restaurant',
    'Café',
    'Hotel',
    'Einzelhandel',
    'Dienstleister',
    'Handwerk',
    'Beratung',
    'Gesundheit',
    'Fitness',
    'Bildung',
    'Sonstiges',
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

// Form initialisieren
const form = useForm({
    business_name: props.businessProfile.business_name || '',
    description: props.businessProfile.description || '',
    industry: props.businessProfile.industry || '',
    phone: props.businessProfile.phone || '',
    email: props.businessProfile.email || '',
    website: props.businessProfile.website || '',
    street: props.businessProfile.street || '',
    city: props.businessProfile.city || '',
    postal_code: props.businessProfile.postal_code || '',
    country: props.businessProfile.country || 'Deutschland',
    opening_hours: props.businessProfile.opening_hours || {
        monday: { open: '09:00', close: '18:00', closed: false },
        tuesday: { open: '09:00', close: '18:00', closed: false },
        wednesday: { open: '09:00', close: '18:00', closed: false },
        thursday: { open: '09:00', close: '18:00', closed: false },
        friday: { open: '09:00', close: '18:00', closed: false },
        saturday: { open: '10:00', close: '14:00', closed: true },
        sunday: { open: '00:00', close: '00:00', closed: true },
    },
    social_links: props.businessProfile.social_links || {
        facebook: '',
        instagram: '',
        twitter: '',
        linkedin: '',
    },
});

const submit = () => {
    form.put('/settings/business', {
        preserveScroll: true,
    });
};
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbItems">
        <Head title="Unternehmensprofil" />

        <SettingsLayout>
            <form @submit.prevent="submit" class="space-y-8">
                <!-- Firmendaten -->
                <div class="space-y-6">
                    <HeadingSmall
                        title="Firmendaten"
                        description="Grundlegende Informationen über dein Unternehmen"
                    >
                        <template #icon>
                            <Building2 class="h-5 w-5" />
                        </template>
                    </HeadingSmall>

                    <div class="grid gap-4 md:grid-cols-2">
                        <div class="space-y-2">
                            <Label for="business_name">Firmenname</Label>
                            <Input
                                id="business_name"
                                v-model="form.business_name"
                                placeholder="Meine Firma GmbH"
                            />
                            <InputError :message="form.errors.business_name" />
                        </div>

                        <div class="space-y-2">
                            <Label for="industry">Branche</Label>
                            <Select v-model="form.industry">
                                <SelectTrigger>
                                    <SelectValue placeholder="Branche wählen" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem v-for="industry in industries" :key="industry" :value="industry">
                                        {{ industry }}
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                            <InputError :message="form.errors.industry" />
                        </div>
                    </div>

                    <div class="space-y-2">
                        <Label for="description">Beschreibung</Label>
                        <Textarea
                            id="description"
                            v-model="form.description"
                            placeholder="Beschreibe dein Unternehmen..."
                            rows="4"
                        />
                        <InputError :message="form.errors.description" />
                    </div>
                </div>

                <!-- Kontaktdaten -->
                <div class="space-y-6">
                    <HeadingSmall
                        title="Kontaktdaten"
                        description="So können dich deine Kunden erreichen"
                    >
                        <template #icon>
                            <Phone class="h-5 w-5" />
                        </template>
                    </HeadingSmall>

                    <div class="grid gap-4 md:grid-cols-3">
                        <div class="space-y-2">
                            <Label for="phone">Telefon</Label>
                            <Input
                                id="phone"
                                v-model="form.phone"
                                type="tel"
                                placeholder="+49 123 456789"
                            />
                            <InputError :message="form.errors.phone" />
                        </div>

                        <div class="space-y-2">
                            <Label for="email">E-Mail</Label>
                            <Input
                                id="email"
                                v-model="form.email"
                                type="email"
                                placeholder="kontakt@firma.de"
                            />
                            <InputError :message="form.errors.email" />
                        </div>

                        <div class="space-y-2">
                            <Label for="website">Website</Label>
                            <Input
                                id="website"
                                v-model="form.website"
                                type="url"
                                placeholder="https://www.firma.de"
                            />
                            <InputError :message="form.errors.website" />
                        </div>
                    </div>
                </div>

                <!-- Adresse -->
                <div class="space-y-6">
                    <HeadingSmall
                        title="Adresse"
                        description="Wo befindet sich dein Unternehmen?"
                    >
                        <template #icon>
                            <MapPin class="h-5 w-5" />
                        </template>
                    </HeadingSmall>

                    <div class="grid gap-4">
                        <div class="space-y-2">
                            <Label for="street">Straße & Hausnummer</Label>
                            <Input
                                id="street"
                                v-model="form.street"
                                placeholder="Musterstraße 123"
                            />
                            <InputError :message="form.errors.street" />
                        </div>

                        <div class="grid gap-4 md:grid-cols-3">
                            <div class="space-y-2">
                                <Label for="postal_code">PLZ</Label>
                                <Input
                                    id="postal_code"
                                    v-model="form.postal_code"
                                    placeholder="12345"
                                />
                                <InputError :message="form.errors.postal_code" />
                            </div>

                            <div class="space-y-2">
                                <Label for="city">Stadt</Label>
                                <Input
                                    id="city"
                                    v-model="form.city"
                                    placeholder="Berlin"
                                />
                                <InputError :message="form.errors.city" />
                            </div>

                            <div class="space-y-2">
                                <Label for="country">Land</Label>
                                <Input
                                    id="country"
                                    v-model="form.country"
                                    placeholder="Deutschland"
                                />
                                <InputError :message="form.errors.country" />
                            </div>
                        </div>
                    </div>
                </div>

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
                            class="grid gap-4 items-center md:grid-cols-[120px_1fr_1fr_100px]"
                        >
                            <Label class="font-medium">{{ day.label }}</Label>

                            <div class="space-y-2">
                                <Input
                                    v-model="form.opening_hours[day.key].open"
                                    type="time"
                                    :disabled="form.opening_hours[day.key].closed"
                                />
                            </div>

                            <div class="space-y-2">
                                <Input
                                    v-model="form.opening_hours[day.key].close"
                                    type="time"
                                    :disabled="form.opening_hours[day.key].closed"
                                />
                            </div>

                            <div class="flex items-center gap-2">
                                <Switch
                                    :checked="!form.opening_hours[day.key].closed"
                                    @update:checked="(val) => form.opening_hours[day.key].closed = !val"
                                />
                                <Label class="text-sm text-muted-foreground">Geöffnet</Label>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Social Media -->
                <div class="space-y-6">
                    <HeadingSmall
                        title="Social Media"
                        description="Verlinke deine Social-Media-Profile"
                    >
                        <template #icon>
                            <Globe class="h-5 w-5" />
                        </template>
                    </HeadingSmall>

                    <div class="grid gap-4 md:grid-cols-2">
                        <div class="space-y-2">
                            <Label for="facebook">Facebook</Label>
                            <Input
                                id="facebook"
                                v-model="form.social_links.facebook"
                                type="url"
                                placeholder="https://facebook.com/..."
                            />
                        </div>

                        <div class="space-y-2">
                            <Label for="instagram">Instagram</Label>
                            <Input
                                id="instagram"
                                v-model="form.social_links.instagram"
                                type="url"
                                placeholder="https://instagram.com/..."
                            />
                        </div>

                        <div class="space-y-2">
                            <Label for="twitter">Twitter</Label>
                            <Input
                                id="twitter"
                                v-model="form.social_links.twitter"
                                type="url"
                                placeholder="https://twitter.com/..."
                            />
                        </div>

                        <div class="space-y-2">
                            <Label for="linkedin">LinkedIn</Label>
                            <Input
                                id="linkedin"
                                v-model="form.social_links.linkedin"
                                type="url"
                                placeholder="https://linkedin.com/company/..."
                            />
                        </div>
                    </div>
                </div>

                <!-- Actions -->
                <div class="flex items-center gap-4 pt-4 border-t">
                    <Button
                        type="submit"
                        :disabled="form.processing"
                    >
                        Speichern
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
        </SettingsLayout>
    </AppLayout>
</template>
