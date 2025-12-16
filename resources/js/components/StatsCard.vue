<script setup>
import { computed } from 'vue';
import SimpleTooltip from '@/components/ui/tooltip/SimpleTooltip.vue';

const props = defineProps({
    title: String,
    value: [String, Number],
    icon: Object,
    trend: {
        type: String,
        default: null, // 'up', 'down', or null
    },
    trendValue: {
        type: String,
        default: null,
    },
    loading: {
        type: Boolean,
        default: false,
    },
    tooltip: {
        type: String,
        default: null,
    },
});

const trendColor = computed(() => {
    if (!props.trend) return '';
    return props.trend === 'up'
        ? 'text-green-600 dark:text-green-400'
        : 'text-red-600 dark:text-red-400';
});
</script>

<template>
    <div class="rounded-xl border bg-card p-6 shadow-sm transition-shadow hover:shadow-md">
        <div class="flex items-center justify-between">
            <div class="flex-1">
                <SimpleTooltip v-if="tooltip" :text="tooltip">
                    <p class="text-sm font-medium text-muted-foreground cursor-help inline-flex items-center gap-1">
                        {{ title }}
                        <svg
                            xmlns="http://www.w3.org/2000/svg"
                            width="14"
                            height="14"
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="2"
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            class="opacity-50"
                        >
                            <circle cx="12" cy="12" r="10" />
                            <path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3" />
                            <path d="M12 17h.01" />
                        </svg>
                    </p>
                </SimpleTooltip>
                <p v-else class="text-sm font-medium text-muted-foreground">
                    {{ title }}
                </p>

                <div class="mt-2 flex items-baseline gap-2">
                    <h3 v-if="!loading" class="text-2xl font-bold">
                        {{ value }}
                    </h3>
                    <div v-else class="h-8 w-20 animate-pulse rounded bg-muted" />

                    <span v-if="trendValue && !loading" :class="['text-sm font-medium', trendColor]">
                        {{ trendValue }}
                    </span>
                </div>
            </div>

            <div v-if="icon" class="rounded-lg bg-primary/10 p-3">
                <component :is="icon" class="h-6 w-6 text-primary" />
            </div>
        </div>
    </div>
</template>
