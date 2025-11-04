<script setup>
import { ref } from 'vue';
import { Dialog, DialogPanel, DialogTitle } from '@headlessui/vue';

const props = defineProps({
    show: Boolean,
});

const emit = defineEmits(['close']);

const isOpen = ref(props.show);

const closeModal = () => {
    isOpen.value = false;
    emit('close');
};
</script>

<template>
    <Dialog :open="isOpen" @close="closeModal" class="relative z-50">
        <!-- Backdrop -->
        <div class="fixed inset-0 bg-black/30" aria-hidden="true" />

        <!-- Modal Container -->
        <div class="fixed inset-0 flex items-center justify-center p-4">
            <DialogPanel
                class="mx-auto max-w-2xl w-full rounded-2xl bg-white p-8 shadow-2xl dark:bg-gray-900"
            >
                <!-- Header -->
                <DialogTitle
                    class="text-2xl font-bold text-gray-900 dark:text-white"
                >
                    Verbinde deine Plattformen
                </DialogTitle>

                <p class="mt-3 text-gray-600 dark:text-gray-400">
                    Synchronisiere deine Bewertungen von verschiedenen
                    Plattformen und verwalte sie alle an einem Ort.
                </p>

                <!-- Platforms Grid -->
                <div class="mt-8 grid gap-4 md:grid-cols-2">
                    <!-- Google My Business -->
                    <a
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

                    <!-- Trustpilot -->
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

                    <!-- Facebook -->
                    <div
                        class="relative overflow-hidden rounded-xl border-2 border-gray-200 bg-white p-6 opacity-50 dark:border-gray-700 dark:bg-gray-800"
                    >
                        <div class="flex items-start gap-4">
                            <div
                                class="flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-lg bg-blue-50 dark:bg-blue-900/20"
                            >
                                <svg
                                    class="h-6 w-6 text-blue-600"
                                    fill="currentColor"
                                    viewBox="0 0 24 24"
                                >
                                    <path
                                        d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"
                                    />
                                </svg>
                            </div>
                            <div class="flex-1">
                                <h3
                                    class="font-semibold text-gray-900 dark:text-white"
                                >
                                    Facebook
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

                    <!-- Yelp -->
                    <div
                        class="relative overflow-hidden rounded-xl border-2 border-gray-200 bg-white p-6 opacity-50 dark:border-gray-700 dark:bg-gray-800"
                    >
                        <div class="flex items-start gap-4">
                            <div
                                class="flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-lg bg-red-50 dark:bg-red-900/20"
                            >
                                <svg
                                    class="h-6 w-6 text-red-600"
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
                                    Yelp
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

                <!-- Footer -->
                <div class="mt-8 flex items-center justify-between">
                    <p class="text-sm text-gray-500 dark:text-gray-400">
                        Du kannst weitere Plattformen später hinzufügen
                    </p>
                    <button
                        @click="closeModal"
                        class="rounded-lg bg-gray-100 px-4 py-2 text-sm font-medium text-gray-700 transition hover:bg-gray-200 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700"
                    >
                        Später
                    </button>
                </div>
            </DialogPanel>
        </div>
    </Dialog>
</template>
