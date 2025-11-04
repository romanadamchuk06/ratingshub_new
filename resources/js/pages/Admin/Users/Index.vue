<script setup>
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { ref, watch } from 'vue';
import { debounce } from 'lodash-es';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
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
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuSeparator,
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
import { Search, Plus, MoreVertical, Edit, Trash2, Shield, ShieldOff } from 'lucide-vue-next';

const props = defineProps({
    users: {
        type: Object,
        required: true,
    },
    filters: {
        type: Object,
        default: () => ({ search: '', filter: 'all' }),
    },
});

// Search and filters
const search = ref(props.filters.search || '');
const filter = ref(props.filters.filter || 'all');

// Delete dialog
const deleteDialogOpen = ref(false);
const userToDelete = ref(null);

// Watch search and filter changes
watch(
    [search, filter],
    debounce(([searchValue, filterValue]) => {
        router.get(
            '/admin/users',
            {
                search: searchValue,
                filter: filterValue,
            },
            {
                preserveState: true,
                preserveScroll: true,
            }
        );
    }, 300)
);

// Delete user
const confirmDelete = (user) => {
    userToDelete.value = user;
    deleteDialogOpen.value = true;
};

const deleteUser = () => {
    if (!userToDelete.value) return;

    router.delete(`/admin/users/${userToDelete.value.id}`, {
        onSuccess: () => {
            deleteDialogOpen.value = false;
            userToDelete.value = null;
        },
    });
};

// Toggle admin status
const toggleAdmin = (user) => {
    router.post(`/admin/users/${user.id}/toggle-admin`, {}, {
        preserveScroll: true,
    });
};

// Format date
const formatDate = (dateString) => {
    if (!dateString) return '-';
    return new Date(dateString).toLocaleDateString('de-DE', {
        year: 'numeric',
        month: '2-digit',
        day: '2-digit',
    });
};
</script>

<template>
    <Head title="Benutzerverwaltung" />

    <AppLayout :breadcrumbs="[
        { label: 'Admin', href: '/admin' },
        { label: 'Benutzer', href: '/admin/users' }
    ]">
        <div class="space-y-6 p-4 md:p-6 lg:p-8">
            <!-- Header -->
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h1 class="text-2xl font-bold tracking-tight md:text-3xl">Benutzerverwaltung</h1>
                    <p class="text-muted-foreground">Verwalte alle Benutzer der Plattform</p>
                </div>
                <Link href="/admin/users/create">
                    <Button>
                        <Plus class="mr-2 h-4 w-4" />
                        Neuer Benutzer
                    </Button>
                </Link>
            </div>

            <!-- Filters -->
            <div class="flex flex-col gap-4 sm:flex-row">
                <div class="relative flex-1">
                    <Search class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                    <Input
                        v-model="search"
                        placeholder="Suche nach Name oder E-Mail..."
                        class="pl-9"
                    />
                </div>
                <Select v-model="filter">
                    <SelectTrigger class="w-full sm:w-48">
                        <SelectValue placeholder="Filter" />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem value="all">Alle Benutzer</SelectItem>
                        <SelectItem value="admin">Nur Admins</SelectItem>
                        <SelectItem value="user">Nur Benutzer</SelectItem>
                    </SelectContent>
                </Select>
            </div>

            <!-- Users Table -->
            <div class="rounded-xl border bg-card">
                <Table>
                    <TableHeader>
                        <TableRow>
                            <TableHead>Name</TableHead>
                            <TableHead>E-Mail</TableHead>
                            <TableHead>Status</TableHead>
                            <TableHead>Plattformen</TableHead>
                            <TableHead>Erstellt am</TableHead>
                            <TableHead class="w-[70px]"></TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        <TableRow v-for="user in users.data" :key="user.id">
                            <TableCell class="font-medium">{{ user.name }}</TableCell>
                            <TableCell>{{ user.email }}</TableCell>
                            <TableCell>
                                <Badge v-if="user.is_admin" variant="default">
                                    <Shield class="mr-1 h-3 w-3" />
                                    Admin
                                </Badge>
                                <Badge v-else variant="secondary">Benutzer</Badge>
                            </TableCell>
                            <TableCell>
                                <Badge variant="outline">
                                    {{ user.connected_platforms_count }}
                                </Badge>
                            </TableCell>
                            <TableCell>{{ formatDate(user.created_at) }}</TableCell>
                            <TableCell>
                                <DropdownMenu>
                                    <DropdownMenuTrigger as-child>
                                        <Button variant="ghost" size="icon">
                                            <MoreVertical class="h-4 w-4" />
                                        </Button>
                                    </DropdownMenuTrigger>
                                    <DropdownMenuContent align="end">
                                        <Link :href="`/admin/users/${user.id}/edit`">
                                            <DropdownMenuItem>
                                                <Edit class="mr-2 h-4 w-4" />
                                                Bearbeiten
                                            </DropdownMenuItem>
                                        </Link>
                                        <DropdownMenuItem @click="toggleAdmin(user)">
                                            <ShieldOff v-if="user.is_admin" class="mr-2 h-4 w-4" />
                                            <Shield v-else class="mr-2 h-4 w-4" />
                                            {{ user.is_admin ? 'Admin entfernen' : 'Zum Admin machen' }}
                                        </DropdownMenuItem>
                                        <DropdownMenuSeparator />
                                        <DropdownMenuItem
                                            class="text-destructive focus:text-destructive"
                                            @click="confirmDelete(user)"
                                        >
                                            <Trash2 class="mr-2 h-4 w-4" />
                                            Löschen
                                        </DropdownMenuItem>
                                    </DropdownMenuContent>
                                </DropdownMenu>
                            </TableCell>
                        </TableRow>
                        <TableRow v-if="users.data.length === 0">
                            <TableCell colspan="6" class="text-center text-muted-foreground">
                                Keine Benutzer gefunden.
                            </TableCell>
                        </TableRow>
                    </TableBody>
                </Table>

                <!-- Pagination -->
                <div v-if="users.links.length > 3" class="border-t p-4">
                    <div class="flex items-center justify-between">
                        <p class="text-sm text-muted-foreground">
                            Zeige {{ users.from }} bis {{ users.to }} von {{ users.total }} Benutzern
                        </p>
                        <div class="flex gap-2">
                            <Link
                                v-for="link in users.links"
                                :key="link.label"
                                :href="link.url"
                                preserve-scroll
                                preserve-state
                            >
                                <Button
                                    variant="outline"
                                    size="sm"
                                    :disabled="!link.url"
                                    :class="{ 'bg-primary text-primary-foreground': link.active }"
                                    v-html="link.label"
                                />
                            </Link>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Delete Confirmation Dialog -->
        <Dialog v-model:open="deleteDialogOpen">
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>Benutzer löschen?</DialogTitle>
                    <DialogDescription>
                        Möchtest du den Benutzer "{{ userToDelete?.name }}" wirklich löschen? Diese Aktion
                        kann nicht rückgängig gemacht werden. Alle verbundenen Plattformen werden ebenfalls
                        gelöscht.
                    </DialogDescription>
                </DialogHeader>
                <DialogFooter>
                    <Button variant="outline" @click="deleteDialogOpen = false">
                        Abbrechen
                    </Button>
                    <Button @click="deleteUser" variant="destructive">
                        Löschen
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    </AppLayout>
</template>
