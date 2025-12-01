<script setup>
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Bug, Lightbulb, Wrench, HelpCircle } from 'lucide-vue-next';
import { store, myReports } from '@/routes/bug-reports';
import { dashboard } from '@/routes';

const form = useForm({
    title: '',
    description: '',
    type: 'bug',
    steps_to_reproduce: '',
});

const submit = () => {
    form.post(store.url(), {
        onSuccess: () => {
            router.visit(myReports.url());
        },
    });
};

const typeIcons = {
    bug: Bug,
    feature: Lightbulb,
    improvement: Wrench,
    question: HelpCircle,
};
</script>

<template>
    <Head title="Bug melden" />

    <AppLayout>
        <div class="mx-auto max-w-3xl space-y-6 p-4 md:p-6 lg:p-8">
            <!-- Header -->
            <div>
                <h1 class="text-2xl font-bold tracking-tight md:text-3xl">Bug melden</h1>
                <p class="text-muted-foreground">
                    Hilf uns, RatingsHub zu verbessern! Melde Bugs, schlage Features vor oder stelle Fragen.
                </p>
            </div>

            <!-- Form -->
            <div class="rounded-xl border bg-card">
                <form @submit.prevent="submit" class="p-6 space-y-6">
                    <!-- Type Selection -->
                    <div class="space-y-2">
                        <Label for="type">Was möchtest du melden?</Label>
                        <Select v-model="form.type">
                            <SelectTrigger id="type">
                                <SelectValue placeholder="Wähle eine Kategorie" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="bug">
                                    <div class="flex items-center gap-2">
                                        <Bug class="h-4 w-4" />
                                        <span>Bug (etwas funktioniert nicht)</span>
                                    </div>
                                </SelectItem>
                                <SelectItem value="feature">
                                    <div class="flex items-center gap-2">
                                        <Lightbulb class="h-4 w-4" />
                                        <span>Feature Request (neue Funktion)</span>
                                    </div>
                                </SelectItem>
                                <SelectItem value="improvement">
                                    <div class="flex items-center gap-2">
                                        <Wrench class="h-4 w-4" />
                                        <span>Verbesserung (etwas optimieren)</span>
                                    </div>
                                </SelectItem>
                                <SelectItem value="question">
                                    <div class="flex items-center gap-2">
                                        <HelpCircle class="h-4 w-4" />
                                        <span>Frage (Hilfe benötigt)</span>
                                    </div>
                                </SelectItem>
                            </SelectContent>
                        </Select>
                        <p v-if="form.errors.type" class="text-sm text-destructive">
                            {{ form.errors.type }}
                        </p>
                    </div>

                    <!-- Title -->
                    <div class="space-y-2">
                        <Label for="title">Titel</Label>
                        <Input
                            id="title"
                            v-model="form.title"
                            placeholder="Kurze Beschreibung des Problems"
                            :class="{ 'border-destructive': form.errors.title }"
                        />
                        <p v-if="form.errors.title" class="text-sm text-destructive">
                            {{ form.errors.title }}
                        </p>
                    </div>

                    <!-- Description -->
                    <div class="space-y-2">
                        <Label for="description">Beschreibung</Label>
                        <Textarea
                            id="description"
                            v-model="form.description"
                            rows="6"
                            placeholder="Beschreibe das Problem so detailliert wie möglich..."
                            :class="{ 'border-destructive': form.errors.description }"
                        />
                        <p v-if="form.errors.description" class="text-sm text-destructive">
                            {{ form.errors.description }}
                        </p>
                        <p class="text-xs text-muted-foreground">
                            Mindestens 10 Zeichen
                        </p>
                    </div>

                    <!-- Steps to Reproduce (optional, nur bei Bug) -->
                    <div v-if="form.type === 'bug'" class="space-y-2">
                        <Label for="steps">Schritte zum Reproduzieren (optional)</Label>
                        <Textarea
                            id="steps"
                            v-model="form.steps_to_reproduce"
                            rows="4"
                            placeholder="1. Gehe zu...&#10;2. Klicke auf...&#10;3. Bug tritt auf..."
                        />
                        <p class="text-xs text-muted-foreground">
                            Hilft uns, den Bug schneller zu finden und zu beheben
                        </p>
                    </div>

                    <!-- Info Box -->
                    <div class="rounded-lg bg-muted p-4 text-sm">
                        <p class="font-medium mb-2">ℹ️ Automatisch erfasste Informationen:</p>
                        <ul class="space-y-1 text-muted-foreground">
                            <li>• Dein Browser und Betriebssystem</li>
                            <li>• Die Seite auf der du dich befindest</li>
                            <li>• Zeitpunkt der Meldung</li>
                        </ul>
                    </div>

                    <!-- Actions -->
                    <div class="flex justify-end gap-3">
                        <Button
                            type="button"
                            variant="outline"
                            @click="$inertia.visit(dashboard.url())"
                        >
                            Abbrechen
                        </Button>
                        <Button type="submit" :disabled="form.processing">
                            {{ form.processing ? 'Wird gesendet...' : 'Bug melden' }}
                        </Button>
                    </div>
                </form>
            </div>

            <!-- My Reports Link -->
            <div class="text-center">
                <a
                    :href="myReports.url()"
                    class="text-sm text-primary hover:underline"
                >
                    Meine Bug-Reports ansehen
                </a>
            </div>
        </div>
    </AppLayout>
</template>
