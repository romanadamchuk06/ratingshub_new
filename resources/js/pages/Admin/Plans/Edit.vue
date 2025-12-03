<script setup>
/**
 * ADMIN PLAN EDIT
 * ================
 *
 * Formular zum Bearbeiten bestehender Subscription-Pläne
 *
 * WICHTIG:
 * - Wenn User den Plan nutzen, Warnung anzeigen
 * - Stripe Plan ID sollte nicht geändert werden (nur bei Bedarf)
 * - Preis-Änderungen betreffen nur NEUE Subscriptions
 */

import AppLayout from '@/layouts/AppLayout.vue';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Switch } from '@/components/ui/switch';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { ArrowLeft, Plus, X, AlertCircle, CheckCircle2 } from 'lucide-vue-next';
import { computed } from 'vue';

const props = defineProps({
    plan: {
        type: Object,
        required: true,
    },
});

// Flash-Messages vom Server (success/error)
const page = usePage();
const flashSuccess = computed(() => page.props.flash?.success);
const flashError = computed(() => page.props.flash?.error);

// Form mit bestehenden Daten vorausfüllen
const form = useForm({
    name: props.plan.name,
    slug: props.plan.slug,
    stripe_plan_id: props.plan.stripe_plan_id || '',
    price: props.plan.price,
    max_platforms: props.plan.max_platforms,
    description: props.plan.description || '',
    // Features: Wenn es ein Object ist (aus DB), wandle es in Array um
    // Wenn es ein Array ist, verwende es direkt
    // Wenn leer, starte mit einem leeren String
    features: Array.isArray(props.plan.features) && props.plan.features.length > 0
        ? props.plan.features
        : (props.plan.features && typeof props.plan.features === 'object'
            ? Object.values(props.plan.features)
            : ['']),
    is_active: !!props.plan.is_active, // Boolean erzwingen
    is_popular: !!props.plan.is_popular, // Boolean erzwingen
    sort_order: props.plan.sort_order || 10,
});

/**
 * Feature hinzufügen
 */
const addFeature = () => {
    form.features.push('');
};

/**
 * Feature entfernen
 */
const removeFeature = (index) => {
    form.features.splice(index, 1);
};

/**
 * Form absenden
 */
const submit = () => {
    // Features filtern (nur nicht-leere)
    form.features = form.features.filter((f) => f && f.trim() !== '');

    // Datentypen sicherstellen
    form.price = parseFloat(form.price);
    form.max_platforms = parseInt(form.max_platforms);
    form.sort_order = parseInt(form.sort_order);
    form.is_active = !!form.is_active;
    form.is_popular = !!form.is_popular;

    // DEBUGGING: Log Form-Daten in Console
    console.log('Plan Update - Gesendete Daten:', form.data());

    form.patch(`/admin/plans/${props.plan.id}`, {
        preserveScroll: true,
        onSuccess: () => {
            console.log('Plan erfolgreich aktualisiert');
        },
        onError: (errors) => {
            // DEBUGGING: Log Fehler in Console
            console.error('Plan Update Fehler:', errors);
        },
    });
};
</script>

