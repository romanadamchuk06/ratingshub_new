<script setup>
import { Head } from '@inertiajs/vue3';
import AppLogo from '@/components/AppLogo.vue';

defineProps({
    title: {
        type: String,
        required: true,
    },
});
</script>

<template>
    <Head :title="title" />

    <div class="flex min-h-screen flex-col items-center justify-center bg-white px-4 dark:bg-black">
        <!-- Logo -->
        <div class="mb-8">
            <AppLogo />
        </div>

        <!-- Content Slot -->
        <div class="w-full max-w-2xl text-center">
            <slot />
        </div>
    </div>
</template>
