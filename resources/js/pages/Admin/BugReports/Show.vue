<script setup>
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, useForm, Link, router } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { ArrowLeft, Save, Trash2, User, Calendar, Globe, Monitor, ExternalLink } from 'lucide-vue-next';
import { ref } from 'vue';

const props = defineProps({
    bugReport: {
        type: Object,
        required: true,
    },
    admins: {
        type: Array,
        required: true,
    },
});

const form = useForm({
    status: props.bugReport.status,
    priority: props.bugReport.priority,
    assigned_to: props.bugReport.assigned_to ? props.bugReport.assigned_to.toString() : '',
    admin_notes: props.bugReport.admin_notes || '',
});

const statusColors = {
    open: 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300',
    in_progress: 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-300',
    resolved: 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300',
    closed: 'bg-gray-100 text-gray-800 dark:bg-gray-800 dark:text-gray-300',
};

const statusLabels = {
    open: 'Offen',
    in_progress: 'In Bearbeitung',
    resolved: 'Gelöst',
    closed: 'Geschlossen',
};

const priorityColors = {
    low: 'bg-gray-100 text-gray-800 dark:bg-gray-800 dark:text-gray-300',
    medium: 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-300',
    high: 'bg-orange-100 text-orange-800 dark:bg-orange-900 dark:text-orange-300',
    critical: 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300',
};

const priorityLabels = {
    low: 'Niedrig',
    medium: 'Mittel',
    high: 'Hoch',
    critical: 'Kritisch',
};

const typeLabels = {
    bug: '🐛 Bug',
    feature: '💡 Feature Request',
    improvement: '🔧 Verbesserung',
    question: '❓ Frage',
};

const updateReport = () => {
    form.patch(`/admin/bug-reports/${props.bugReport.id}`, {
        preserveScroll: true,
        onSuccess: () => {
            // Form wird automatisch resettet
        },
        onError: (errors) => {
            console.error('Fehler beim Aktualisieren:', errors);
        },
    });
};

const deleteReport = () => {
    if (confirm('Bist du sicher, dass du diesen Bug-Report löschen möchtest?')) {
        router.delete(`/admin/bug-reports/${props.bugReport.id}`);
    }
};
</script>

