<script setup>
/**
 * USER MENU DROPDOWN
 * ==================
 *
 * Zwei Abo-Links:
 * 1. "Abos" → Zeigt alle verfügbaren Pläne (Pricing-Seite)
 * 2. "Abo verwalten" → Verwaltet das aktuelle Abo des Users
 *
 * Warum beide?
 * - User kann schnell neue Pläne ansehen ohne Dashboard zu verlassen
 * - "Verwalten" ist für Änderungen am aktuellen Abo (Cancel, Zahlungsmethode, etc.)
 */

import UserInfo from '@/components/UserInfo.vue';
import {
    DropdownMenuGroup,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuSeparator,
} from '@/components/ui/dropdown-menu';
import { logout } from '@/routes';
import { edit } from '@/routes/profile';
import { Link, router } from '@inertiajs/vue3';
import { LogOut, Settings, CreditCard, Package, Bug } from 'lucide-vue-next';

const handleLogout = () => {
    router.flushAll();
};

defineProps({
    user: Object,
});
</script>

<template>
    <DropdownMenuLabel class="p-0 font-normal">
        <div class="flex items-center gap-2 px-1 py-1.5 text-left text-sm">
            <UserInfo :user="user" :show-email="true" />
        </div>
    </DropdownMenuLabel>
    <DropdownMenuSeparator />
    <DropdownMenuGroup>
        <!-- Einstellungen -->
        <DropdownMenuItem :as-child="true">
            <Link class="block w-full" :href="edit()" prefetch as="button">
                <Settings class="mr-2 h-4 w-4" />
                Einstellungen
            </Link>
        </DropdownMenuItem>

        <!-- Abos: Zeigt alle verfügbaren Pläne (Pricing-Seite) -->
        <DropdownMenuItem :as-child="true">
            <Link class="block w-full" href="/subscription" as="button">
                <Package class="mr-2 h-4 w-4" />
                Abos
            </Link>
        </DropdownMenuItem>

        <!-- Abo verwalten: Management-Seite für aktuelles Abo -->
        <DropdownMenuItem :as-child="true">
            <Link class="block w-full" href="/subscription/manage" as="button">
                <CreditCard class="mr-2 h-4 w-4" />
                Abo verwalten
            </Link>
        </DropdownMenuItem>

        <!-- Bug melden -->
        <DropdownMenuItem :as-child="true">
            <Link class="block w-full" href="/bug-reports/create" as="button">
                <Bug class="mr-2 h-4 w-4" />
                Bug melden
            </Link>
        </DropdownMenuItem>
    </DropdownMenuGroup>
    <DropdownMenuSeparator />
    <DropdownMenuItem :as-child="true">
        <Link
            class="block w-full"
            :href="logout()"
            @click="handleLogout"
            as="button"
            data-test="logout-button"
        >
            <LogOut class="mr-2 h-4 w-4" />
            Abmelden
        </Link>
    </DropdownMenuItem>
</template>
