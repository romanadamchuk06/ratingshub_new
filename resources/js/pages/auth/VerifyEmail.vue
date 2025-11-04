<script setup>
import TextLink from '@/components/TextLink.vue';
import { Button } from '@/components/ui/button';
import AuthLayout from '@/layouts/AuthLayout.vue';
import { logout } from '@/routes';
import { send } from '@/routes/verification';
import { Form, Head } from '@inertiajs/vue3';
import { LoaderCircle } from 'lucide-vue-next';

defineProps({
    status: String,
});
</script>

<template>
    <AuthLayout
        title="E-Mail bestätigen"
        description="Bitte bestätige deine E-Mail-Adresse, indem du auf den Link klickst, den wir dir gerade per E-Mail gesendet haben."
    >
        <Head title="E-Mail-Bestätigung" />

        <div
            v-if="status === 'verification-link-sent'"
            class="mb-4 text-center text-sm font-medium text-green-600"
        >
            Ein neuer Bestätigungslink wurde an die E-Mail-Adresse gesendet, die du bei der Registrierung angegeben hast.
        </div>

        <Form
            v-bind="send.form()"
            class="space-y-6 text-center"
            v-slot="{ processing }"
        >
            <Button :disabled="processing" variant="secondary">
                <LoaderCircle v-if="processing" class="h-4 w-4 animate-spin" />
                Bestätigungs-E-Mail erneut senden
            </Button>

            <TextLink
                :href="logout()"
                as="button"
                class="mx-auto block text-sm"
            >
                Abmelden
            </TextLink>
        </Form>
    </AuthLayout>
</template>
