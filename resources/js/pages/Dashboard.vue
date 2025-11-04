<script setup>
import AppLayout from '@/layouts/AppLayout.vue';
import { Head } from '@inertiajs/vue3';
import { ref, onMounted } from 'vue';
import ConnectPlatformModal from '../components/ConnectPlatformModal.vue';
import StatsCard from '../components/StatsCard.vue';
import EmptyState from '../components/EmptyState.vue';
import { Star, TrendingUp, MessageSquare, Award, Link2 } from 'lucide-vue-next';

const props = defineProps({
    hasGoogleConnected: Boolean,
});

const showModal = ref(false);

// Show modal on mount if no platforms connected and not dismissed
onMounted(() => {
    const dismissed = sessionStorage.getItem('platformModalDismissed');
    if (!props.hasGoogleConnected && !dismissed) {
        showModal.value = true;
    }
});

const closeModal = () => {
    showModal.value = false;
    sessionStorage.setItem('platformModalDismissed', 'true');
};
</script>

<template>
    <Head title="Dashboard" />

    <!-- Connect Platform Modal -->
    <ConnectPlatformModal :show="showModal" @close="closeModal" />

    <AppLayout>
        <div class="space-y-6 p-4 md:p-6 lg:p-8">
            <!-- Header -->
            <div>
                <h1 class="text-2xl font-bold tracking-tight md:text-3xl">Dashboard</h1>
                <p class="text-muted-foreground">
                    Überblick über deine Bewertungen und Statistiken
                </p>
            </div>

            <!-- Stats Grid -->
            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <StatsCard
                    title="Gesamtbewertungen"
                    :value="hasGoogleConnected ? '0' : '-'"
                    :icon="Star"
                    :loading="false"
                />
                <StatsCard
                    title="Durchschnitt"
                    :value="hasGoogleConnected ? '-' : '-'"
                    :icon="Award"
                    :loading="false"
                />
                <StatsCard
                    title="Neue diese Woche"
                    :value="hasGoogleConnected ? '0' : '-'"
                    :icon="TrendingUp"
                    trend="up"
                    trendValue="+0%"
                    :loading="false"
                />
                <StatsCard
                    title="Zu beantworten"
                    :value="hasGoogleConnected ? '0' : '-'"
                    :icon="MessageSquare"
                    :loading="false"
                />
            </div>

            <!-- Main Content Area -->
            <div class="rounded-xl border bg-card">
                <div class="border-b p-6">
                    <h2 class="text-lg font-semibold">Neueste Bewertungen</h2>
                </div>
                <div class="p-6">
                    <EmptyState
                        v-if="!hasGoogleConnected"
                        :icon="Link2"
                        title="Keine Plattform verbunden"
                        description="Verbinde zuerst eine Plattform wie Google My Business, um deine Bewertungen zu sehen."
                        actionText="Plattform verbinden"
                        actionHref="/settings/platforms"
                    />
                    <EmptyState
                        v-else
                        :icon="Star"
                        title="Noch keine Bewertungen"
                        description="Sobald du Bewertungen erhältst, werden sie hier angezeigt."
                    />
                </div>
            </div>
        </div>
    </AppLayout>
</template>
