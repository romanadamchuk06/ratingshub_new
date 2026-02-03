<script setup>
/**
 * ADMIN PROMO CODE MANAGEMENT
 * ===========================
 *
 * Übersicht aller Promo Codes mit:
 * - Statistiken (Gesamt, Aktiv, Verwendungen, etc.)
 * - Suche & Filter
 * - Tabellenansicht
 * - Schnelle Aktionen (Aktivieren/Deaktivieren, Löschen)
 * - Letzte Aktivitäten
 */

import AppLayout from '@/layouts/AppLayout.vue';
import { Head, router, Link } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
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
    DialogFooter,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select/index';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import {
    Ticket,
    Plus,
    Trash2,
    ToggleLeft,
    ToggleRight,
    Search,
    MoreHorizontal,
    CheckCircle,
    XCircle,
    Clock,
    Percent,
    Euro,
    X,
    Copy
} from 'lucide-vue-next';
import { ref, computed, watch } from 'vue';
import { useDebounceFn } from '@vueuse/core';

const props = defineProps({
    promoCodes: Object,
    stats: Object,
    recentActivity: Array,
    filters: Object,
});

// Filter State
const search = ref(props.filters?.search || '');
const statusFilter = ref(props.filters?.status || '');
const typeFilter = ref(props.filters?.type || '');

// Dialog State
const showCreateDialog = ref(false);
const newCode = ref({
    code: '',
    type: 'percentage',
    value: 0,
    max_uses: null,
    expires_at: null,
    description: '',
});

// Debounced Suche
const debouncedSearch = useDebounceFn(() => {
    applyFilters();
}, 300);

watch(search, () => {
    debouncedSearch();
});

const applyFilters = () => {
    router.get('/admin/promo-codes', {
        search: search.value || undefined,
        status: statusFilter.value || undefined,
        type: typeFilter.value || undefined,
    }, {
        preserveState: true,
        preserveScroll: true,
    });
};

const clearFilters = () => {
    search.value = '';
    statusFilter.value = '';
    typeFilter.value = '';
    router.get('/admin/promo-codes');
};

const hasActiveFilters = computed(() => {
    return search.value || statusFilter.value || typeFilter.value;
});

// Code generieren
const generateCode = () => {
    const chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
    let result = '';
    for (let i = 0; i < 8; i++) {
        result += chars.charAt(Math.floor(Math.random() * chars.length));
    }
    newCode.value.code = result;
};

const createPromoCode = () => {
    router.post('/admin/promo-codes', newCode.value, {
        onSuccess: () => {
            showCreateDialog.value = false;
            newCode.value = {
                code: '',
                type: 'percentage',
                value: 0,
                max_uses: null,
                expires_at: null,
                description: '',
            };
        },
    });
};

const toggleActive = (promoCode) => {
    router.patch(`/admin/promo-codes/${promoCode.id}`, {
        is_active: !promoCode.is_active,
    }, {
        preserveScroll: true,
    });
};

const deletePromoCode = (promoCode) => {
    if (!confirm(`Möchtest du den Promo Code "${promoCode.code}" wirklich löschen?`)) return;

    router.delete(`/admin/promo-codes/${promoCode.id}`, {
        preserveScroll: true,
    });
};

const copyToClipboard = (code) => {
    navigator.clipboard.writeText(code);
};

const getStatus = (code) => {
    if (!code.is_active) {
        return { text: 'Inaktiv', variant: 'secondary', icon: XCircle };
    }
    if (code.expires_at && new Date(code.expires_at) < new Date()) {
        return { text: 'Abgelaufen', variant: 'destructive', icon: Clock };
    }
    if (code.max_uses && code.usages_count >= code.max_uses) {
        return { text: 'Aufgebraucht', variant: 'outline', icon: XCircle };
    }
    return { text: 'Aktiv', variant: 'default', icon: CheckCircle };
};

const formatDate = (dateString) => {
    if (!dateString) return '-';
    return new Date(dateString).toLocaleDateString('de-DE', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
    });
};

const formatDiscount = (code) => {
    if (code.type === 'percentage') {
        return `${code.value}%`;
    }
    return `${Number(code.value).toFixed(2).replace('.', ',')} €`;
};

const formatActivityAction = (action) => {
    const actions = {
        'created': 'Erstellt',
        'updated': 'Aktualisiert',
        'toggled_active': 'Status geändert',
        'deleted': 'Gelöscht',
        'used': 'Verwendet',
    };
    return actions[action] || action;
};
</script>

