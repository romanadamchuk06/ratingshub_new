<script setup>
import { Head, Link } from '@inertiajs/vue3';
import { Home, Search, ArrowLeft } from 'lucide-vue-next';
import { ref, onMounted } from 'vue';

const floatingEmojis = ['🤔', '😅', '🔍', '🗺️', '🧭', '📍', '🎯', '🌟'];
const randomEmoji = ref('🤔');

onMounted(() => {
    randomEmoji.value = floatingEmojis[Math.floor(Math.random() * floatingEmojis.length)];
});
</script>

<template>
    <Head title="404 - Seite nicht gefunden" />

    <div class="flex min-h-screen items-center justify-center bg-gradient-to-br from-blue-50 via-white to-purple-50 p-4 dark:from-gray-900 dark:via-gray-800 dark:to-gray-900">
        <div class="w-full max-w-2xl text-center">
            <!-- Animated 404 -->
            <div class="relative mb-8">
                <div class="animate-bounce text-9xl font-black text-transparent bg-clip-text bg-gradient-to-r from-blue-600 to-purple-600 dark:from-blue-400 dark:to-purple-400">
                    404
                </div>
                <div class="absolute left-1/2 top-1/2 -translate-x-1/2 -translate-y-1/2 text-6xl animate-pulse">
                    {{ randomEmoji }}
                </div>
            </div>

            <!-- Lustige Nachricht -->
            <h1 class="mb-4 text-3xl font-bold text-gray-900 dark:text-white md:text-4xl">
                Ups! Diese Seite hat sich versteckt
            </h1>

            <p class="mb-8 text-lg text-gray-600 dark:text-gray-300">
                Sieht so aus, als hätte diese Seite ein besseres Versteck gefunden als wir dachten.
                Vielleicht macht sie gerade Urlaub? 🏖️
            </p>

            <!-- Lustige Vorschläge -->
            <div class="mb-8 rounded-xl border border-gray-200 bg-white p-6 text-left shadow-lg dark:border-gray-700 dark:bg-gray-800">
                <h2 class="mb-4 text-lg font-semibold text-gray-900 dark:text-white">
                    Was du jetzt tun kannst:
                </h2>
                <ul class="space-y-3 text-gray-600 dark:text-gray-300">
                    <li class="flex items-start gap-3">
                        <span class="text-xl">🔄</span>
                        <span>Die URL nochmal überprüfen (Tippfehler passieren den Besten!)</span>
                    </li>
                    <li class="flex items-start gap-3">
                        <span class="text-xl">🏠</span>
                        <span>Zurück zur Startseite gehen und von vorne anfangen</span>
                    </li>
                    <li class="flex items-start gap-3">
                        <span class="text-xl">🔍</span>
                        <span>Nach dem suchen, was du eigentlich finden wolltest</span>
                    </li>
                    <li class="flex items-start gap-3">
                        <span class="text-xl">☕</span>
                        <span>Oder eine Kaffeepause machen - das hilft auch manchmal</span>
                    </li>
                </ul>
            </div>

            <!-- Action Buttons -->
            <div class="flex flex-col gap-4 sm:flex-row sm:justify-center">
                <Link
                    href="/"
                    class="inline-flex items-center justify-center gap-2 rounded-lg bg-gradient-to-r from-blue-600 to-purple-600 px-6 py-3 text-white font-medium transition-all hover:scale-105 hover:shadow-xl"
                >
                    <Home class="h-5 w-5" />
                    Zurück zur Startseite
                </Link>

                <button
                    @click="$router.back()"
                    class="inline-flex items-center justify-center gap-2 rounded-lg border-2 border-gray-300 bg-white px-6 py-3 font-medium text-gray-700 transition-all hover:border-gray-400 hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-300 dark:hover:border-gray-500 dark:hover:bg-gray-700"
                >
                    <ArrowLeft class="h-5 w-5" />
                    Eine Seite zurück
                </button>
            </div>

            <!-- Lustiges Detail am Ende -->
            <p class="mt-12 text-sm text-gray-500 dark:text-gray-400">
                Fehlercode: 404 | Status: Seite spielt Verstecken 🙈
            </p>
        </div>
    </div>
</template>
