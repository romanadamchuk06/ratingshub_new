<script setup>
/**
 * ADMIN ACTIVITY LOGS - INDEX (READ-ONLY)
 * ========================================
 *
 * Zeigt alle Activity Logs im System.
 *
 * WICHTIG: KEINE Edit/Delete Buttons!
 * Logs sind IMMUTABLE (unveränderlich).
 *
 * Features:
 * - Filter nach Typ (User, Plan, Subscription, PromoCode)
 * - Filter nach Datum
 * - Suche in Beschreibung
 * - Ansicht der Änderungen (JSON)
 *
 * Warum read-only?
 * - Compliance (DSGVO, Audit-Anforderungen)
 * - Forensik (bei Security-Incidents)
 * - Vertrauen (User vertrauen auf unveränderliche Historie)
 */

import AppLayout from '@/layouts/AppLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Input } from '@/components/ui/input';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { FileText, Search, Filter } from 'lucide-vue-next';
import { ref, computed } from 'vue';

const props = defineProps({
    logs: {
        type: Array,
        required: true,
    },
    filters: {
        type: Object,
        required: true,
    },
});

// Filter State
const typeFilter = ref(props.filters.type || 'all');
const searchQuery = ref(props.filters.search || '');

// Details Dialog
const selectedLog = ref(null);
const showDetailsDialog = ref(false);

/**
 * Filter anwenden
 */
const applyFilters = () => {
    router.get('/admin/activity-logs', {
        type: typeFilter.value,
        search: searchQuery.value,
    }, {
        preserveState: true,
        preserveScroll: true,
    });
};

/**
 * Details anzeigen
 */
const showDetails = (log) => {
    selectedLog.value = log;
    showDetailsDialog.value = true;
};

/**
 * Badge-Variante je nach Log-Typ
 */
const getTypeBadgeVariant = (type) => {
    const variants = {
        user: 'default',
        plan: 'secondary',
        subscription: 'outline',
        promo_code: 'destructive',
    };
    return variants[type] || 'default';
};

/**
 * Badge-Variante je nach Aktion
 */
const getActionBadgeVariant = (action) => {
    if (action === 'created') return 'default';
    if (action === 'deleted') return 'destructive';
    if (action === 'updated') return 'secondary';
    return 'outline';
};

/**
 * Formatiere Datum
 */
