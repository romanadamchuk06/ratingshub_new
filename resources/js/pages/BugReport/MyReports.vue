<script setup>
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import EmptyState from '@/components/EmptyState.vue';
import { Bug, Plus } from 'lucide-vue-next';
import { create } from '@/routes/bug-reports';

const props = defineProps({
    bugReports: {
        type: Object,
        required: true,
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
</script>

<template>
    <Head title="Meine Bug-Reports" />

    <AppLayout>
        <div class="space-y-6 p-4 md:p-6 lg:p-8">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold tracking-tight md:text-3xl">Meine Bug-Reports</h1>
                    <p class="text-muted-foreground">
                        Verfolge den Status deiner gemeldeten Bugs und Feature Requests
                    </p>
                </div>
                <Link :href="create.url()">
                    <Button>
                        <Plus class="mr-2 h-4 w-4" />
                        Neuer Report
                    </Button>
                </Link>
            </div>

            <!-- Bug Reports List -->
            <div class="rounded-xl border bg-card">
                <div class="p-6">
                    <!-- Empty State -->
                    <EmptyState
                        v-if="bugReports.data.length === 0"
                        :icon="Bug"
                        title="Keine Bug-Reports"
                        description="Du hast noch keine Bugs gemeldet. Hilf uns, RatingsHub zu verbessern!"
                        actionText="Bug melden"
                        :actionHref="create.url()"
                    />

                    <!-- Reports -->
                    <div v-else class="space-y-4">
                        <div
                            v-for="report in bugReports.data"
                            :key="report.id"
                            class="rounded-lg border p-4 hover:bg-accent/50 transition-colors"
                        >
                            <div class="flex items-start justify-between gap-4">
                                <div class="flex-1 space-y-2">
                                    <div class="flex items-center gap-2">
                                        <h3 class="font-semibold">{{ report.title }}</h3>
                                        <Badge :class="statusColors[report.status]">
                                            {{ statusLabels[report.status] }}
                                        </Badge>
                                    </div>
                                    <p class="text-sm text-muted-foreground line-clamp-2">
                                        {{ report.description }}
                                    </p>
                                    <div class="flex items-center gap-4 text-xs text-muted-foreground">
                                        <span>{{ new Date(report.created_at).toLocaleDateString('de-DE') }}</span>
                                        <span v-if="report.assigned_admin" class="flex items-center gap-1">
                                            Zugewiesen an: {{ report.assigned_admin.name }}
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
