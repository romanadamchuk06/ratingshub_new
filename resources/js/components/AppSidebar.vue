<script setup>
import NavMain from '@/components/NavMain.vue';
import NavUser from '@/components/NavUser.vue';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { dashboard } from '@/routes';
import { Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import { LayoutGrid, Star, Shield } from 'lucide-vue-next';
import AppLogo from './AppLogo.vue';

const page = usePage();
const hasPlatformConnected = computed(() => page.props.auth.hasPlatformConnected);
const isAdmin = computed(() => page.props.auth.isAdmin);

const mainNavItems = computed(() => {
    const items = [
        {
            title: 'Dashboard',
            href: dashboard(),
            icon: LayoutGrid,
            disabled: false,
        },
        {
            title: 'Bewertungen',
            href: '/reviews',
            icon: Star,
            disabled: !hasPlatformConnected.value,
        },
    ];

    // Add admin link only for admins
    if (isAdmin.value) {
        items.push({
            title: 'Admin',
            href: '/admin',
            icon: Shield,
            disabled: false,
        });
    }

    return items;
});
</script>

<template>
    <Sidebar collapsible="icon" variant="inset">
        <SidebarHeader>
            <SidebarMenu>
                <SidebarMenuItem>
                    <SidebarMenuButton size="lg" as-child>
                        <Link :href="dashboard()">
                            <AppLogo />
                        </Link>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>
        </SidebarHeader>

        <SidebarContent>
            <NavMain :items="mainNavItems" />
        </SidebarContent>

        <SidebarFooter>
            <NavUser />
        </SidebarFooter>
    </Sidebar>
    <slot />
</template>