const formatDate = (date) => {
    return new Date(date).toLocaleString('de-DE', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
};
</script>

<template>
    <Head title="Activity Logs" />

    <AppLayout>
        <div class="space-y-6 p-4 md:p-6 lg:p-8">
            <!-- Header -->
            <div>
                <h1 class="text-2xl font-bold tracking-tight md:text-3xl">
                    Activity Logs
                </h1>
                <p class="text-muted-foreground">
                    Unveränderliche Historie aller Systemänderungen (Read-Only)
                </p>
            </div>

            <!-- Filter -->
            <div class="flex flex-col gap-4 rounded-xl border bg-card p-4 md:flex-row">
                <!-- Typ-Filter -->
                <div class="flex-1">
                    <Select v-model="typeFilter" @update:model-value="applyFilters">
                        <SelectTrigger>
                            <SelectValue placeholder="Alle Typen" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="all">Alle Typen</SelectItem>
                            <SelectItem value="user">Benutzer</SelectItem>
                            <SelectItem value="plan">Pläne</SelectItem>
                            <SelectItem value="subscription">Subscriptions</SelectItem>
                            <SelectItem value="promo_code">Promo-Codes</SelectItem>
                        </SelectContent>
                    </Select>
                </div>

                <!-- Suche -->
                <div class="flex flex-1 gap-2">
                    <Input
                        v-model="searchQuery"
                        placeholder="In Beschreibung suchen..."
                        @keyup.enter="applyFilters"
                    >
                        <template #prefix>
                            <Search class="h-4 w-4" />
                        </template>
                    </Input>
                    <Button @click="applyFilters">
                        <Filter class="h-4 w-4" />
                    </Button>
                </div>
            </div>

            <!-- Logs Table -->
            <div class="rounded-xl border bg-card">
                <Table>
                    <TableHeader>
                        <TableRow>
                            <TableHead>Zeitpunkt</TableHead>
                            <TableHead>Typ</TableHead>
                            <TableHead>Aktion</TableHead>
                            <TableHead>Durchgeführt von</TableHead>
                            <TableHead>Ziel</TableHead>
                            <TableHead>Beschreibung</TableHead>
                            <TableHead class="text-right">Details</TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        <TableRow v-for="log in logs" :key="`${log.type}-${log.id}`">
                            <!-- Zeitpunkt -->
                            <TableCell class="font-mono text-xs">
                                {{ formatDate(log.created_at) }}
                            </TableCell>

                            <!-- Typ -->
                            <TableCell>
                                <Badge :variant="getTypeBadgeVariant(log.type)">
                                    {{ log.type_label }}
                                </Badge>
                            </TableCell>

                            <!-- Aktion -->
                            <TableCell>
                                <Badge :variant="getActionBadgeVariant(log.action)">
                                    {{ log.action_label }}
                                </Badge>
                            </TableCell>

                            <!-- Durchgeführt von -->
                            <TableCell>
                                {{ log.performed_by }}
                            </TableCell>

                            <!-- Ziel -->
                            <TableCell>
                                {{ log.target }}
                            </TableCell>

                            <!-- Beschreibung -->
                            <TableCell class="max-w-md truncate">
                                {{ log.description }}
                            </TableCell>

                            <!-- Details Button -->
                            <TableCell class="text-right">
                                <Button
                                    variant="ghost"
                                    size="sm"
                                    @click="showDetails(log)"
                                >
                                    <FileText class="h-4 w-4" />
                                </Button>
                            </TableCell>
                        </TableRow>

                        <!-- Empty State -->
                        <TableRow v-if="logs.length === 0">
                            <TableCell colspan="7" class="text-center text-muted-foreground">
                                Keine Logs gefunden
                            </TableCell>
                        </TableRow>
                    </TableBody>
                </Table>
            </div>

            <!-- Info-Box -->
            <div class="rounded-lg border border-amber-200 bg-amber-50 p-4 dark:border-amber-900 dark:bg-amber-950">
                <p class="text-sm text-amber-900 dark:text-amber-200">
                    <strong>🔒 Read-Only:</strong> Activity Logs können nicht bearbeitet oder gelöscht werden.
                    Sie dienen der Nachvollziehbarkeit und Compliance.
                </p>
            </div>
        </div>

        <!-- Details Dialog -->
        <Dialog v-model:open="showDetailsDialog">
            <DialogContent class="max-w-2xl">
                <DialogHeader>
                    <DialogTitle>Log Details</DialogTitle>
                    <DialogDescription>
                        Detaillierte Informationen zu diesem Activity Log
                    </DialogDescription>
                </DialogHeader>

                <div v-if="selectedLog" class="space-y-4">
                    <!-- Basis-Infos -->
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <p class="text-sm font-medium text-muted-foreground">Typ</p>
                            <Badge :variant="getTypeBadgeVariant(selectedLog.type)">
                                {{ selectedLog.type_label }}
                            </Badge>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-muted-foreground">Aktion</p>
                            <Badge :variant="getActionBadgeVariant(selectedLog.action)">
                                {{ selectedLog.action_label }}
                            </Badge>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-muted-foreground">Durchgeführt von</p>
                            <p class="text-sm">{{ selectedLog.performed_by }}</p>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-muted-foreground">Ziel</p>
                            <p class="text-sm">{{ selectedLog.target }}</p>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-muted-foreground">Zeitpunkt</p>
                            <p class="text-sm">{{ formatDate(selectedLog.created_at) }}</p>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-muted-foreground">IP-Adresse</p>
                            <p class="font-mono text-sm">{{ selectedLog.ip_address || 'N/A' }}</p>
                        </div>
                    </div>

                    <!-- Beschreibung -->
                    <div>
                        <p class="text-sm font-medium text-muted-foreground">Beschreibung</p>
                        <p class="text-sm">{{ selectedLog.description }}</p>
                    </div>

                    <!-- Änderungen (JSON) -->
                    <div v-if="selectedLog.changes">
                        <p class="mb-2 text-sm font-medium text-muted-foreground">Änderungen</p>
                        <pre class="overflow-auto rounded-lg border bg-muted p-4 text-xs">{{ JSON.stringify(selectedLog.changes, null, 2) }}</pre>
                    </div>
                </div>
            </DialogContent>
        </Dialog>
    </AppLayout>
</template>
