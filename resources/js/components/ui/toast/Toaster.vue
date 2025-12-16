<script setup>
/**
 * Toaster Component
 *
 * Container für alle aktiven Toasts
 * Platziert sich fixed am unteren rechten Bildschirmrand
 *
 * Usage:
 * Einfach in AppLayout.vue einbinden:
 * <Toaster />
 */

import { ToastProvider, ToastViewport } from 'reka-ui';
import { useToast } from '@/composables/useToast';
import Toast from './Toast.vue';

const { toasts } = useToast();
</script>

<template>
    <ToastProvider>
        <Toast
            v-for="toast in toasts"
            :key="toast.id"
            :variant="toast.variant"
            :title="toast.title"
            :description="toast.description"
        />
        <ToastViewport
            class="fixed bottom-0 right-0 z-[100] flex max-h-screen w-full flex-col-reverse p-4 sm:bottom-0 sm:right-0 sm:top-auto sm:flex-col md:max-w-[420px]"
        />
    </ToastProvider>
</template>
