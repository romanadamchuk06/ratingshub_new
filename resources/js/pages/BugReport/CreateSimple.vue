<script setup>
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';

const form = useForm({
    title: '',
    description: '',
    type: 'bug',
    steps_to_reproduce: '',
});

const submit = () => {
    form.post(route('bug-reports.store'), {
        onSuccess: () => {
            form.reset();
        },
    });
};
</script>

<template>
    <Head title="Bug melden" />

    <AppLayout>
        <div class="mx-auto max-w-3xl space-y-6 p-4 md:p-6 lg:p-8">
            <h1 class="text-2xl font-bold">Bug melden - Test</h1>

            <div class="rounded-xl border bg-card p-6">
                <form @submit.prevent="submit" class="space-y-4">
                    <div>
                        <label>Type:</label>
                        <select v-model="form.type" class="border rounded p-2 w-full">
                            <option value="bug">Bug</option>
                            <option value="feature">Feature</option>
                            <option value="improvement">Improvement</option>
                            <option value="question">Question</option>
                        </select>
                    </div>

                    <div>
                        <label>Titel:</label>
                        <input
                            v-model="form.title"
                            type="text"
                            class="border rounded p-2 w-full"
                            placeholder="Titel"
                        />
                    </div>

                    <div>
                        <label>Beschreibung:</label>
                        <textarea
                            v-model="form.description"
                            class="border rounded p-2 w-full"
                            rows="4"
                            placeholder="Beschreibung"
                        ></textarea>
                    </div>

                    <Button type="submit" :disabled="form.processing">
                        {{ form.processing ? 'Wird gesendet...' : 'Absenden' }}
                    </Button>
                </form>
            </div>
        </div>
    </AppLayout>
</template>
