<script setup>
/**
 * Simple Tooltip Wrapper
 *
 * Vereinfachte Tooltip-Komponente ohne TypeScript
 *
 * Usage:
 * <SimpleTooltip text="Das ist ein Tooltip">
 *   <Button>Hover me</Button>
 * </SimpleTooltip>
 */

import { TooltipProvider, TooltipRoot, TooltipTrigger, TooltipContent } from 'reka-ui';

defineProps({
    text: {
        type: String,
        required: true,
    },
    side: {
        type: String,
        default: 'top',
        validator: (value) => ['top', 'right', 'bottom', 'left'].includes(value),
    },
    delayDuration: {
        type: Number,
        default: 300,
    },
});
</script>

<template>
    <TooltipProvider>
        <TooltipRoot :delay-duration="delayDuration">
            <TooltipTrigger as-child>
                <slot />
            </TooltipTrigger>
            <TooltipContent
                :side="side"
                :side-offset="5"
                class="z-50 overflow-hidden rounded-md bg-primary px-3 py-1.5 text-xs text-primary-foreground animate-in fade-in-0 zoom-in-95"
            >
                {{ text }}
            </TooltipContent>
        </TooltipRoot>
    </TooltipProvider>
</template>