<template>
    <Head :title="`Bug-Report #${bugReport.id}`" />

    <AppLayout>
        <div class="space-y-6 p-4 md:p-6 lg:p-8">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-4">
                    <Link href="/admin/bug-reports">
                        <Button variant="outline" size="sm">
                            <ArrowLeft class="h-4 w-4 mr-2" />
                            Zurück
                        </Button>
                    </Link>
                    <div>
                        <h1 class="text-2xl font-bold tracking-tight md:text-3xl">
                            Bug-Report #{{ bugReport.id }}
                        </h1>
                        <p class="text-muted-foreground">
                            Erstellt am {{ new Date(bugReport.created_at).toLocaleDateString('de-DE', { dateStyle: 'long' }) }}
                        </p>
                    </div>
                </div>
                <Button variant="destructive" size="sm" @click="deleteReport">
                    <Trash2 class="h-4 w-4 mr-2" />
                    Löschen
                </Button>
            </div>

            <div class="grid gap-6 lg:grid-cols-3">
                <!-- Main Content -->
                <div class="lg:col-span-2 space-y-6">
                    <!-- Report Details -->
                    <div class="rounded-xl border bg-card">
                        <div class="border-b p-6">
                            <div class="flex items-center gap-2 flex-wrap mb-2">
                                <span class="text-2xl">{{ typeLabels[bugReport.type] }}</span>
                                <Badge :class="statusColors[bugReport.status]">
                                    {{ statusLabels[bugReport.status] }}
                                </Badge>
                                <Badge :class="priorityColors[bugReport.priority]">
                                    {{ priorityLabels[bugReport.priority] }}
                                </Badge>
                            </div>
                            <h2 class="text-xl font-semibold">{{ bugReport.title }}</h2>
                        </div>

                        <div class="p-6 space-y-6">
                            <!-- Description -->
                            <div>
                                <h3 class="text-sm font-semibold mb-2">Beschreibung</h3>
                                <p class="text-muted-foreground whitespace-pre-wrap">{{ bugReport.description }}</p>
                            </div>

                            <!-- Steps to Reproduce -->
                            <div v-if="bugReport.steps_to_reproduce" class="border-t pt-6">
                                <h3 class="text-sm font-semibold mb-2">Schritte zum Reproduzieren</h3>
                                <p class="text-muted-foreground whitespace-pre-wrap">{{ bugReport.steps_to_reproduce }}</p>
                            </div>

                            <!-- Technical Details -->
                            <div class="border-t pt-6">
                                <h3 class="text-sm font-semibold mb-3">Technische Details</h3>
                                <div class="grid gap-3 sm:grid-cols-2">
                                    <div v-if="bugReport.browser" class="flex items-center gap-2 text-sm">
                                        <Monitor class="h-4 w-4 text-muted-foreground" />
                                        <span class="text-muted-foreground">Browser:</span>
                                        <span class="font-medium">{{ bugReport.browser }}</span>
                                    </div>
                                    <div v-if="bugReport.os" class="flex items-center gap-2 text-sm">
                                        <Monitor class="h-4 w-4 text-muted-foreground" />
                                        <span class="text-muted-foreground">OS:</span>
                                        <span class="font-medium">{{ bugReport.os }}</span>
                                    </div>
                                    <div v-if="bugReport.page_url" class="sm:col-span-2 flex items-center gap-2 text-sm">
                                        <Globe class="h-4 w-4 text-muted-foreground" />
                                        <span class="text-muted-foreground">Seite:</span>
                                        <a :href="bugReport.page_url" target="_blank" class="text-primary hover:underline flex items-center gap-1">
                                            {{ bugReport.page_url }}
                                            <ExternalLink class="h-3 w-3" />
                                        </a>
                                    </div>
                                </div>
                            </div>

                            <!-- User Info -->
                            <div class="border-t pt-6">
                                <h3 class="text-sm font-semibold mb-3">Gemeldet von</h3>
                                <div class="flex items-center gap-3">
                                    <div class="h-10 w-10 rounded-full bg-primary/10 flex items-center justify-center">
                                        <User class="h-5 w-5 text-primary" />
                                    </div>
                                    <div>
                                        <p class="font-medium">{{ bugReport.user.name }}</p>
                                        <p class="text-sm text-muted-foreground">{{ bugReport.user.email }}</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Sidebar -->
                <div class="space-y-6">
                    <!-- Management Form -->
                    <div class="rounded-xl border bg-card">
                        <div class="border-b p-6">
                            <h3 class="font-semibold">Ticket verwalten</h3>
                        </div>
                        <form @submit.prevent="updateReport" class="p-6 space-y-4">
                            <!-- Status -->
                            <div class="space-y-2">
                                <Label for="status">Status</Label>
                                <Select v-model="form.status">
                                    <SelectTrigger id="status">
                                        <SelectValue />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="open">Offen</SelectItem>
                                        <SelectItem value="in_progress">In Bearbeitung</SelectItem>
                                        <SelectItem value="resolved">Gelöst</SelectItem>
                                        <SelectItem value="closed">Geschlossen</SelectItem>
                                    </SelectContent>
                                </Select>
                                <p v-if="form.errors.status" class="text-sm text-destructive">
                                    {{ form.errors.status }}
                                </p>
                            </div>

                            <!-- Priority -->
                            <div class="space-y-2">
                                <Label for="priority">Priorität</Label>
                                <Select v-model="form.priority">
                                    <SelectTrigger id="priority">
                                        <SelectValue />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="critical">Kritisch</SelectItem>
                                        <SelectItem value="high">Hoch</SelectItem>
                                        <SelectItem value="medium">Mittel</SelectItem>
                                        <SelectItem value="low">Niedrig</SelectItem>
                                    </SelectContent>
                                </Select>
                                <p v-if="form.errors.priority" class="text-sm text-destructive">
                                    {{ form.errors.priority }}
                                </p>
                            </div>

                            <!-- Assign to Admin -->
                            <div class="space-y-2">
                                <Label for="assigned_to">Zuweisen an</Label>
                                <Select v-model="form.assigned_to">
                                    <SelectTrigger id="assigned_to">
                                        <SelectValue placeholder="Nicht zugewiesen" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="">Nicht zugewiesen</SelectItem>
                                        <SelectItem
                                            v-for="admin in admins"
                                            :key="admin.id"
                                            :value="admin.id.toString()"
                                        >
                                            {{ admin.name }}
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                                <p v-if="form.errors.assigned_to" class="text-sm text-destructive">
                                    {{ form.errors.assigned_to }}
                                </p>
                            </div>

                            <!-- Admin Notes -->
                            <div class="space-y-2">
                                <Label for="admin_notes">Admin-Notizen</Label>
                                <Textarea
                                    id="admin_notes"
                                    v-model="form.admin_notes"
                                    rows="4"
                                    placeholder="Interne Notizen (nur für Admins sichtbar)..."
                                />
                                <p v-if="form.errors.admin_notes" class="text-sm text-destructive">
                                    {{ form.errors.admin_notes }}
                                </p>
                            </div>

                            <!-- Save Button -->
                            <Button type="submit" class="w-full" :disabled="form.processing">
                                <Save class="h-4 w-4 mr-2" />
                                {{ form.processing ? 'Wird gespeichert...' : 'Änderungen speichern' }}
                            </Button>
                        </form>
                    </div>

                    <!-- Timeline -->
                    <div v-if="bugReport.resolved_at" class="rounded-xl border bg-card p-6">
                        <h3 class="font-semibold mb-4">Timeline</h3>
                        <div class="space-y-4">
                            <div class="flex gap-3">
                                <div class="mt-1">
                                    <div class="h-2 w-2 rounded-full bg-blue-500" />
                                </div>
                                <div class="text-sm">
                                    <p class="font-medium">Erstellt</p>
                                    <p class="text-muted-foreground">
                                        {{ new Date(bugReport.created_at).toLocaleString('de-DE', { dateStyle: 'medium', timeStyle: 'short' }) }}
                                    </p>
                                </div>
                            </div>
                            <div class="flex gap-3">
                                <div class="mt-1">
                                    <div class="h-2 w-2 rounded-full bg-green-500" />
                                </div>
                                <div class="text-sm">
                                    <p class="font-medium">Gelöst</p>
                                    <p class="text-muted-foreground">
                                        {{ new Date(bugReport.resolved_at).toLocaleString('de-DE', { dateStyle: 'medium', timeStyle: 'short' }) }}
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
