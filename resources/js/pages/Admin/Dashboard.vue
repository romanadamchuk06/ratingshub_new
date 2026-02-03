<script setup>
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import StatsCard from '@/components/StatsCard.vue';
import { Button } from '@/components/ui/button';
import { Users, Globe, Shield, ArrowRight, CreditCard, FileText, Bug } from 'lucide-vue-next';

defineProps({
    stats: {
        type: Object,
        required: true,
    },
});
</script>

<template>
    <Head title="Admin Dashboard" />

    <AppLayout>
        <div class="space-y-6 p-4 md:p-6 lg:p-8">
            <!-- Header -->
            <div>
                <h1 class="text-2xl font-bold tracking-tight md:text-3xl">Admin Dashboard</h1>
                <p class="text-muted-foreground">
                    Verwaltung und Übersicht der Plattform
                </p>
            </div>

            <!-- Stats Grid -->
            <!-- 4 Karten: Users, Platforms, Subscriptions, Admins -->
            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <StatsCard
                    title="Gesamte Benutzer"
                    :value="stats.totalUsers.toString()"
                    :icon="Users"
                    :loading="false"
                />
                <StatsCard
                    title="Verbundene Plattformen"
                    :value="stats.totalPlatforms.toString()"
                    :icon="Globe"
                    :loading="false"
                />
                <StatsCard
                    title="Aktive Subscriptions"
                    :value="stats.activeSubscriptions.toString()"
                    :icon="CreditCard"
                    :loading="false"
                />
                <StatsCard
                    title="Administratoren"
                    :value="stats.totalAdmins.toString()"
                    :icon="Shield"
                    :loading="false"
                />
            </div>

            <!-- Quick Actions -->
            <div class="grid gap-4 lg:grid-cols-2">
                <!-- User Management -->
                <div class="rounded-xl border bg-card">
                    <div class="border-b p-6">
                        <h2 class="text-lg font-semibold">Benutzerverwaltung</h2>
                    </div>
                    <div class="p-6">
                        <p class="mb-4 text-sm text-muted-foreground">
                            Verwalte Benutzer, weise Admin-Rechte zu und erstelle neue Accounts.
                        </p>
                        <Link href="/admin/users">
                            <Button variant="outline">
                                Benutzer verwalten
                                <ArrowRight class="ml-2 h-4 w-4" />
                            </Button>
                        </Link>
                    </div>
                </div>

                <!-- Subscription Management -->
                <div class="rounded-xl border bg-card">
                    <div class="border-b p-6">
                        <h2 class="text-lg font-semibold">Subscription-Verwaltung</h2>
                    </div>
                    <div class="p-6">
                        <p class="mb-4 text-sm text-muted-foreground">
                            Verwalte Benutzer-Subscriptions, ändere Pläne und überwache Abos.
                        </p>
                        <Link href="/admin/subscriptions">
                            <Button variant="outline">
                                <CreditCard class="mr-2 h-4 w-4" />
                                Subscriptions verwalten
                                <ArrowRight class="ml-2 h-4 w-4" />
                            </Button>
                        </Link>
                    </div>
                </div>

                <!-- Activity Logs -->
                <div class="rounded-xl border bg-card">
                    <div class="border-b p-6">
                        <h2 class="text-lg font-semibold">Activity Logs</h2>
                    </div>
                    <div class="p-6">
                        <p class="mb-4 text-sm text-muted-foreground">
                            Unveränderliche Historie aller Systemänderungen. Compliance & Audit.
                        </p>
                        <Link href="/admin/activity-logs">
                            <Button variant="outline">
                                <FileText class="mr-2 h-4 w-4" />
                                Logs ansehen (Read-Only)
                                <ArrowRight class="ml-2 h-4 w-4" />
                            </Button>
                        </Link>
                    </div>
                </div>

                <!-- Bug Reports -->
                <div class="rounded-xl border bg-card">
                    <div class="border-b p-6">
                        <h2 class="text-lg font-semibold">Bug Reports</h2>
                    </div>
                    <div class="p-6">
                        <p class="mb-4 text-sm text-muted-foreground">
                            Verwalte User-Feedback, Bugs und Feature Requests als Tickets.
                        </p>
                        <Link href="/admin/bug-reports">
                            <Button variant="outline">
                                <Bug class="mr-2 h-4 w-4" />
                                Bug-Reports verwalten
                                <ArrowRight class="ml-2 h-4 w-4" />
                            </Button>
                        </Link>
                    </div>
                </div>

                <!-- System Info -->
                <div class="rounded-xl border bg-card">
                    <div class="border-b p-6">
                        <h2 class="text-lg font-semibold">System-Informationen</h2>
                    </div>
                    <div class="p-6">
                        <div class="space-y-3">
                            <div class="flex justify-between">
                                <span class="text-sm text-muted-foreground">Laravel Version</span>
                                <span class="text-sm font-medium">11.x</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-sm text-muted-foreground">PHP Version</span>
                                <span class="text-sm font-medium">8.2+</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-sm text-muted-foreground">Database</span>
                                <span class="text-sm font-medium">MySQL 8.0</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
