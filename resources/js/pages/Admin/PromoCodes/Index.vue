<script setup>
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import { Label } from '@/components/ui/label';
import { Input } from '@/components/ui/input';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select/index';
import { Plus, Trash2, ToggleLeft, ToggleRight } from 'lucide-vue-next';
import { ref } from 'vue';

const props = defineProps({
    promoCodes: {
        type: Object,
        required: true,
    },
});

const showCreateDialog = ref(false);
const newCode = ref({
    code: '',
    type: 'percentage',
    value: 0,
    max_uses: null,
    expires_at: null,
    description: '',
});

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

const formatDate = (dateString) => {
    if (!dateString) return '-';
    return new Date(dateString).toLocaleDateString('de-DE');
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
                                <Input
                                    id="code"
                                    v-model="newCode.code"
                                    placeholder="z.B. SOMMER2024"
                                    class="uppercase"
                                />
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
                                    <Label for="max_uses">Max. Verwendungen (optional)</Label>
                                    <Input
                                        id="max_uses"
                                        v-model="newCode.max_uses"
                                        type="number"
                                        min="1"
                                        placeholder="Unbegrenzt"
                                    />
                                </div>

                                <div class="space-y-2">
                                    <Label for="expires_at">Ablaufdatum (optional)</Label>
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

            <!-- Promo Codes List -->
            <div class="grid gap-4">
                <Card v-for="code in promoCodes.data" :key="code.id">
                    <CardHeader>
                        <div class="flex items-start justify-between">
                            <div>
                                <CardTitle class="text-xl">{{ code.code }}</CardTitle>
                                <CardDescription>{{ code.description || 'Keine Beschreibung' }}</CardDescription>
                            </div>
                            <div class="flex gap-2">
                                <Badge :variant="code.is_active ? 'default' : 'secondary'">
                                    {{ code.is_active ? 'Aktiv' : 'Inaktiv' }}
                                </Badge>
                            </div>
                        </div>
                    </CardHeader>
                    <CardContent>
                        <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
                            <div>
                                <p class="text-sm text-muted-foreground">Rabatt</p>
                                <p class="text-lg font-semibold">
                                    {{ code.type === 'percentage' ? `${code.value}%` : `${code.value} €` }}
                                </p>
                            </div>

                            <div>
                                <p class="text-sm text-muted-foreground">Verwendet</p>
                                <p class="text-lg font-semibold">
                                    {{ code.usages_count }}
                                    <span v-if="code.max_uses" class="text-sm text-muted-foreground">
                                        / {{ code.max_uses }}
                                    </span>
                                </p>
                            </div>

                            <div>
                                <p class="text-sm text-muted-foreground">Ablaufdatum</p>
                                <p class="text-lg font-semibold">{{ formatDate(code.expires_at) }}</p>
                            </div>

                            <div class="flex items-end gap-2">
                                <Button
                                    variant="outline"
                                    size="sm"
                                    @click="toggleActive(code)"
                                >
                                    <component
                                        :is="code.is_active ? ToggleRight : ToggleLeft"
                                        class="mr-2 h-4 w-4"
                                    />
                                    {{ code.is_active ? 'Deaktivieren' : 'Aktivieren' }}
                                </Button>

                                <Button
                                    variant="destructive"
                                    size="sm"
                                    @click="deletePromoCode(code)"
                                >
                                    <Trash2 class="h-4 w-4" />
                                </Button>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <div v-if="promoCodes.data.length === 0" class="text-center py-12">
                    <p class="text-muted-foreground">Noch keine Promo Codes erstellt.</p>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
