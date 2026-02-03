<script setup>
/**
 * ADMIN PLAN EDIT
 * ================
 *
 * Bearbeitet Tarif mit zwei Stripe Price IDs:
 * - stripe_price_id_monthly
 * - stripe_price_id_yearly
 */

import AppLayout from '@/layouts/AppLayout.vue';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { Switch } from '@/components/ui/switch';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { ExternalLink, Save, ArrowLeft, AlertCircle, Info, CheckCircle2, Plus, X } from 'lucide-vue-next';
import { computed } from 'vue';

const props = defineProps({
    plan: {
        type: Object,
        required: true,
    },
});

// Flash Messages
const page = usePage();
const flashSuccess = computed(() => page.props.flash?.success);
const flashError = computed(() => page.props.flash?.error);

const form = useForm({
    name: props.plan.name,
    slug: props.plan.slug,
    // Stripe Price IDs (NEU: zwei separate IDs)
    stripe_price_id_monthly: props.plan.stripe_price_id_monthly || '',
    stripe_price_id_yearly: props.plan.stripe_price_id_yearly || '',
    // Preise (für Anzeige)
    price: props.plan.price || '0.00',
    price_yearly: props.plan.price_yearly || '',
    // Limits
    max_platforms: props.plan.max_platforms || 1,
    // Description & Features
    description: props.plan.description || '',
    features: Array.isArray(props.plan.features) && props.plan.features.length > 0
        ? props.plan.features
        : [''],
    // Status
    is_active: !!props.plan.is_active,
    is_popular: !!props.plan.is_popular,
    sort_order: props.plan.sort_order || 0,
});

// Prüft ob Plan bezahlt ist
const isPaidPlan = computed(() => parseFloat(form.price) > 0);

const submit = () => {
    // Leere Features filtern
    form.features = form.features.filter(f => f && f.trim() !== '');
    form.patch(`/admin/plans/${props.plan.id}`, {
        preserveScroll: true,
    });
};

// Feature hinzufügen
const addFeature = () => {
    form.features.push('');
};

// Feature entfernen
const removeFeature = (index) => {
    form.features.splice(index, 1);
};
</script>

