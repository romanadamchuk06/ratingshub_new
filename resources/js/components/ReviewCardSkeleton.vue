<script setup>
/**
 * ReviewCardSkeleton Component
 *
 * Skeleton-Placeholder für ReviewCard während dem Laden
 * Zeigt animierte Platzhalter für alle Elemente (Badges, Stars, Text, Buttons)
 */

import { Card, CardContent, CardFooter, CardHeader } from '@/components/ui/card';
</script>

<template>
    <Card class="animate-pulse">
        <CardHeader class="space-y-4">
            <!-- Top Row: Platform Badge + Status Badge + Date -->
            <div class="flex items-center justify-between flex-wrap gap-2">
                <div class="flex items-center gap-2">
                    <!-- Platform Badge Skeleton -->
                    <div class="h-6 w-24 rounded-full bg-muted" />
                    <!-- Status Badge Skeleton -->
                    <div class="h-6 w-20 rounded-full bg-muted" />
                </div>

                <!-- Date Skeleton -->
                <div class="h-4 w-24 rounded bg-muted" />
            </div>

            <!-- Star Rating Skeleton -->
            <div class="flex items-center gap-1">
                <div class="h-5 w-5 rounded bg-muted" />
                <div class="h-5 w-5 rounded bg-muted" />
                <div class="h-5 w-5 rounded bg-muted" />
                <div class="h-5 w-5 rounded bg-muted" />
                <div class="h-5 w-5 rounded bg-muted" />
                <div class="ml-2 h-5 w-12 rounded bg-muted" />
            </div>

            <!-- Reviewer Info Skeleton -->
            <div class="flex items-center gap-3">
                <!-- Avatar -->
                <div class="h-10 w-10 rounded-full bg-muted" />
                <div class="space-y-2 flex-1">
                    <!-- Name -->
                    <div class="h-4 w-32 rounded bg-muted" />
                    <!-- Location -->
                    <div class="h-3 w-48 rounded bg-muted" />
                </div>
            </div>
        </CardHeader>

        <CardContent class="space-y-4">
            <!-- Review Text Skeleton (3 Zeilen) -->
            <div class="space-y-2">
                <div class="h-4 w-full rounded bg-muted" />
                <div class="h-4 w-full rounded bg-muted" />
                <div class="h-4 w-3/4 rounded bg-muted" />
            </div>
        </CardContent>

        <CardFooter class="flex items-center justify-between flex-wrap gap-2">
            <!-- Left Actions Skeleton -->
            <div class="flex items-center gap-2">
                <div class="h-9 w-28 rounded-md bg-muted" />
            </div>

            <!-- Right Actions Skeleton -->
            <div class="flex items-center gap-2">
                <div class="h-9 w-32 rounded-md bg-muted" />
            </div>
        </CardFooter>
    </Card>
</template>
