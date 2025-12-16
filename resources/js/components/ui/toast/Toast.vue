<script setup>
import { computed } from 'vue';
import { ToastRoot, ToastTitle, ToastDescription, ToastClose } from 'reka-ui';
import { X } from 'lucide-vue-next';
import { cn } from '@/lib/utils';

const props = defineProps({
    variant: {
        type: String,
        default: 'default',
        validator: (value) => ['default', 'success', 'error', 'warning'].includes(value),
    },
    title: {
        type: String,
        required: true,
    },
    description: {
        type: String,
        default: null,
    },
});

const variantClasses = computed(() => {
    const base = 'group pointer-events-auto relative flex w-full items-center justify-between space-x-4 overflow-hidden rounded-md border p-6 pr-8 shadow-lg transition-all';

    const variants = {
        default: 'border-border bg-card text-card-foreground',
        success: 'border-green-200 dark:border-green-800 bg-green-50 dark:bg-green-950 text-green-900 dark:text-green-100',
        error: 'border-red-200 dark:border-red-800 bg-red-50 dark:bg-red-950 text-red-900 dark:text-red-100',
        warning: 'border-orange-200 dark:border-orange-800 bg-orange-50 dark:bg-orange-950 text-orange-900 dark:text-orange-100',
    };

    return cn(base, variants[props.variant]);
});
</script>

<template>
    <ToastRoot :class="variantClasses">
        <div class="grid gap-1">
            <ToastTitle class="text-sm font-semibold">
                {{ title }}
            </ToastTitle>
            <ToastDescription v-if="description" class="text-sm opacity-90">
                {{ description }}
            </ToastDescription>
        </div>
        <ToastClose
            class="absolute right-2 top-2 rounded-md p-1 text-foreground/50 opacity-0 transition-opacity hover:text-foreground focus:opacity-100 focus:outline-none focus:ring-2 group-hover:opacity-100"
        >
            <X class="h-4 w-4" />
        </ToastClose>
    </ToastRoot>
</template>
