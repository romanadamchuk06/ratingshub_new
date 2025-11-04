<script setup>
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Switch } from '@/components/ui/switch';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { ArrowLeft } from 'lucide-vue-next';
import InputError from '@/components/InputError.vue';

const props = defineProps({
    user: Object,
});

const form = useForm({
    name: props.user.name,
    email: props.user.email,
    password: '',
    password_confirmation: '',
    is_admin: props.user.is_admin,
});

const submit = () => {
    form.put(`/admin/users/${props.user.id}`, {
        preserveScroll: true,
    });
};
</script>

<template>
    <Head :title="`Benutzer bearbeiten: ${user.name}`" />

    <AppLayout>
        <div class="space-y-6 p-4 md:p-6 lg:p-8">
            <!-- Header -->
            <div class="flex items-center gap-4">
                <Link href="/admin/users">
                    <Button variant="ghost" size="icon">
                        <ArrowLeft class="h-4 w-4" />
                    </Button>
                </Link>
                <div>
                    <h1 class="text-2xl font-bold tracking-tight md:text-3xl">Benutzer bearbeiten</h1>
                    <p class="text-muted-foreground">Bearbeite Benutzerinformationen</p>
                </div>
            </div>

            <!-- Edit Form -->
            <Card class="max-w-2xl">
                <CardHeader>
                    <CardTitle>Benutzerdaten</CardTitle>
                    <CardDescription>
                        Aktualisiere die Informationen für {{ user.name }}
                    </CardDescription>
                </CardHeader>
                <CardContent>
                    <form @submit.prevent="submit" class="space-y-6">
                        <!-- Name -->
                        <div class="space-y-2">
                            <Label for="name">Name</Label>
                            <Input
                                id="name"
                                v-model="form.name"
                                type="text"
                                required
                            />
                            <InputError :message="form.errors.name" />
                        </div>

                        <!-- Email -->
                        <div class="space-y-2">
                            <Label for="email">E-Mail</Label>
                            <Input
                                id="email"
                                v-model="form.email"
                                type="email"
                                required
                            />
                            <InputError :message="form.errors.email" />
                        </div>

                        <!-- Password -->
                        <div class="space-y-2">
                            <Label for="password">Neues Passwort</Label>
                            <Input
                                id="password"
                                v-model="form.password"
                                type="password"
                                autocomplete="new-password"
                            />
                            <p class="text-sm text-muted-foreground">
                                Leer lassen, um das Passwort nicht zu ändern
                            </p>
                            <InputError :message="form.errors.password" />
                        </div>

                        <!-- Password Confirmation -->
                        <div class="space-y-2">
                            <Label for="password_confirmation">Passwort bestätigen</Label>
                            <Input
                                id="password_confirmation"
                                v-model="form.password_confirmation"
                                type="password"
                                autocomplete="new-password"
                            />
                            <InputError :message="form.errors.password_confirmation" />
                        </div>

                        <!-- Admin Status -->
                        <div class="flex items-center justify-between rounded-lg border p-4">
                            <div class="space-y-0.5">
                                <Label for="is_admin">Administrator</Label>
                                <p class="text-sm text-muted-foreground">
                                    Gewähre diesem Benutzer Admin-Rechte
                                </p>
                            </div>
                            <Switch
                                id="is_admin"
                                v-model:checked="form.is_admin"
                            />
                        </div>

                        <!-- User Info -->
                        <div class="rounded-lg border p-4">
                            <h3 class="mb-2 font-medium">Account-Informationen</h3>
                            <div class="space-y-1 text-sm text-muted-foreground">
                                <p>Erstellt am: {{ new Date(user.created_at).toLocaleDateString('de-DE') }}</p>
                                <p v-if="user.email_verified_at">
                                    E-Mail verifiziert: {{ new Date(user.email_verified_at).toLocaleDateString('de-DE') }}
                                </p>
                                <p v-else class="text-amber-600">E-Mail noch nicht verifiziert</p>
                            </div>
                        </div>

                        <!-- Actions -->
                        <div class="flex gap-4">
                            <Button type="submit" :disabled="form.processing">
                                {{ form.processing ? 'Speichern...' : 'Änderungen speichern' }}
                            </Button>
                            <Link href="/admin/users">
                                <Button type="button" variant="outline">
                                    Abbrechen
                                </Button>
                            </Link>
                        </div>
                    </form>
                </CardContent>
            </Card>
        </div>
    </AppLayout>
</template>
