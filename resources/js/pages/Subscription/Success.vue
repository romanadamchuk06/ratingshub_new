<script setup>
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardFooter, CardHeader, CardTitle } from '@/components/ui/card';
import { CheckCircle2, ArrowRight } from 'lucide-vue-next';
</script>

<template>
    <Head title="Subscription erfolgreich" />

    <AppLayout>
        <div class="flex min-h-[60vh] items-center justify-center p-4">
            <Card class="w-full max-w-md text-center">
                <CardHeader>
                    <div class="mb-4 flex justify-center">
                        <div class="flex h-16 w-16 items-center justify-center rounded-full bg-green-100 dark:bg-green-900/20">
                            <CheckCircle2 class="h-8 w-8 text-green-600 dark:text-green-500" />
                        </div>
                    </div>
                    <CardTitle class="text-2xl">Willkommen an Bord!</CardTitle>
                    <CardDescription>
                        Deine Subscription wurde erfolgreich aktiviert. Du kannst jetzt alle Features nutzen.
                    </CardDescription>
                </CardHeader>

                <CardContent class="space-y-4">
                    <div class="rounded-lg bg-muted p-4 text-left">
                        <h3 class="mb-2 font-semibold">Was jetzt?</h3>
                        <ul class="space-y-2 text-sm text-muted-foreground">
                            <li class="flex items-start gap-2">
                                <CheckCircle2 class="mt-0.5 h-4 w-4 flex-shrink-0 text-primary" />
                                <span>Verbinde deine Plattformen</span>
                            </li>
                            <li class="flex items-start gap-2">
                                <CheckCircle2 class="mt-0.5 h-4 w-4 flex-shrink-0 text-primary" />
                                <span>Starte mit dem Verwalten deiner Bewertungen</span>
                            </li>
                            <li class="flex items-start gap-2">
                                <CheckCircle2 class="mt-0.5 h-4 w-4 flex-shrink-0 text-primary" />
                                <span>Erkunde die Analytics-Features</span>
                            </li>
                        </ul>
                    </div>

                    <p class="text-sm text-muted-foreground">
                        Du erhältst in Kürze eine Bestätigungs-E-Mail mit allen Details.
                    </p>
                </CardContent>

                <CardFooter class="flex gap-3">
                    <Link href="/dashboard" class="flex-1">
                        <Button class="w-full">
                            Zum Dashboard
                            <ArrowRight class="ml-2 h-4 w-4" />
                        </Button>
                    </Link>
                    <a href="/subscription/billing-portal">
                        <Button variant="outline">
                            Subscription verwalten
                        </Button>
                    </a>
                </CardFooter>
            </Card>
        </div>
    </AppLayout>
</template>