<template>
    <Head :title="`${plan.name} bearbeiten`" />

    <AppLayout>
        <div class="space-y-6 p-4 md:p-6 lg:p-8">
            <!-- Header -->
            <div class="flex items-center gap-4">
                <Link href="/admin/plans">
                    <Button variant="ghost" size="icon">
                        <ArrowLeft class="h-4 w-4" />
                    </Button>
                </Link>
                <div>
                    <h1 class="text-2xl font-bold tracking-tight md:text-3xl">
                        {{ plan.name }} bearbeiten
                    </h1>
                    <p class="text-muted-foreground">
                        Ändere die Eigenschaften dieses Plans
                    </p>
                </div>
            </div>

            <!-- SUCCESS-MESSAGE (vom Server) -->
            <Alert v-if="flashSuccess" variant="default" class="border-green-500 bg-green-50 dark:bg-green-950">
                <CheckCircle2 class="h-4 w-4 text-green-600 dark:text-green-400" />
                <AlertTitle class="text-green-800 dark:text-green-200">Erfolg</AlertTitle>
                <AlertDescription class="text-green-700 dark:text-green-300">
                    {{ flashSuccess }}
                </AlertDescription>
            </Alert>

            <!-- ERROR-MESSAGE (vom Server) -->
            <Alert v-if="flashError" variant="destructive">
                <AlertCircle class="h-4 w-4" />
                <AlertTitle>Fehler</AlertTitle>
                <AlertDescription>
                    {{ flashError }}
                </AlertDescription>
            </Alert>

            <!-- ALLGEMEINE VALIDIERUNGSFEHLER -->
            <Alert v-if="form.errors && Object.keys(form.errors).length > 0 && !form.errors.name && !form.errors.slug && !form.errors.price" variant="destructive">
                <AlertCircle class="h-4 w-4" />
                <AlertTitle>Validierungsfehler</AlertTitle>
                <AlertDescription>
                    Bitte überprüfe die markierten Felder.
                </AlertDescription>
            </Alert>

            <!-- Warnung: User nutzen diesen Plan -->
            <Alert v-if="plan.users_count > 0" variant="default">
                <AlertCircle class="h-4 w-4" />
                <AlertDescription>
                    <strong>{{ plan.users_count }} Benutzer</strong> nutzen aktuell diesen Plan.
                    Änderungen betreffen nur <strong>neue</strong> Subscriptions.
                </AlertDescription>
            </Alert>

            <form @submit.prevent="submit" class="mx-auto max-w-2xl space-y-6">
                <!-- Basis-Informationen -->
                <Card>
                    <CardHeader>
                        <CardTitle>Basis-Informationen</CardTitle>
                        <CardDescription>
                            Name, Slug und Preis des Plans
                        </CardDescription>
                    </CardHeader>
                    <CardContent class="space-y-4">
                        <!-- Name -->
                        <div class="space-y-2">
                            <Label for="name">Plan-Name *</Label>
                            <Input
                                id="name"
                                v-model="form.name"
                                placeholder="z.B. Premium"
                                required
                            />
                            <p v-if="form.errors.name" class="text-sm text-destructive">
                                {{ form.errors.name }}
                            </p>
                        </div>

                        <!-- Slug -->
                        <div class="space-y-2">
                            <Label for="slug">Slug *</Label>
                            <Input
                                id="slug"
                                v-model="form.slug"
                                placeholder="z.B. premium"
                                required
                            />
                            <p class="text-xs text-muted-foreground">
                                Nur Kleinbuchstaben, Zahlen und Bindestriche
                            </p>
                            <p v-if="form.errors.slug" class="text-sm text-destructive">
                                {{ form.errors.slug }}
                            </p>
                        </div>

                        <!-- Preis -->
                        <div class="space-y-2">
                            <Label for="price">Preis (€) *</Label>
                            <Input
                                id="price"
                                v-model="form.price"
                                type="number"
                                step="0.01"
                                min="0"
                                max="9999.99"
                                placeholder="9.99"
                                required
                            />
                            <p class="text-xs text-muted-foreground">
                                0.00 für kostenlose Pläne
                            </p>
                            <p v-if="form.errors.price" class="text-sm text-destructive">
                                {{ form.errors.price }}
                            </p>
                        </div>

                        <!-- Stripe Plan ID -->
                        <div class="space-y-2">
                            <Label for="stripe_plan_id">Stripe Price ID</Label>
                            <Input
                                id="stripe_plan_id"
                                v-model="form.stripe_plan_id"
                                placeholder="price_xxxxx"
                            />
                            <p class="text-xs text-muted-foreground">
                                ⚠️ Nur ändern wenn du eine neue Stripe Price erstellt hast
                            </p>
                            <p v-if="form.errors.stripe_plan_id" class="text-sm text-destructive">
                                {{ form.errors.stripe_plan_id }}
                            </p>
                        </div>
                    </CardContent>
                </Card>

                <!-- Features & Limits -->
                <Card>
                    <CardHeader>
                        <CardTitle>Features & Limits</CardTitle>
                        <CardDescription>
                            Definiere die Funktionen und Limits
                        </CardDescription>
                    </CardHeader>
                    <CardContent class="space-y-4">
                        <!-- Max Plattformen -->
                        <div class="space-y-2">
                            <Label for="max_platforms">Max. Plattformen *</Label>
                            <Input
                                id="max_platforms"
                                v-model.number="form.max_platforms"
                                type="number"
                                min="1"
                                max="1000"
                                required
                            />
                            <p class="text-xs text-muted-foreground">
                                1000 = Unbegrenzt
                            </p>
                            <p v-if="form.errors.max_platforms" class="text-sm text-destructive">
                                {{ form.errors.max_platforms }}
                            </p>
                        </div>

                        <!-- Features -->
                        <div class="space-y-2">
                            <Label>Features</Label>
                            <div class="space-y-2">
                                <div
                                    v-for="(feature, index) in form.features"
                                    :key="index"
                                    class="flex gap-2"
                                >
                                    <Input
                                        v-model="form.features[index]"
                                        placeholder="z.B. Erweiterte Analytics"
                                    />
                                    <Button
                                        type="button"
                                        variant="ghost"
                                        size="icon"
                                        @click="removeFeature(index)"
                                        :disabled="form.features.length === 1"
                                    >
                                        <X class="h-4 w-4" />
                                    </Button>
                                </div>
                            </div>
                            <Button
                                type="button"
                                variant="outline"
                                size="sm"
                                @click="addFeature"
                                class="mt-2"
                            >
                                <Plus class="mr-2 h-4 w-4" />
                                Feature hinzufügen
                            </Button>
                            <p v-if="form.errors.features" class="text-sm text-destructive">
                                {{ form.errors.features }}
                            </p>
                        </div>

                        <!-- Beschreibung -->
                        <div class="space-y-2">
                            <Label for="description">Beschreibung</Label>
                            <Input
                                id="description"
                                v-model="form.description"
                                placeholder="Kurze Beschreibung des Plans"
                            />
                            <p v-if="form.errors.description" class="text-sm text-destructive">
                                {{ form.errors.description }}
                            </p>
                        </div>
                    </CardContent>
                </Card>

                <!-- Einstellungen -->
                <Card>
                    <CardHeader>
                        <CardTitle>Einstellungen</CardTitle>
                        <CardDescription>
                            Status und Sortierung
                        </CardDescription>
                    </CardHeader>
                    <CardContent class="space-y-4">
                        <!-- Aktiv -->
                        <div class="flex items-center justify-between">
                            <div class="space-y-0.5">
                                <Label for="is_active">Plan aktiv</Label>
                                <p class="text-xs text-muted-foreground">
                                    Inaktive Pläne werden nicht angezeigt
                                </p>
                            </div>
                            <Switch
                                id="is_active"
                                v-model:checked="form.is_active"
                            />
                        </div>

                        <!-- Popular Badge -->
                        <div class="flex items-center justify-between">
                            <div class="space-y-0.5">
                                <Label for="is_popular">Als "Beliebt" markieren</Label>
                                <p class="text-xs text-muted-foreground">
                                    Zeigt "Beliebt"-Badge auf der Pricing-Seite
                                </p>
                            </div>
                            <Switch
                                id="is_popular"
                                v-model:checked="form.is_popular"
                            />
                        </div>

                        <!-- Sort Order -->
                        <div class="space-y-2">
                            <Label for="sort_order">Sortierung</Label>
                            <Input
                                id="sort_order"
                                v-model.number="form.sort_order"
                                type="number"
                                min="0"
                                max="100"
                            />
                            <p class="text-xs text-muted-foreground">
                                Niedrigere Zahlen = weiter oben
                            </p>
                            <p v-if="form.errors.sort_order" class="text-sm text-destructive">
                                {{ form.errors.sort_order }}
                            </p>
                        </div>
                    </CardContent>
                </Card>

                <!-- Actions -->
                <div class="flex justify-end gap-3">
                    <Link href="/admin/plans">
                        <Button type="button" variant="outline">
                            Abbrechen
                        </Button>
                    </Link>
                    <Button type="submit" :disabled="form.processing">
                        {{ form.processing ? 'Wird gespeichert...' : 'Änderungen speichern' }}
                    </Button>
                </div>
            </form>
        </div>
    </AppLayout>
</template>
