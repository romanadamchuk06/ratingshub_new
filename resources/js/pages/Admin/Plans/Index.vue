<script setup>
/**
 * ADMIN PLAN MANAGEMENT - INDEX
 * ==============================
 *
 * Zeigt alle Subscription-Pläne mit:
 * - Name, Preis, Max Plattformen
 * - Anzahl Benutzer pro Plan
 * - Aktiv/Inaktiv Status
 * - "Beliebt"-Badge (wenn is_popular = true)
 * - Aktionen: Edit, Delete, Toggle Active, Toggle Popular
 *
 * Sortierung: sort_order ASC, dann Preis
 *
 * TOGGLE-FUNKTIONEN:
 * - Toggle Active: Plan aktivieren/deaktivieren (beeinflusst Sichtbarkeit auf Pricing-Seite)
 * - Toggle Popular: "Beliebt"-Badge ein/ausschalten (für Marketing-Hervorhebung)
 */

import AppLayout from '@/layouts/AppLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { MoreHorizontal, Plus, Edit, Trash2, Power, Users, Star } from 'lucide-vue-next';
import { ref } from 'vue';

const props = defineProps({
    plans: {
        type: Array,
        required: true,
    },
});

// Delete Dialog State
const planToDelete = ref(null);
const deleteDialogOpen = ref(false);

const confirmDelete = (plan) => {
    planToDelete.value = plan;
    deleteDialogOpen.value = true;
};

const deletePlan = () => {
    if (!planToDelete.value) return;

    router.delete(`/admin/plans/${planToDelete.value.id}`, {
        onSuccess: () => {
            planToDelete.value = null;
            deleteDialogOpen.value = false;
        },
    });
};

const toggleActive = (plan) => {
    router.post(`/admin/plans/${plan.id}/toggle-active`);
};

/**
 * Toggle "Beliebt"-Badge für einen Plan
 * Zeigt/versteckt den Badge auf der Pricing-Seite
 */
const togglePopular = (plan) => {
    router.post(`/admin/plans/${plan.id}/toggle-popular`);
};

/**
 * Formatiere Preis
 * 0 → "Kostenlos"
 * 9.99 → "9,99 € / Monat"
 */
const formatPrice = (price) => {
    if (parseFloat(price) === 0) {
        return 'Kostenlos';
    }
    return `${parseFloat(price).toFixed(2).replace('.', ',')} € / Monat`;
};

/**
 * Bestimme Badge-Variante für Status
 */
const getStatusBadge = (isActive) => {
    return isActive
        ? { variant: 'default', text: 'Aktiv' }
        : { variant: 'secondary', text: 'Inaktiv' };
};
</script>

