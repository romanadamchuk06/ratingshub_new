<script setup>
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import EmptyState from '../components/EmptyState.vue';
import { Star, Link2 } from 'lucide-vue-next';

const page = usePage();
const hasPlatformConnected = computed(() => page.props.auth.hasPlatformConnected);

const props = defineProps({
    reviews: {
        type: Array,
        default: () => []
    }
});
</script>

<template>
    <Head title="Bewertungen" />

    <AppLayout>
        <div class="space-y-6 p-4 md:p-6 lg:p-8">
            <!-- Header -->
            <div>
                <h1 class="text-2xl font-bold tracking-tight md:text-3xl">Bewertungen</h1>
                <p class="text-muted-foreground">
                    Verwalte und beantworte deine Bewertungen
                </p>
            </div>

            <!-- Reviews List -->
            <div class="rounded-xl border bg-card">
                <div class="border-b p-6">
                    <div class="flex items-center justify-between">
                        <h2 class="text-lg font-semibold">Alle Bewertungen</h2>
                        <span v-if="hasPlatformConnected && reviews.length > 0" class="text-sm text-muted-foreground">
                            {{ reviews.length }} {{ reviews.length === 1 ? 'Bewertung' : 'Bewertungen' }}
                        </span>
                    </div>
                </div>
                <div class="p-6">
                    <EmptyState
                        v-if="!hasPlatformConnected"
                        :icon="Link2"
                        title="Keine Plattform verbunden"
                        description="Verbinde zuerst eine Plattform wie Google My Business, um deine Bewertungen zu sehen."
                        actionText="Plattform verbinden"
                        actionHref="/settings/platforms"
                    />
                    <EmptyState
                        v-else-if="reviews.length === 0"
                        :icon="Star"
                        title="Noch keine Bewertungen"
                        description="Sobald du Bewertungen erhältst, werden sie hier angezeigt und du kannst darauf antworten."
                    />
                    <div v-else class="space-y-4">
                        <!-- Reviews will be displayed here when they exist -->
                        <div
                            v-for="review in reviews"
                            :key="review.id"
                            class="rounded-lg border p-4"
                        >
                            <!-- Review content will be added here -->
                            {{ review }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