<template>
    <Head :title="`${plan.name} bearbeiten`" />

    <AppLayout :breadcrumbs="[
        { label: 'Admin', href: '/admin' },
        { label: 'Tarife', href: '/admin/plans' },
        { label: plan.name }
    ]">
        <div class="space-y-6 p-4 md:p-6 lg:p-8">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-4">
                    <Link href="/admin/plans">
                        <Button variant="ghost" size="icon">
                            <ArrowLeft class="h-4 w-4" />
                        </Button>
                    </Link>
                    <div>
                        <h1 class="text-2xl font-bold tracking-tight md:text-3xl">{{ plan.name }} bearbeiten</h1>
                        <p class="text-muted-foreground">
                            Konfiguriere Stripe Price IDs und Limits
                        </p>
                    </div>
                </div>
                <a
                    href="https://dashboard.stripe.com/products"
                    target="_blank"
                >
                    <Button variant="outline">
                        <ExternalLink class="mr-2 h-4 w-4" />
                        Stripe Dashboard
                    </Button>
                </a>
            </div>

            <!-- Success Message -->
            <Alert v-if="flashSuccess" variant="default" class="border-green-500 bg-green-50 dark:bg-green-950">
                <CheckCircle2 class="h-4 w-4 text-green-600" />
                <AlertTitle class="text-green-800 dark:text-green-200">Erfolg</AlertTitle>
                <AlertDescription class="text-green-700 dark:text-green-300">
                    {{ flashSuccess }}
                </AlertDescription>
            </Alert>

            <!-- Error Message -->
            <Alert v-if="flashError" variant="destructive">
                <AlertCircle class="h-4 w-4" />
                <AlertTitle>Fehler</AlertTitle>
                <AlertDescription>{{ flashError }}</AlertDescription>
            </Alert>

            <!-- User Warning -->
            <Alert v-if="plan.users_count > 0" variant="default">
                <AlertCircle class="h-4 w-4" />
                <AlertTitle>{{ plan.users_count }} Benutzer verwenden diesen Tarif</AlertTitle>
                <AlertDescription>
                    Änderungen an Limits wirken sich auf alle Benutzer aus.
                </AlertDescription>
            </Alert>

            <!-- Info Alert -->
            <Alert>
                <Info class="h-4 w-4" />
                <AlertDescription>
                    Die <strong>Stripe Price IDs</strong> findest du im Stripe Dashboard unter Produkte → Preise.
                    Sie beginnen mit <code class="bg-muted px-1 rounded">price_</code>.
                </AlertDescription>
            </Alert>

            <form @submit.prevent="submit" class="mx-auto max-w-2xl space-y-6">
                <!-- Grunddaten -->
                <Card>
                    <CardHeader>
                        <CardTitle>Grunddaten</CardTitle>
                        <CardDescription>Name und Slug des Tarifs</CardDescription>
                    </CardHeader>
                    <CardContent class="space-y-4">
                        <div class="grid gap-4 md:grid-cols-2">
                            <div class="space-y-2">
                                <Label for="name">Name *</Label>
                                <Input
                                    id="name"
                                    v-model="form.name"
                                    placeholder="z.B. Pro"
                                    :class="{ 'border-destructive': form.errors.name }"
                                    required
                                />
                                <p v-if="form.errors.name" class="text-sm text-destructive">
                                    {{ form.errors.name }}
                                </p>
                            </div>

                            <div class="space-y-2">
                                <Label for="slug">Slug *</Label>
                                <Input
                                    id="slug"
                                    v-model="form.slug"
                                    placeholder="z.B. pro"
                                    :class="{ 'border-destructive': form.errors.slug }"
                                    required
                                />
                                <p v-if="form.errors.slug" class="text-sm text-destructive">
                                    {{ form.errors.slug }}
                                </p>
                            </div>
                        </div>

                        <div class="space-y-2">
                            <Label for="description">Beschreibung</Label>
                            <Textarea
                                id="description"
                                v-model="form.description"
                                placeholder="Kurze Beschreibung des Tarifs..."
                                rows="3"
                            />
                        </div>
                    </CardContent>
                </Card>

                <!-- Stripe Konfiguration -->
                <Card>
                    <CardHeader>
                        <CardTitle>Stripe Konfiguration</CardTitle>
                        <CardDescription>
                            Stripe Price IDs für monatliche und jährliche Zahlung
                        </CardDescription>
                    </CardHeader>
                    <CardContent class="space-y-6">
                        <!-- Monatlich -->
                        <div class="rounded-lg border p-4 space-y-4">
                            <h4 class="font-medium">Monatliche Zahlung</h4>
                            <div class="grid gap-4 md:grid-cols-2">
                                <div class="space-y-2">
                                    <Label for="price">Preis (€/Monat) *</Label>
                                    <Input
                                        id="price"
                                        v-model="form.price"
                                        type="number"
                                        step="0.01"
                                        min="0"
                                        placeholder="14.99"
                                        required
                                    />
                                    <p class="text-xs text-muted-foreground">
                                        0 = Kostenloser Tarif
                                    </p>
                                </div>

                                <div class="space-y-2">
                                    <Label for="stripe_price_id_monthly">Stripe Price ID (Monatlich)</Label>
                                    <Input
                                        id="stripe_price_id_monthly"
                                        v-model="form.stripe_price_id_monthly"
                                        placeholder="price_1ABC..."
                                        :class="{ 'border-destructive': form.errors.stripe_price_id_monthly }"
                                    />
                                    <p v-if="isPaidPlan && !form.stripe_price_id_monthly" class="text-xs text-amber-600">
                                        Erforderlich für bezahlte Tarife
                                    </p>
                                </div>
                            </div>
                        </div>

                        <!-- Jährlich -->
                        <div class="rounded-lg border p-4 space-y-4">
                            <h4 class="font-medium">Jährliche Zahlung (optional)</h4>
                            <div class="grid gap-4 md:grid-cols-2">
                                <div class="space-y-2">
                                    <Label for="price_yearly">Preis (€/Jahr)</Label>
                                    <Input
                                        id="price_yearly"
                                        v-model="form.price_yearly"
                                        type="number"
                                        step="0.01"
                                        min="0"
                                        placeholder="149.99"
                                    />
                                    <p class="text-xs text-muted-foreground">
                                        Leer lassen wenn keine jährliche Option
                                    </p>
                                </div>

                                <div class="space-y-2">
                                    <Label for="stripe_price_id_yearly">Stripe Price ID (Jährlich)</Label>
                                    <Input
                                        id="stripe_price_id_yearly"
                                        v-model="form.stripe_price_id_yearly"
                                        placeholder="price_1XYZ..."
                                        :class="{ 'border-destructive': form.errors.stripe_price_id_yearly }"
                                    />
                                    <p v-if="parseFloat(form.price_yearly) > 0 && !form.stripe_price_id_yearly" class="text-xs text-amber-600">
                                        Erforderlich wenn Jahrespreis angegeben
                                    </p>
                                </div>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <!-- Limits & Features -->
                <Card>
                    <CardHeader>
                        <CardTitle>Limits & Features</CardTitle>
                        <CardDescription>
                            Was der Tarif beinhaltet
                        </CardDescription>
                    </CardHeader>
                    <CardContent class="space-y-4">
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
                        </div>

                        <!-- Features Liste -->
                        <div class="space-y-2">
                            <Label>Features (für Anzeige)</Label>
                            <div class="space-y-2">
                                <div
                                    v-for="(feature, index) in form.features"
                                    :key="index"
                                    class="flex gap-2"
                                >
                                    <Input
                                        v-model="form.features[index]"
                                        placeholder="z.B. KI-Antwortvorschläge"
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
                            >
                                <Plus class="mr-2 h-4 w-4" />
                                Feature hinzufügen
                            </Button>
                        </div>
                    </CardContent>
                </Card>

                <!-- Status -->
                <Card>
                    <CardHeader>
                        <CardTitle>Status</CardTitle>
                    </CardHeader>
                    <CardContent class="space-y-4">
                        <div class="flex items-center justify-between">
                            <div>
                                <Label>Aktiv</Label>
                                <p class="text-sm text-muted-foreground">
                                    Nur aktive Tarife werden angezeigt
                                </p>
                            </div>
                            <Switch v-model:checked="form.is_active" />
                        </div>

                        <div class="flex items-center justify-between">
                            <div>
                                <Label>Als "Beliebt" markieren</Label>
                                <p class="text-sm text-muted-foreground">
                                    Zeigt ein Badge in der Pricing Table
                                </p>
                            </div>
                            <Switch v-model:checked="form.is_popular" />
                        </div>

                        <div class="space-y-2">
                            <Label for="sort_order">Sortierung</Label>
                            <Input
                                id="sort_order"
                                v-model.number="form.sort_order"
                                type="number"
                                min="0"
                                max="100"
                                class="w-24"
                            />
                        </div>
                    </CardContent>
                </Card>

                <!-- Submit -->
                <div class="flex justify-end gap-2">
                    <Link href="/admin/plans">
                        <Button type="button" variant="outline">
                            Abbrechen
                        </Button>
                    </Link>
                    <Button type="submit" :disabled="form.processing">
                        <Save class="mr-2 h-4 w-4" />
                        {{ form.processing ? 'Speichern...' : 'Speichern' }}
                    </Button>
                </div>
            </form>
        </div>
    </AppLayout>
</template>
