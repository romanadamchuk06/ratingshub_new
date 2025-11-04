<script setup>
defineProps({
    title: String,
    description: String,
    icon: Object,
    actionText: {
        type: String,
        default: null,
    },
    actionHref: {
        type: String,
        default: null,
    },
});

const emit = defineEmits(['action']);

const handleAction = () => {
    emit('action');
};
</script>

<template>
    <div class="flex flex-col items-center justify-center rounded-xl border-2 border-dashed border-muted-foreground/25 bg-muted/10 p-12 text-center">
        <div v-if="icon" class="mb-4 rounded-full bg-muted p-4">
            <component :is="icon" class="h-8 w-8 text-muted-foreground" />
        </div>

        <h3 class="mb-2 text-lg font-semibold">{{ title }}</h3>
        <p class="mb-6 max-w-sm text-sm text-muted-foreground">
            {{ description }}
        </p>

        <a
            v-if="actionText && actionHref"
            :href="actionHref"
            class="inline-flex items-center gap-2 rounded-lg bg-primary px-4 py-2 text-sm font-medium text-primary-foreground transition-colors hover:bg-primary/90"
        >
            {{ actionText }}
        </a>

        <button
            v-else-if="actionText"
            @click="handleAction"
            class="inline-flex items-center gap-2 rounded-lg bg-primary px-4 py-2 text-sm font-medium text-primary-foreground transition-colors hover:bg-primary/90"
        >
            {{ actionText }}
        </button>
    </div>
</template>
