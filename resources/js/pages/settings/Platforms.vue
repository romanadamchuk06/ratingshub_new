<script setup>
import AppLayout from '@/layouts/AppLayout.vue';
import SettingsLayout from '@/layouts/settings/Layout.vue';
import { Head, router } from '@inertiajs/vue3';

const props = defineProps({
    connectedPlatforms: {
        type: Array,
        default: () => [],
    },
});

const breadcrumbs = [
    {
        title: 'Settings',
        href: '/settings/profile',
    },
    {
        title: 'Plattformen',
        href: '/settings/platforms',
    },
];

const disconnect = (platformId) => {
    if (confirm('Möchtest du diese Plattform wirklich trennen?')) {
        router.delete(`/platforms/${platformId}`, {
            preserveScroll: true,
        });
    }
};

const getProviderName = (provider) => {
    const names = {
        google: 'Google My Business',
        trustpilot: 'Trustpilot',
        facebook: 'Facebook',
        yelp: 'Yelp',
    };
    return names[provider] || provider;
};

const isConnected = (provider) => {
    return props.connectedPlatforms.some((p) => p.provider === provider);
};
</script>

<template>
    <Head title="Plattformen" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <SettingsLayout>
        <div class="flex h-full flex-1 flex-col gap-6 rounded-xl">
            <div>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
                    Plattformen verwalten
                </h1>
                <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">
                    Verbinde deine Review-Plattformen um alle Bewertungen an
                    einem Ort zu verwalten.
                </p>
            </div>

            <!-- Connected Platforms -->
            <div
                v-if="connectedPlatforms.length > 0"
                class="rounded-xl border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-800"
            >
                <div class="border-b border-gray-200 px-6 py-4 dark:border-gray-700">
                    <h2 class="font-semibold text-gray-900 dark:text-white">
                        Verbundene Plattformen
                    </h2>
                </div>
                <div class="divide-y divide-gray-200 dark:divide-gray-700">
                    <div
                        v-for="platform in connectedPlatforms"
                        :key="platform.id"
                        class="flex items-center justify-between px-6 py-4"
                    >
                        <div class="flex items-center gap-4">
                            <div
                                class="flex h-12 w-12 items-center justify-center rounded-lg bg-blue-50 dark:bg-blue-900/20"
                            >
                                <svg
                                    v-if="platform.provider === 'google'"
                                    class="h-6 w-6"
                                    viewBox="0 0 24 24"
                                    fill="currentColor"
                                >
                                    <path
                                        fill="#4285F4"
                                        d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"
                                    />
                                    <path
                                        fill="#34A853"
                                        d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"
                                    />
                                    <path
                                        fill="#FBBC05"
                                        d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"
                                    />
                                    <path
                                        fill="#EA4335"
                                        d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"
                                    />
                                </svg>
                            </div>
                            <div>
                                <h3
                                    class="font-medium text-gray-900 dark:text-white"
                                >
                                    {{ getProviderName(platform.provider) }}
                                </h3>
                                <p
                                    class="text-sm text-gray-500 dark:text-gray-400"
                                >
                                    {{
                                        platform.metadata?.email ||
                                        'Verbunden'
                                    }}
                                </p>
                            </div>
                        </div>
                        <button
                            @click="disconnect(platform.id)"
                            class="rounded-lg border border-red-200 bg-red-50 px-4 py-2 text-sm font-medium text-red-700 transition hover:bg-red-100 dark:border-red-800 dark:bg-red-900/20 dark:text-red-400 dark:hover:bg-red-900/30"
                        >
                            Trennen
                        </button>
                    </div>
                </div>
            </div>

            <!-- Available Platforms -->
            <div class="rounded-xl border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-800">
                <div class="border-b border-gray-200 px-6 py-4 dark:border-gray-700">
                    <h2 class="font-semibold text-gray-900 dark:text-white">
                        Verfügbare Plattformen
                    </h2>
                </div>
                <div class="grid gap-4 p-6 md:grid-cols-2">
                    <!-- Google -->
                    <a
                        v-if="!isConnected('google')"
                        href="/platforms/connect/google"
                        class="group relative overflow-hidden rounded-xl border-2 border-gray-200 bg-white p-6 transition hover:border-blue-500 hover:shadow-lg dark:border-gray-700 dark:bg-gray-800"
                    >
                        <div class="flex items-start gap-4">
                            <div
                                class="flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-lg bg-blue-50 dark:bg-blue-900/20"
                            >
                                <svg
                                    class="h-6 w-6"
                                    viewBox="0 0 24 24"
                                    fill="currentColor"
                                >
                                    <path
                                        fill="#4285F4"
                                        d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"
                                    />
                                    <path
                                        fill="#34A853"
                                        d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"
                                    />
                                    <path
                                        fill="#FBBC05"
                                        d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"
                                    />
                                    <path
                                        fill="#EA4335"
                                        d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"
                                    />
                                </svg>
                            </div>
                            <div class="flex-1">
                                <h3
                                    class="font-semibold text-gray-900 dark:text-white"
                                >
                                    Google My Business
                                </h3>
                                <p
                                    class="mt-1 text-sm text-gray-500 dark:text-gray-400"
                                >
                                    Bewertungen von Google Maps
                                </p>
                            </div>
                            <svg
                                class="h-5 w-5 text-gray-400 transition group-hover:text-blue-500"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M9 5l7 7-7 7"
                                />
                            </svg>
                        </div>
                    </a>

                    <!-- Coming Soon Platforms -->
                    <div
                        class="relative overflow-hidden rounded-xl border-2 border-gray-200 bg-white p-6 opacity-50 dark:border-gray-700 dark:bg-gray-800"
                    >
                        <div class="flex items-start gap-4">
                            <div
                                class="flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-lg bg-green-50 dark:bg-green-900/20"
                            >
                                <svg
                                    class="h-6 w-6 text-green-600"
                                    fill="currentColor"
                                    viewBox="0 0 24 24"
                                >
                                    <path
                                        d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"
                                    />
                                </svg>
                            </div>
                            <div class="flex-1">
                                <h3
                                    class="font-semibold text-gray-900 dark:text-white"
                                >
                                    Trustpilot
                                </h3>
                                <p
                                    class="mt-1 text-sm text-gray-500 dark:text-gray-400"
                                >
                                    Bald verfügbar
                                </p>
                            </div>
                        </div>
                        <div
                            class="absolute right-4 top-4 rounded-full bg-gray-100 px-3 py-1 text-xs font-medium text-gray-600 dark:bg-gray-700 dark:text-gray-400"
                        >
                            Bald
                        </div>
                    </div>
                </div>
            </div>
        </div>
        </SettingsLayout>
    </AppLayout>
</template>
