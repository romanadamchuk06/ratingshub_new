<script setup>
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import StatsCard from '@/components/StatsCard.vue';
import { Bug, Clock, CheckCircle, AlertCircle, Search } from 'lucide-vue-next';
import { Badge } from '@/components/ui/badge';
import { Input } from '@/components/ui/input';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { ref, watch } from 'vue';

const props = defineProps({
    bugReports: {
        type: Object,
        required: true,
    },
    stats: {
        type: Object,
        required: true,
    },
    filters: {
        type: Object,
        default: () => ({}),
    },
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
    feature: '💡 Feature',
    improvement: '🔧 Verbesserung',
    question: '❓ Frage',
};

// Filter State
const statusFilter = ref(props.filters.status || 'all');
const typeFilter = ref(props.filters.type || 'all');
const priorityFilter = ref(props.filters.priority || 'all');
const searchQuery = ref(props.filters.search || '');

// Apply Filters
const applyFilters = () => {
    router.get('/admin/bug-reports', {
        status: statusFilter.value !== 'all' ? statusFilter.value : undefined,
        type: typeFilter.value !== 'all' ? typeFilter.value : undefined,
        priority: priorityFilter.value !== 'all' ? priorityFilter.value : undefined,
        search: searchQuery.value || undefined,
    }, {
        preserveState: true,
        preserveScroll: true,
    });
};

// Watch filters and apply with debounce for search
let searchTimeout;
watch(searchQuery, () => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(applyFilters, 500);
});

watch([statusFilter, typeFilter, priorityFilter], applyFilters);
</script>

<template>
    <Head title="Bug Reports" />

    <AppLayout>
        <div class="space-y-6 p-4 md:p-6 lg:p-8">
            <!-- Header -->
            <div>
                <h1 class="text-2xl font-bold tracking-tight md:text-3xl">Bug Reports</h1>
                <p class="text-muted-foreground">
                    Verwalte User-Feedback, Bugs und Feature Requests
                </p>
            </div>

            <!-- Stats -->
            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <StatsCard
                    title="Gesamt"
                    :value="stats.total.toString()"
                    :icon="Bug"
                    :loading="false"
                />
                <StatsCard
                    title="Offen"
                    :value="stats.open.toString()"
                    :icon="AlertCircle"
                    :loading="false"
                />
                <StatsCard
                    title="In Bearbeitung"
                    :value="stats.in_progress.toString()"
                    :icon="Clock"
                    :loading="false"
                />
                <StatsCard
                    title="Gelöst"
                    :value="stats.resolved.toString()"
                    :icon="CheckCircle"
                    :loading="false"
                />
            </div>

            <!-- Filters -->
            <div class="rounded-xl border bg-card p-6">
                <div class="grid gap-4 md:grid-cols-4">
                    <!-- Search -->
                    <div class="md:col-span-2">
                        <div class="relative">
                            <Search class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                            <Input
                                v-model="searchQuery"
                                placeholder="Suche nach Titel oder Beschreibung..."
                                class="pl-9"
                            />
                        </div>
                    </div>

                    <!-- Status Filter -->
                    <Select v-model="statusFilter">
                        <SelectTrigger>
                            <SelectValue placeholder="Status" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="all">Alle Status</SelectItem>
                            <SelectItem value="open">Offen</SelectItem>
                            <SelectItem value="in_progress">In Bearbeitung</SelectItem>
                            <SelectItem value="resolved">Gelöst</SelectItem>
                            <SelectItem value="closed">Geschlossen</SelectItem>
                        </SelectContent>
                    </Select>

                    <!-- Priority Filter -->
                    <Select v-model="priorityFilter">
                        <SelectTrigger>
                            <SelectValue placeholder="Priorität" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="all">Alle Prioritäten</SelectItem>
                            <SelectItem value="critical">Kritisch</SelectItem>
                            <SelectItem value="high">Hoch</SelectItem>
                            <SelectItem value="medium">Mittel</SelectItem>
                            <SelectItem value="low">Niedrig</SelectItem>
                        </SelectContent>
                    </Select>
                </div>
            </div>

            <!-- Bug Reports List -->
            <div class="rounded-xl border bg-card">
                <div class="border-b p-6">
                    <h2 class="text-lg font-semibold">Alle Bug Reports ({{ bugReports.total }})</h2>
                </div>
                <div class="p-6">
                    <div v-if="bugReports.data.length === 0" class="text-center py-8 text-muted-foreground">
                        Keine Bug Reports gefunden
                    </div>
                    <div v-else class="space-y-4">
                        <Link
                            v-for="report in bugReports.data"
                            :key="report.id"
                            :href="`/admin/bug-reports/${report.id}`"
                            class="block rounded-lg border p-4 hover:bg-accent/50 transition-colors"
                        >
                            <div class="flex items-start justify-between gap-4">
                                <div class="flex-1 space-y-2">
                                    <div class="flex items-center gap-2 flex-wrap">
                                        <span class="text-lg">{{ typeLabels[report.type] }}</span>
                                        <h3 class="font-semibold">{{ report.title }}</h3>
                                        <Badge :class="statusColors[report.status]">
                                            {{ statusLabels[report.status] }}
                                        </Badge>
                                        <Badge :class="priorityColors[report.priority]">
                                            {{ priorityLabels[report.priority] }}
                                        </Badge>
                                    </div>
                                    <p class="text-sm text-muted-foreground line-clamp-2">
                                        {{ report.description }}
                                    </p>
                                    <div class="flex items-center gap-4 text-xs text-muted-foreground">
                                        <span>Von: {{ report.user.name }}</span>
                                        <span>{{ new Date(report.created_at).toLocaleDateString('de-DE') }}</span>
                                        <span v-if="report.browser">{{ report.browser }}</span>
                                        <span v-if="report.os">{{ report.os }}</span>
                                        <span v-if="report.assigned_admin" class="text-primary">
                                            → {{ report.assigned_admin.name }}
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </Link>

                        <!-- Pagination -->
                        <div v-if="bugReports.links.length > 3" class="flex justify-center gap-2 pt-4">
                            <Link
                                v-for="(link, index) in bugReports.links"
                                :key="index"
                                :href="link.url"
                                :class="[
                                    'px-3 py-1 rounded border',
                                    link.active ? 'bg-primary text-primary-foreground' : 'hover:bg-accent',
                                    !link.url && 'opacity-50 cursor-not-allowed'
                                ]"
                                v-html="link.label"
                            />
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
