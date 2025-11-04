<script setup>
import {
    SidebarGroup,
    SidebarGroupLabel,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { urlIsActive } from '@/lib/utils';
import { Link, usePage } from '@inertiajs/vue3';

defineProps({
    items: Array,
});

const page = usePage();
</script>

<template>
    <SidebarGroup class="px-2 py-0">
        <SidebarGroupLabel>Platform</SidebarGroupLabel>
        <SidebarMenu>
            <SidebarMenuItem v-for="item in items" :key="item.title">
                <SidebarMenuButton
                    :class="{ 'pointer-events-none opacity-50': item.disabled }"
                    as-child
                    :is-active="!item.disabled && urlIsActive(item.href, page.url)"
                    :tooltip="item.disabled ? 'Verbinde zuerst eine Plattform' : item.title"
                >
                    <Link :href="item.disabled ? '#' : item.href">
                        <component :is="item.icon" />
                        <span>{{ item.title }}</span>
                    </Link>
                </SidebarMenuButton>
            </SidebarMenuItem>
        </SidebarMenu>
    </SidebarGroup>
</template>
