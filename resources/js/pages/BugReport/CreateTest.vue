<script setup>
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import { store, myReports } from '@/routes/bug-reports';

const form = useForm({
    title: '',
    description: '',
    type: 'bug',
    steps_to_reproduce: '',
});

const submit = () => {
    console.log('Submit clicked!', form.data());
    console.log('Route:', store.url());

    form.post(store.url(), {
        onSuccess: () => {
            console.log('Success!');
            router.visit(myReports.url());
        },
        onError: (errors) => {
            console.log('Errors:', errors);
        },
    });
};
</script>

<template>
    <Head title="Bug melden - Test" />

    <AppLayout>
        <div class="mx-auto max-w-3xl space-y-6 p-8">
            <h1 class="text-2xl font-bold">Bug melden - Debug Version</h1>

            <div class="rounded-xl border bg-card p-6">
                <form @submit.prevent="submit" class="space-y-4">
                    <div>
                        <label class="block mb-2">Type:</label>
                        <select v-model="form.type" class="border rounded p-2 w-full">
                            <option value="bug">Bug</option>
                            <option value="feature">Feature</option>
                            <option value="improvement">Improvement</option>
                            <option value="question">Question</option>
                        </select>
                        <p v-if="form.errors.type" class="text-red-600 text-sm mt-1">{{ form.errors.type }}</p>
                    </div>

                    <div>
                        <label class="block mb-2">Titel: *</label>
                        <input
                            v-model="form.title"
                            type="text"
                            class="border rounded p-2 w-full"
                            placeholder="Titel eingeben"
                            required
                        />
                        <p v-if="form.errors.title" class="text-red-600 text-sm mt-1">{{ form.errors.title }}</p>
                    </div>

                    <div>
                        <label class="block mb-2">Beschreibung: * (min. 10 Zeichen)</label>
                        <textarea
                            v-model="form.description"
                            class="border rounded p-2 w-full"
                            rows="4"
                            placeholder="Beschreibung eingeben (mindestens 10 Zeichen)"
                            required
                        ></textarea>
                        <p v-if="form.errors.description" class="text-red-600 text-sm mt-1">{{ form.errors.description }}</p>
                    </div>

                    <div v-if="form.type === 'bug'">
                        <label class="block mb-2">Schritte zum Reproduzieren (optional):</label>
                        <textarea
                            v-model="form.steps_to_reproduce"
                            class="border rounded p-2 w-full"
                            rows="3"
                            placeholder="Optional"
                        ></textarea>
                    </div>

                    <div class="flex gap-3">
                        <button
                            type="submit"
                            :disabled="form.processing"
                            class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700 disabled:opacity-50"
                        >
                            {{ form.processing ? 'Wird gesendet...' : 'Bug melden' }}
                        </button>

                        <button
                            type="button"
                            @click="console.log('Form data:', form.data())"
                            class="bg-gray-600 text-white px-4 py-2 rounded hover:bg-gray-700"
                        >
                            Debug: Log Form Data
                        </button>
                    </div>

                    <div class="bg-gray-100 p-4 rounded">
                        <p class="text-sm font-bold mb-2">Debug Info:</p>
                        <pre class="text-xs">{{ JSON.stringify(form.data(), null, 2) }}</pre>
                        <p class="text-xs mt-2">Processing: {{ form.processing }}</p>
                    </div>
                </form>
            </div>
        </div>
    </AppLayout>
</template>
