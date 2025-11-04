<script setup>
import { Head } from '@inertiajs/vue3';
import { Wrench, Clock, Coffee } from 'lucide-vue-next';
import { ref, onMounted, onUnmounted } from 'vue';

const timeLeft = ref('');
const progress = ref(0);

const updateProgress = () => {
    progress.value = (progress.value + 0.5) % 100;
};

let interval;

onMounted(() => {
    interval = setInterval(updateProgress, 100);
});

onUnmounted(() => {
    clearInterval(interval);
});
</script>

<template>
    <Head title="503 - Wartungsarbeiten" />

    <div class="flex min-h-screen items-center justify-center bg-gradient-to-br from-purple-50 via-white to-pink-50 p-4 dark:from-gray-900 dark:via-gray-800 dark:to-gray-900">
        <div class="w-full max-w-2xl text-center">
            <!-- Animated Wrench -->
            <div class="mb-8 flex justify-center">
                <div class="relative">
                    <Wrench class="h-32 w-32 text-purple-600 dark:text-purple-400 animate-bounce" />
                    <div class="absolute -right-2 -top-2">
                        <div class="flex h-8 w-8 items-center justify-center rounded-full bg-purple-600 text-white dark:bg-purple-400 animate-ping">
                        </div>
                    </div>
                </div>
            </div>

            <!-- Haupttitel -->
            <h1 class="mb-4 text-4xl font-bold text-gray-900 dark:text-white md:text-5xl">
                Wartungsarbeiten
            </h1>

            <p class="mb-8 text-xl text-gray-600 dark:text-gray-300">
                Wir machen uns gerade noch schöner! 💅
            </p>

            <!-- Was passiert -->
            <div class="mb-8 rounded-xl border border-purple-200 bg-purple-50 p-6 text-left shadow-lg dark:border-purple-900 dark:bg-purple-950">
                <h2 class="mb-4 flex items-center gap-2 text-lg font-semibold text-purple-900 dark:text-purple-100">
                    <Coffee class="h-5 w-5" />
                    Was passiert gerade?
                </h2>
                <div class="space-y-3 text-purple-800 dark:text-purple-200">
                    <p class="flex items-start gap-3">
                        <span class="text-xl">🔧</span>
                        <span>Wir führen geplante Wartungsarbeiten durch, um dir noch besseren Service zu bieten.</span>
                    </p>
                    <p class="flex items-start gap-3">
                        <span class="text-xl">⚡</span>
                        <span>Neue Features werden installiert und bestehende optimiert.</span>
                    </p>
                    <p class="flex items-start gap-3">
                        <span class="text-xl">🚀</span>
                        <span>Wir sind bald wieder zurück - versprochen!</span>
                    </p>
                </div>
            </div>

            <!-- Progress Bar -->
            <div class="mb-8">
                <div class="mb-2 flex items-center justify-between text-sm text-gray-600 dark:text-gray-400">
                    <span class="flex items-center gap-2">
                        <Clock class="h-4 w-4" />
                        Fortschritt
                    </span>
                    <span>{{ Math.round(progress) }}%</span>
                </div>
                <div class="h-3 overflow-hidden rounded-full bg-gray-200 dark:bg-gray-700">
                    <div
                        class="h-full rounded-full bg-gradient-to-r from-purple-600 to-pink-600 transition-all duration-300 dark:from-purple-400 dark:to-pink-400"
                        :style="{ width: progress + '%' }"
                    ></div>
                </div>
            </div>

            <!-- Lustige Zeitvertreibe -->
            <div class="mb-8 rounded-xl border border-gray-200 bg-white p-6 text-left dark:border-gray-700 dark:bg-gray-800">
                <h2 class="mb-4 text-lg font-semibold text-gray-900 dark:text-white">
                    💡 Während du wartest, könntest du:
                </h2>
                <ul class="space-y-2 text-gray-600 dark:text-gray-300">
                    <li>☕ Einen Kaffee machen (oder Tee, wir urteilen nicht)</li>
                    <li>🧘 Eine kurze Meditation einlegen</li>
                    <li>🎮 Ein schnelles Spiel zocken</li>
                    <li>📱 Deine Social Media checken</li>
                    <li>🌱 Die Pflanzen gießen (die haben es verdient!)</li>
                    <li>🎵 Dein Lieblingslied hören</li>
                </ul>
            </div>

            <!-- Info Box -->
            <div class="rounded-xl border-2 border-dashed border-purple-300 bg-purple-50/50 p-6 dark:border-purple-700 dark:bg-purple-950/50">
                <p class="text-gray-700 dark:text-gray-300">
                    <strong>Keine Sorge!</strong> Deine Daten sind sicher und wir sind gleich wieder für dich da.
                    Die Seite wird automatisch neu geladen, sobald wir fertig sind. ✨
                </p>
            </div>

            <!-- Lustiges Detail am Ende -->
            <p class="mt-12 text-sm text-gray-500 dark:text-gray-400">
                Status: Server beim Friseur 💈 | Dauert nicht mehr lange!
            </p>
        </div>
    </div>
</template>

<style scoped>
@keyframes bounce {
    0%, 100% {
        transform: translateY(-5%);
        animation-timing-function: cubic-bezier(0.8, 0, 1, 1);
    }
    50% {
        transform: translateY(0);
        animation-timing-function: cubic-bezier(0, 0, 0.2, 1);
    }
}
</style>
