<script setup>
import logoLight from '@/assets/logo-light.svg';
import logoDark from '@/assets/logo-dark.svg';
</script>

<template>
    <div class="flex items-center justify-center w-full">
        <!-- Light mode logo -->
        <img
            :src="logoLight"
            alt="RatingsHub Logo"
            class="h-10 w-auto max-w-full dark:hidden"
        />
        <!-- Dark mode logo -->
        <img
            :src="logoDark"
            alt="RatingsHub Logo"
            class="hidden h-10 w-auto max-w-full dark:block"
        />
    </div>
</template>
