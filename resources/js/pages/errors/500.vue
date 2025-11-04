<script setup>
import ErrorLayout from '@/layouts/ErrorLayout.vue';
</script>

<template>
    <ErrorLayout title="500 - Serverfehler">
        <h1 class="mb-4 text-9xl font-extrabold text-black dark:text-white">500</h1>
        <p class="mb-4 text-2xl font-semibold text-black dark:text-white">Serverfehler</p>
        <p class="mb-8 text-gray-600 dark:text-gray-400">
            Etwas ist schief gelaufen. Bitte versuchen Sie es später erneut.
        </p>
        <a
            href="/"
            class="inline-block rounded-lg bg-[#DC2626] px-6 py-3 text-white transition hover:bg-[#B91C1C]"
        >
            Zur Startseite
        </a>
    </ErrorLayout>
</template>