<template>
    <Head title="Promo Codes verwalten" />

    <AppLayout :breadcrumbs="[
        { label: 'Admin', href: '/admin' },
        { label: 'Promo Codes', href: '/admin/promo-codes' }
    ]">
        <div class="space-y-6 p-4 md:p-6 lg:p-8">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold tracking-tight md:text-3xl">Promo Codes</h1>
                    <p class="text-muted-foreground">Verwalte Rabattcodes für Subscriptions</p>
                </div>

                <Dialog v-model:open="showCreateDialog">
                    <DialogTrigger as-child>
                        <Button>
                            <Plus class="mr-2 h-4 w-4" />
                            Neuer Promo Code
                        </Button>
                    </DialogTrigger>
                    <DialogContent>
                        <DialogHeader>
                            <DialogTitle>Neuen Promo Code erstellen</DialogTitle>
                            <DialogDescription>
                                Erstelle einen neuen Rabattcode für Kunden
                            </DialogDescription>
                        </DialogHeader>

                        <div class="space-y-4">
                            <div class="space-y-2">
                                <Label for="code">Code</Label>
                                <div class="flex gap-2">
                                    <Input
                                        id="code"
                                        v-model="newCode.code"
                                        placeholder="z.B. SOMMER2024"
                                        class="flex-1 uppercase"
                                    />
                                    <Button variant="outline" size="icon" @click="generateCode" type="button">
                                        <Ticket class="h-4 w-4" />
                                    </Button>
                                </div>
                            </div>

                            <div class="grid grid-cols-2 gap-4">
                                <div class="space-y-2">
                                    <Label for="type">Typ</Label>
                                    <Select :model-value="newCode.type" @update:model-value="(v) => newCode.type = v">
                                        <SelectTrigger>
                                            <SelectValue placeholder="Typ wählen" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="percentage">Prozent</SelectItem>
                                            <SelectItem value="fixed">Fester Betrag</SelectItem>
                                        </SelectContent>
                                    </Select>
                                </div>

                                <div class="space-y-2">
                                    <Label for="value">
                                        {{ newCode.type === 'percentage' ? 'Prozent' : 'Betrag (€)' }}
                                    </Label>
                                    <Input
                                        id="value"
                                        v-model="newCode.value"
                                        type="number"
                                        min="0"
                                        :max="newCode.type === 'percentage' ? 100 : undefined"
                                    />
                                </div>
                            </div>

                            <div class="grid grid-cols-2 gap-4">
                                <div class="space-y-2">
                                    <Label for="max_uses">Max. Verwendungen</Label>
                                    <Input
                                        id="max_uses"
                                        v-model="newCode.max_uses"
                                        type="number"
                                        min="1"
                                        placeholder="Unbegrenzt"
                                    />
                                </div>

                                <div class="space-y-2">
                                    <Label for="expires_at">Ablaufdatum</Label>
                                    <Input
                                        id="expires_at"
                                        v-model="newCode.expires_at"
                                        type="date"
                                    />
                                </div>
                            </div>

                            <div class="space-y-2">
                                <Label for="description">Beschreibung</Label>
                                <Input
                                    id="description"
                                    v-model="newCode.description"
                                    placeholder="Optionale Beschreibung"
                                />
                            </div>
                        </div>

                        <DialogFooter>
                            <Button variant="outline" @click="showCreateDialog = false">
                                Abbrechen
                            </Button>
                            <Button @click="createPromoCode">
                                Erstellen
                            </Button>
                        </DialogFooter>
                    </DialogContent>
                </Dialog>
            </div>

            <!-- Stats Cards -->
            <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
                <Card>
                    <CardHeader class="flex flex-row items-center justify-between pb-2">
                        <CardTitle class="text-sm font-medium">Gesamt Codes</CardTitle>
                        <Ticket class="h-4 w-4 text-muted-foreground" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold">{{ stats.totalCodes }}</div>
                    </CardContent>
                </Card>
                <Card>
                    <CardHeader class="flex flex-row items-center justify-between pb-2">
                        <CardTitle class="text-sm font-medium">Aktive Codes</CardTitle>
                        <CheckCircle class="h-4 w-4 text-green-600" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold text-green-600">{{ stats.activeCodes }}</div>
                        <p class="text-xs text-muted-foreground">
                            {{ stats.expiredCodes }} abgelaufen
                        </p>
                    </CardContent>
                </Card>
                <Card>
                    <CardHeader class="flex flex-row items-center justify-between pb-2">
                        <CardTitle class="text-sm font-medium">Verwendungen</CardTitle>
                        <Percent class="h-4 w-4 text-muted-foreground" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold">{{ stats.totalUsages }}</div>
                        <p class="text-xs text-muted-foreground">
                            Gesamt eingelöst
                        </p>
                    </CardContent>
                </Card>
                <Card>
                    <CardHeader class="flex flex-row items-center justify-between pb-2">
                        <CardTitle class="text-sm font-medium">Inaktive Codes</CardTitle>
                        <XCircle class="h-4 w-4 text-muted-foreground" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold">{{ stats.totalCodes - stats.activeCodes }}</div>
                        <p class="text-xs text-muted-foreground">
                            Deaktiviert oder abgelaufen
                        </p>
                    </CardContent>
                </Card>
            </div>

            <!-- Filters -->
            <Card>
                <CardContent class="pt-6">
                    <div class="flex flex-col gap-4 md:flex-row md:items-center">
                        <!-- Search -->
                        <div class="relative flex-1">
                            <Search class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                            <Input
                                v-model="search"
                                placeholder="Suche nach Code oder Beschreibung..."
                                class="pl-10"
                            />
                        </div>

                        <!-- Status Filter -->
                        <Select v-model="statusFilter" @update:model-value="applyFilters">
                            <SelectTrigger class="w-[160px]">
                                <SelectValue placeholder="Alle Status" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="">Alle Status</SelectItem>
                                <SelectItem value="active">Aktiv</SelectItem>
                                <SelectItem value="inactive">Inaktiv</SelectItem>
                                <SelectItem value="expired">Abgelaufen</SelectItem>
                            </SelectContent>
                        </Select>

                        <!-- Type Filter -->
                        <Select v-model="typeFilter" @update:model-value="applyFilters">
                            <SelectTrigger class="w-[160px]">
                                <SelectValue placeholder="Alle Typen" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="">Alle Typen</SelectItem>
                                <SelectItem value="percentage">Prozentual</SelectItem>
                                <SelectItem value="fixed">Fester Betrag</SelectItem>
                            </SelectContent>
                        </Select>

                        <!-- Clear Filters -->
                        <Button
                            v-if="hasActiveFilters"
                            variant="ghost"
                            size="icon"
                            @click="clearFilters"
                        >
                            <X class="h-4 w-4" />
                        </Button>
                    </div>
                </CardContent>
            </Card>

            <!-- Promo Codes Table -->
            <Card>
                <CardHeader>
                    <CardTitle>Promo Codes ({{ promoCodes.total }})</CardTitle>
                    <CardDescription>
                        Seite {{ promoCodes.current_page }} von {{ promoCodes.last_page }}
                    </CardDescription>
                </CardHeader>
                <CardContent>
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead>Code</TableHead>
                                <TableHead>Rabatt</TableHead>
                                <TableHead>Verwendet</TableHead>
                                <TableHead>Status</TableHead>
                                <TableHead>Ablaufdatum</TableHead>
                                <TableHead class="text-right">Aktionen</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            <TableRow v-for="code in promoCodes.data" :key="code.id">
                                <!-- Code -->
                                <TableCell>
                                    <div class="flex items-center gap-2">
                                        <code class="rounded bg-muted px-2 py-1 font-mono text-sm font-semibold">
                                            {{ code.code }}
                                        </code>
                                        <Button
                                            variant="ghost"
                                            size="icon"
                                            class="h-6 w-6"
                                            @click="copyToClipboard(code.code)"
                                        >
                                            <Copy class="h-3 w-3" />
                                        </Button>
                                    </div>
                                    <p v-if="code.description" class="mt-1 text-xs text-muted-foreground">
                                        {{ code.description }}
                                    </p>
                                </TableCell>

                                <!-- Discount -->
                                <TableCell>
                                    <div class="flex items-center gap-1">
                                        <component :is="code.type === 'percentage' ? Percent : Euro" class="h-3 w-3 text-muted-foreground" />
                                        <span class="font-medium">{{ formatDiscount(code) }}</span>
                                    </div>
                                </TableCell>

                                <!-- Usage -->
                                <TableCell>
                                    <span class="font-medium">{{ code.usages_count }}</span>
                                    <span v-if="code.max_uses" class="text-muted-foreground">
                                        / {{ code.max_uses }}
                                    </span>
                                    <span v-else class="text-xs text-muted-foreground"> (unbegrenzt)</span>
                                </TableCell>

                                <!-- Status -->
                                <TableCell>
                                    <Badge :variant="getStatus(code).variant">
                                        <component :is="getStatus(code).icon" class="mr-1 h-3 w-3" />
                                        {{ getStatus(code).text }}
                                    </Badge>
                                </TableCell>

                                <!-- Expiry -->
                                <TableCell>
                                    {{ formatDate(code.expires_at) }}
                                </TableCell>

                                <!-- Actions -->
                                <TableCell class="text-right">
                                    <DropdownMenu>
                                        <DropdownMenuTrigger as-child>
                                            <Button variant="ghost" size="icon">
                                                <MoreHorizontal class="h-4 w-4" />
                                            </Button>
                                        </DropdownMenuTrigger>
                                        <DropdownMenuContent align="end">
                                            <DropdownMenuItem @click="toggleActive(code)">
                                                <component
                                                    :is="code.is_active ? ToggleLeft : ToggleRight"
                                                    class="mr-2 h-4 w-4"
                                                />
                                                {{ code.is_active ? 'Deaktivieren' : 'Aktivieren' }}
                                            </DropdownMenuItem>

                                            <DropdownMenuSeparator />

                                            <DropdownMenuItem
                                                class="text-destructive"
                                                @click="deletePromoCode(code)"
                                            >
                                                <Trash2 class="mr-2 h-4 w-4" />
                                                Löschen
                                            </DropdownMenuItem>
                                        </DropdownMenuContent>
                                    </DropdownMenu>
                                </TableCell>
                            </TableRow>

                            <TableRow v-if="promoCodes.data.length === 0">
                                <TableCell colspan="6" class="py-8 text-center">
                                    <div class="flex flex-col items-center gap-2">
                                        <Ticket class="h-8 w-8 text-muted-foreground" />
                                        <p class="text-muted-foreground">Keine Promo Codes gefunden.</p>
                                        <Button variant="outline" size="sm" @click="showCreateDialog = true">
                                            <Plus class="mr-2 h-4 w-4" />
                                            Ersten Code erstellen
                                        </Button>
                                    </div>
                                </TableCell>
                            </TableRow>
                        </TableBody>
                    </Table>

                    <!-- Pagination -->
                    <div v-if="promoCodes.last_page > 1" class="mt-4 flex items-center justify-between">
                        <p class="text-sm text-muted-foreground">
                            {{ promoCodes.from }} - {{ promoCodes.to }} von {{ promoCodes.total }} Codes
                        </p>
                        <div class="flex gap-2">
                            <Link
                                v-if="promoCodes.prev_page_url"
                                :href="promoCodes.prev_page_url"
                                preserve-scroll
                            >
                                <Button variant="outline" size="sm">Zurück</Button>
                            </Link>
                            <Link
                                v-if="promoCodes.next_page_url"
                                :href="promoCodes.next_page_url"
                                preserve-scroll
                            >
                                <Button variant="outline" size="sm">Weiter</Button>
                            </Link>
                        </div>
                    </div>
                </CardContent>
            </Card>

            <!-- Recent Activity -->
            <Card v-if="recentActivity && recentActivity.length > 0">
                <CardHeader>
                    <CardTitle>Letzte Aktivitäten</CardTitle>
                    <CardDescription>Die letzten Promo-Code-Änderungen</CardDescription>
                </CardHeader>
                <CardContent>
                    <div class="space-y-4">
                        <div
                            v-for="activity in recentActivity"
                            :key="activity.id"
                            class="flex items-start gap-3 text-sm"
                        >
                            <div class="flex h-8 w-8 items-center justify-center rounded-full bg-muted">
                                <Ticket class="h-4 w-4" />
                            </div>
                            <div class="flex-1">
                                <p>
                                    <code class="rounded bg-muted px-1 font-mono text-xs">
                                        {{ activity.promo_code?.code || 'Code gelöscht' }}
                                    </code>
                                    - {{ formatActivityAction(activity.action) }}
                                </p>
                                <p class="text-muted-foreground">
                                    {{ activity.description }}
                                </p>
                                <p class="text-xs text-muted-foreground">
                                    {{ formatDate(activity.created_at) }}
                                    <span v-if="activity.performed_by">
                                        von {{ activity.performed_by.name }}
                                    </span>
                                </p>
                            </div>
                        </div>
                    </div>
                </CardContent>
            </Card>
        </div>
    </AppLayout>
</template>
