<script setup>
/**
 * Impressum-Seite
 *
 * Enthält die Angaben gemäß § 5 DDG (ehemals TMG)
 * Pflichtangaben für gewerbliche Websites in Deutschland
 */

import { Head, Link } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import AppLogo from '@/components/AppLogo.vue';
import { ArrowLeft, Mail, MapPin, FileText, Scale } from 'lucide-vue-next';
</script>

<template>
    <Head title="Impressum" />

    <div class="min-h-screen bg-background">
        <!-- Navigation -->
        <nav class="sticky top-0 z-50 border-b bg-background/95 backdrop-blur supports-[backdrop-filter]:bg-background/60">
            <div class="container mx-auto px-4">
                <div class="flex h-16 items-center justify-between">
                    <Link href="/" class="flex items-center">
                        <AppLogo />
                    </Link>
                    <Link href="/">
                        <Button variant="ghost" size="sm">
                            <ArrowLeft class="mr-2 h-4 w-4" />
                            Zurück zur Startseite
                        </Button>
                    </Link>
                </div>
            </div>
        </nav>

        <!-- Content -->
        <div class="container mx-auto px-4 py-12">
            <div class="max-w-3xl mx-auto">
                <h1 class="text-3xl font-bold mb-8">Impressum</h1>

                <Card class="mb-8">
                    <CardContent class="p-6 space-y-6">
                        <!-- Anbieter -->
                        <section>
                            <h2 class="text-xl font-semibold mb-4 flex items-center gap-2">
                                <FileText class="h-5 w-5 text-primary" />
                                Angaben gemäß § 5 DDG
                            </h2>
                            <div class="space-y-1 text-muted-foreground">
                                <p class="font-medium text-foreground">Antonio Ferraro</p>
                                <p>KuberaEcom</p>
                                <p>Im Simmerling 18A</p>
                                <p>55411 Bingen am Rhein</p>
                            </div>
                        </section>

                        <!-- Kontakt -->
                        <section>
                            <h2 class="text-xl font-semibold mb-4 flex items-center gap-2">
                                <Mail class="h-5 w-5 text-primary" />
                                Kontakt
                            </h2>
                            <div class="text-muted-foreground">
                                <p>
                                    E-Mail:
                                    <a href="mailto:kuberaecom25@gmail.com" class="text-primary hover:underline">
                                        kuberaecom25@gmail.com
                                    </a>
                                </p>
                            </div>
                        </section>

                        <!-- Umsatzsteuer-ID -->
                        <section>
                            <h2 class="text-xl font-semibold mb-4 flex items-center gap-2">
                                <Scale class="h-5 w-5 text-primary" />
                                Umsatzsteuer-ID
                            </h2>
                            <div class="text-muted-foreground">
                                <p>Umsatzsteuer-Identifikationsnummer gemäß § 27 a Umsatzsteuergesetz:</p>
                                <p class="font-medium text-foreground mt-1">DE362514826</p>
                            </div>
                        </section>

                        <!-- Redaktionell verantwortlich -->
                        <section>
                            <h2 class="text-xl font-semibold mb-4">Redaktionell verantwortlich</h2>
                            <p class="text-muted-foreground">Antonio Ferraro</p>
                        </section>

                        <!-- EU-Streitschlichtung -->
                        <section>
                            <h2 class="text-xl font-semibold mb-4">EU-Streitschlichtung</h2>
                            <div class="text-muted-foreground space-y-2">
                                <p>
                                    Die Europäische Kommission stellt eine Plattform zur Online-Streitbeilegung (OS) bereit:
                                </p>
                                <p>
                                    <a
                                        href="https://ec.europa.eu/consumers/odr/"
                                        target="_blank"
                                        rel="noopener noreferrer"
                                        class="text-primary hover:underline"
                                    >
                                        https://ec.europa.eu/consumers/odr/
                                    </a>
                                </p>
                                <p>Unsere E-Mail-Adresse finden Sie oben im Impressum.</p>
                            </div>
                        </section>

                        <!-- Verbraucherstreitbeilegung -->
                        <section>
                            <h2 class="text-xl font-semibold mb-4">Verbraucherstreitbeilegung / Universalschlichtungsstelle</h2>
                            <p class="text-muted-foreground">
                                Wir sind nicht bereit oder verpflichtet, an Streitbeilegungsverfahren vor einer
                                Verbraucherschlichtungsstelle teilzunehmen.
                            </p>
                        </section>
                    </CardContent>
                </Card>

                <!-- Links zu anderen Rechtstexten -->
                <div class="flex flex-wrap gap-4">
                    <Link href="/agb">
                        <Button variant="outline">AGB ansehen</Button>
                    </Link>
                    <Link href="/datenschutz">
                        <Button variant="outline">Datenschutzerklärung</Button>
                    </Link>
                </div>
            </div>
        </div>
    </div>
</template>
