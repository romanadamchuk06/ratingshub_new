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
import { Head, Link, useForm } from '@inertiajs/vue3';
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
import { Alert, AlertDescription } from '@/components/ui/alert';
import { ArrowLeft, Plus, X, AlertCircle } from 'lucide-vue-next';

const props = defineProps({
    plan: {
        type: Object,
        required: true,
    },
});

// Form mit bestehenden Daten vorausfüllen
const form = useForm({
    name: props.plan.name,
    slug: props.plan.slug,
    stripe_plan_id: props.plan.stripe_plan_id || '',
    price: props.plan.price,
    max_platforms: props.plan.max_platforms,
    description: props.plan.description || '',
    features: props.plan.features && props.plan.features.length > 0 ? props.plan.features : [''],
    is_active: props.plan.is_active,
    is_popular: props.plan.is_popular || false, // Zeigt "Beliebt"-Badge auf Pricing-Seite
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
    form.features = form.features.filter((f) => f.trim() !== '');

    form.patch(`/admin/plans/${props.plan.id}`, {
        onError: () => {
            // Fehler werden automatisch von Inertia behandelt
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