<template>
    <Head title="Plan-Verwaltung" />

    <AppLayout>
        <div class="space-y-6 p-4 md:p-6 lg:p-8">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold tracking-tight md:text-3xl">Plan-Verwaltung</h1>
                    <p class="text-muted-foreground">
                        Erstelle und verwalte Subscription-Pläne
                    </p>
                </div>
                <Link href="/admin/plans/create">
                    <Button>
                        <Plus class="mr-2 h-4 w-4" />
                        Neuer Plan
                    </Button>
                </Link>
            </div>

            <!-- Plans Table -->
            <div class="rounded-xl border bg-card">
                <Table>
                    <TableHeader>
                        <TableRow>
                            <TableHead>Plan</TableHead>
                            <TableHead>Slug</TableHead>
                            <TableHead>Preis</TableHead>
                            <TableHead>Max Plattformen</TableHead>
                            <TableHead>Status</TableHead>
                            <TableHead>Benutzer</TableHead>
                            <TableHead class="text-right">Aktionen</TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        <TableRow v-for="plan in plans" :key="plan.id">
                            <!-- Plan Name mit Popular Badge -->
                            <TableCell class="font-medium">
                                <div class="flex items-center gap-2">
                                    {{ plan.name }}
                                    <Badge v-if="plan.is_popular" variant="default" class="text-xs">
                                        Beliebt
                                    </Badge>
                                </div>
                            </TableCell>

                            <!-- Slug -->
                            <TableCell>
                                <code class="rounded bg-muted px-2 py-1 text-xs">
                                    {{ plan.slug }}
                                </code>
                            </TableCell>

                            <!-- Preis -->
                            <TableCell>
                                {{ formatPrice(plan.price) }}
                            </TableCell>

                            <!-- Max Plattformen -->
                            <TableCell>
                                {{ plan.max_platforms === 1000 ? 'Unbegrenzt' : plan.max_platforms }}
                            </TableCell>

                            <!-- Status Badge -->
                            <TableCell>
                                <Badge :variant="getStatusBadge(plan.is_active).variant">
                                    {{ getStatusBadge(plan.is_active).text }}
                                </Badge>
                            </TableCell>

                            <!-- User Count -->
                            <TableCell>
                                <div class="flex items-center gap-2">
                                    <Users class="h-4 w-4 text-muted-foreground" />
                                    <span>{{ plan.users_count }}</span>
                                </div>
                            </TableCell>

                            <!-- Actions Dropdown -->
                            <TableCell class="text-right">
                                <DropdownMenu>
                                    <DropdownMenuTrigger as-child>
                                        <Button variant="ghost" size="icon">
                                            <MoreHorizontal class="h-4 w-4" />
                                        </Button>
                                    </DropdownMenuTrigger>
                                    <DropdownMenuContent align="end">
                                        <!-- Edit -->
                                        <DropdownMenuItem as-child>
                                            <Link :href="`/admin/plans/${plan.id}/edit`">
                                                <Edit class="mr-2 h-4 w-4" />
                                                Bearbeiten
                                            </Link>
                                        </DropdownMenuItem>

                                        <!-- Toggle Active -->
                                        <DropdownMenuItem @click="toggleActive(plan)">
                                            <Power class="mr-2 h-4 w-4" />
                                            {{ plan.is_active ? 'Deaktivieren' : 'Aktivieren' }}
                                        </DropdownMenuItem>

                                        <!-- Toggle Popular -->
                                        <DropdownMenuItem @click="togglePopular(plan)">
                                            <Star class="mr-2 h-4 w-4" />
                                            {{ plan.is_popular ? 'Von "Beliebt" entfernen' : 'Als "Beliebt" markieren' }}
                                        </DropdownMenuItem>

                                        <!-- Delete -->
                                        <DropdownMenuItem
                                            @click="confirmDelete(plan)"
                                            class="text-destructive"
                                        >
                                            <Trash2 class="mr-2 h-4 w-4" />
                                            Löschen
                                        </DropdownMenuItem>
                                    </DropdownMenuContent>
                                </DropdownMenu>
                            </TableCell>
                        </TableRow>

                        <!-- Empty State -->
                        <TableRow v-if="plans.length === 0">
                            <TableCell colspan="7" class="text-center text-muted-foreground">
                                Keine Pläne vorhanden
                            </TableCell>
                        </TableRow>
                    </TableBody>
                </Table>
            </div>
        </div>

        <!-- Delete Confirmation Dialog -->
        <Dialog v-model:open="deleteDialogOpen">
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>Plan löschen?</DialogTitle>
                    <DialogDescription>
                        Möchtest du den Plan "{{ planToDelete?.name }}" wirklich löschen?
                        <span v-if="planToDelete?.users_count > 0" class="mt-2 block font-semibold text-destructive">
                            Achtung: Dieser Plan kann nicht gelöscht werden,
                            da {{ planToDelete.users_count }} Benutzer diesen Plan nutzen.
                        </span>
                    </DialogDescription>
                </DialogHeader>
                <DialogFooter>
                    <Button variant="outline" @click="deleteDialogOpen = false">
                        Abbrechen
                    </Button>
                    <Button
                        @click="deletePlan"
                        variant="destructive"
                        :disabled="planToDelete?.users_count > 0"
                    >
                        Löschen
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    </AppLayout>
</template>
