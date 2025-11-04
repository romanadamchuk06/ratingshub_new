<script setup>
import { ref, onMounted, onUnmounted } from 'vue';
import ErrorLayout from '@/layouts/ErrorLayout.vue';

const progress = ref(0);
let interval;

const updateProgress = () => {
    progress.value = (progress.value + 0.5) % 100;
};

onMounted(() => {
    interval = setInterval(updateProgress, 100);

    // Auto-reload after 30 seconds
    setTimeout(() => {
        window.location.reload();
    }, 30000);
});

onUnmounted(() => {
    clearInterval(interval);
});
</script>

<template>
    <ErrorLayout title="503 - Wartungsarbeiten">
        <h1 class="mb-4 text-6xl font-extrabold text-black dark:text-white">503</h1>
        <p class="mb-4 text-2xl font-semibold text-black dark:text-white">Wartungsarbeiten</p>
        <p class="mb-8 text-gray-600 dark:text-gray-400">
            Wir sind gleich zurück. Die Seite wird automatisch neu geladen.
        </p>
        <div class="mx-auto mb-4 h-2 w-64 overflow-hidden rounded-full bg-gray-200 dark:bg-gray-700">
            <div
                class="h-full bg-[#DC2626] transition-all duration-300"
                :style="{ width: `${progress}%` }"
            ></div>
        </div>
    </ErrorLayout>
</template>
