<script setup>
import { Head, Link } from '@inertiajs/vue3';
import { Home, RefreshCw, Mail } from 'lucide-vue-next';
import { ref, onMounted } from 'vue';

const errorMessages = [
    'Unser Server hatte einen kleinen Nervenzusammenbruch',
    'Houston, wir haben ein Problem',
    'Der Hamster im Server-Rad braucht eine Pause',
    'Etwas ist spektakulär schiefgegangen',
    'Der Server hat heute einfach keine Lust',
];

const randomMessage = ref('');

onMounted(() => {
    randomMessage.value = errorMessages[Math.floor(Math.random() * errorMessages.length)];
});
</script>

<template>
    <Head title="500 - Server Fehler" />

    <div class="flex min-h-screen items-center justify-center bg-gradient-to-br from-red-50 via-white to-orange-50 p-4 dark:from-gray-900 dark:via-gray-800 dark:to-gray-900">
        <div class="w-full max-w-2xl text-center">
            <!-- Animated 500 -->
            <div class="relative mb-8">
                <div class="text-9xl font-black text-transparent bg-clip-text bg-gradient-to-r from-red-600 to-orange-600 dark:from-red-400 dark:to-orange-400">
                    500
                </div>
                <div class="absolute left-1/2 top-1/2 -translate-x-1/2 -translate-y-1/2">
                    <div class="text-6xl animate-spin">⚙️</div>
                </div>
            </div>

            <!-- Lustige Nachricht -->
            <h1 class="mb-4 text-3xl font-bold text-gray-900 dark:text-white md:text-4xl">
                {{ randomMessage }}
            </h1>

            <p class="mb-8 text-lg text-gray-600 dark:text-gray-300">
                Keine Panik! Unsere besten Techniker (und ein sehr motivierter Praktikant)
                sind bereits dabei, das Problem zu lösen. 🔧
            </p>

            <!-- Lustige Erklärung -->
            <div class="mb-8 rounded-xl border border-gray-200 bg-white p-6 text-left shadow-lg dark:border-gray-700 dark:bg-gray-800">
                <h2 class="mb-4 text-lg font-semibold text-gray-900 dark:text-white">
                    Was ist passiert?
                </h2>
                <div class="space-y-3 text-gray-600 dark:text-gray-300">
                    <p class="flex items-start gap-3">
                        <span class="text-xl">🎲</span>
                        <span>Irgendwo in den Tiefen unseres Servers ist etwas Unvorhergesehenes passiert.</span>
                    </p>
                    <p class="flex items-start gap-3">
                        <span class="text-xl">🔬</span>
                        <span>Wir untersuchen den Fall gerade mit größter Sorgfalt (und viel Kaffee).</span>
                    </p>
                    <p class="flex items-start gap-3">
                        <span class="text-xl">⏱️</span>
                        <span>Das Problem ist hoffentlich nur vorübergehend!</span>
                    </p>
                </div>
            </div>

            <!-- Was tun Box -->
            <div class="mb-8 rounded-xl border border-blue-200 bg-blue-50 p-6 text-left dark:border-blue-900 dark:bg-blue-950">
                <h2 class="mb-4 text-lg font-semibold text-blue-900 dark:text-blue-100">
                    💡 Was du jetzt tun kannst:
                </h2>
                <ul class="space-y-2 text-blue-800 dark:text-blue-200">
                    <li>• Die Seite in ein paar Minuten nochmal versuchen</li>
                    <li>• Einen Kaffee trinken und entspannen ☕</li>
                    <li>• Zur Startseite zurückkehren</li>
                    <li>• Wenn das Problem weiterhin besteht, kontaktiere uns</li>
                </ul>
            </div>

            <!-- Action Buttons -->
            <div class="flex flex-col gap-4 sm:flex-row sm:justify-center">
                <Link
                    href="/"
                    class="inline-flex items-center justify-center gap-2 rounded-lg bg-gradient-to-r from-red-600 to-orange-600 px-6 py-3 text-white font-medium transition-all hover:scale-105 hover:shadow-xl"
                >
                    <Home class="h-5 w-5" />
                    Zurück zur Startseite
                </Link>

                <button
                    @click="window.location.reload()"
                    class="inline-flex items-center justify-center gap-2 rounded-lg border-2 border-gray-300 bg-white px-6 py-3 font-medium text-gray-700 transition-all hover:border-gray-400 hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-300 dark:hover:border-gray-500 dark:hover:bg-gray-700"
                >
                    <RefreshCw class="h-5 w-5" />
                    Seite neu laden
                </button>
            </div>

            <!-- Lustiges Detail am Ende -->
            <p class="mt-12 text-sm text-gray-500 dark:text-gray-400">
                Fehlercode: 500 | Status: Server macht gerade Yoga 🧘
            </p>
        </div>
    </div>
</template>
