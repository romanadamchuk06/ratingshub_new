<script setup>
/**
 * COOKIE BANNER (DSGVO-konform)
 * ==============================
 *
 * Zeigt einen Cookie-Hinweis beim ersten Besuch.
 * Speichert die Zustimmung im localStorage.
 *
 * Funktionsweise:
 * - Prüft ob 'cookie_consent' im localStorage existiert
 * - Wenn nicht: Banner anzeigen
 * - Bei Klick auf "Akzeptieren": consent speichern, Banner ausblenden
 * - Bei Klick auf "Nur notwendige": nur essential cookies erlauben
 *
 * Hinweis: Diese App nutzt nur technisch notwendige Cookies (Session, CSRF).
 * Der Banner ist trotzdem sinnvoll für Transparenz und DSGVO-Konformität.
 */

import { ref, onMounted } from 'vue';
import { Link } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import { Cookie, X } from 'lucide-vue-next';

// Banner-Sichtbarkeit
const showBanner = ref(false);

// Prüfe beim Laden, ob bereits Consent gegeben wurde
onMounted(() => {
    const consent = localStorage.getItem('cookie_consent');
    if (!consent) {
        // Kleiner Delay damit die Seite erst laden kann
        setTimeout(() => {
            showBanner.value = true;
        }, 500);
    }
});

// Alle Cookies akzeptieren
const acceptAll = () => {
    localStorage.setItem('cookie_consent', JSON.stringify({
        essential: true,
        analytics: true,
        marketing: true,
        timestamp: new Date().toISOString()
    }));
    showBanner.value = false;
};

// Nur notwendige Cookies
const acceptEssential = () => {
    localStorage.setItem('cookie_consent', JSON.stringify({
        essential: true,
        analytics: false,
        marketing: false,
        timestamp: new Date().toISOString()
    }));
    showBanner.value = false;
};
</script>

<template>
    <Transition
        enter-active-class="transition-all duration-300 ease-out"
        enter-from-class="translate-y-full opacity-0"
        enter-to-class="translate-y-0 opacity-100"
        leave-active-class="transition-all duration-200 ease-in"
        leave-from-class="translate-y-0 opacity-100"
        leave-to-class="translate-y-full opacity-0"
    >
        <div
            v-if="showBanner"
            class="fixed bottom-0 left-0 right-0 z-50 p-4 md:p-6"
        >
            <div class="mx-auto max-w-4xl">
                <div class="rounded-lg border bg-background/95 backdrop-blur shadow-lg p-4 md:p-6">
                    <div class="flex flex-col md:flex-row md:items-center gap-4">
                        <!-- Icon & Text -->
                        <div class="flex-1">
                            <div class="flex items-start gap-3">
                                <div class="rounded-lg bg-primary/10 p-2 shrink-0">
                                    <Cookie class="h-5 w-5 text-primary" />
                                </div>
                                <div>
                                    <h3 class="font-semibold text-foreground mb-1">
                                        Cookie-Einstellungen
                                    </h3>
                                    <p class="text-sm text-muted-foreground">
                                        Wir verwenden Cookies, um Ihnen die bestmögliche Erfahrung auf unserer Website
                                        zu bieten. Technisch notwendige Cookies sind für den Betrieb der Seite erforderlich.
                                        Weitere Informationen finden Sie in unserer
                                        <Link href="/datenschutz" class="text-primary hover:underline">
                                            Datenschutzerklärung
                                        </Link>.
                                    </p>
                                </div>
                            </div>
                        </div>

                        <!-- Buttons -->
                        <div class="flex flex-col sm:flex-row gap-2 shrink-0">
                            <Button
                                variant="outline"
                                size="sm"
                                @click="acceptEssential"
                            >
                                Nur notwendige
                            </Button>
                            <Button
                                size="sm"
                                @click="acceptAll"
                            >
                                Alle akzeptieren
                            </Button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </Transition>
</template>
