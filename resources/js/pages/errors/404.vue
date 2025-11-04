<script setup>
import ErrorLayout from '@/layouts/ErrorLayout.vue';
</script>

<template>
    <ErrorLayout title="404 - Seite nicht gefunden">
        <h1 class="mb-4 text-9xl font-extrabold text-black dark:text-white">404</h1>
        <p class="mb-4 text-2xl font-semibold text-black dark:text-white">Seite nicht gefunden</p>
        <p class="mb-8 text-gray-600 dark:text-gray-400">
            Die von Ihnen gesuchte Seite existiert nicht oder wurde verschoben.
        </p>
        <a
            href="/"
            class="inline-block rounded-lg bg-[#DC2626] px-6 py-3 text-white transition hover:bg-[#B91C1C]"
        >
            Zur Startseite
        </a>
    </ErrorLayout>
</template>
