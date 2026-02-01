<script setup>
/**
 * Allgemeine Geschäftsbedingungen (AGB)
 *
 * Rechtliche Grundlage für die Nutzung von RatingsHub
 * Regelt das Vertragsverhältnis zwischen Anbieter und Kunde
 */

import { Head, Link } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import AppLogo from '@/components/AppLogo.vue';
import { ArrowLeft } from 'lucide-vue-next';
</script>

<template>
    <Head title="Allgemeine Geschäftsbedingungen" />

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
                <h1 class="text-3xl font-bold mb-2">Allgemeine Geschäftsbedingungen</h1>
                <p class="text-muted-foreground mb-8">für RatingsHub</p>

                <Card class="mb-8">
                    <CardContent class="p-6 space-y-8">
                        <!-- § 1 Gegenstand -->
                        <section>
                            <h2 class="text-xl font-semibold mb-4">§ 1 Gegenstand des Vertrages</h2>
                            <p class="text-muted-foreground">
                                Gegenstand dieses Vertrages ist die Bereitstellung der Software „RatingsHub" als
                                Software-as-a-Service (SaaS). Die Software ermöglicht es dem Kunden, Google-Bewertungen
                                zu verwalten und über die Schnittstelle der Software darauf zu antworten.
                            </p>
                        </section>

                        <!-- § 2 Nutzungsvoraussetzungen -->
                        <section>
                            <h2 class="text-xl font-semibold mb-4">§ 2 Nutzungsvoraussetzungen & Drittplattformen</h2>
                            <div class="text-muted-foreground space-y-3">
                                <p>
                                    <span class="font-medium text-foreground">(1)</span> Zur Nutzung der Software ist ein
                                    aktives Google-Unternehmenskonto erforderlich.
                                </p>
                                <p>
                                    <span class="font-medium text-foreground">(2)</span> Die Software kommuniziert über
                                    offizielle Schnittstellen (APIs) mit Google. Wir haben keinen Einfluss auf Änderungen
                                    der Nutzungsbedingungen oder technischen Spezifikationen durch Google. Sollte Google
                                    den Zugriff einschränken oder einstellen, hat dies keinen Einfluss auf die Wirksamkeit
                                    des Vertrages, sofern die Kernfunktion unserer Software anderweitig aufrechterhalten
                                    werden kann.
                                </p>
                            </div>
                        </section>

                        <!-- § 3 Pflichten des Kunden -->
                        <section>
                            <h2 class="text-xl font-semibold mb-4">§ 3 Pflichten des Kunden (Inhalte)</h2>
                            <div class="text-muted-foreground space-y-3">
                                <p>
                                    <span class="font-medium text-foreground">(1)</span> Der Kunde ist allein verantwortlich
                                    für die Antworten, die er über die Software auf Google-Bewertungen verfasst.
                                </p>
                                <p>
                                    <span class="font-medium text-foreground">(2)</span> Der Kunde verpflichtet sich, keine
                                    Inhalte zu veröffentlichen, die gegen geltendes Recht, gute Sitten oder die Richtlinien
                                    von Google verstoßen (z. B. Beleidigungen, Spam, falsche Tatsachenbehauptungen).
                                </p>
                                <p>
                                    <span class="font-medium text-foreground">(3)</span> Wir prüfen die Inhalte des Kunden
                                    nicht vorab. Der Kunde stellt uns von sämtlichen Ansprüchen Dritter frei, die aufgrund
                                    seiner Antworten entstehen.
                                </p>
                            </div>
                        </section>

                        <!-- § 4 Verfügbarkeit -->
                        <section>
                            <h2 class="text-xl font-semibold mb-4">§ 4 Verfügbarkeit der Software</h2>
                            <p class="text-muted-foreground">
                                Wir streben eine Verfügbarkeit der Software von 98% im Jahresmittel an. Hiervon ausgenommen
                                sind Wartungszeiten sowie Ausfälle, die durch Probleme bei Drittanbietern
                                (z. B. Google-API-Ausfälle) oder höhere Gewalt verursacht werden.
                            </p>
                        </section>

                        <!-- § 5 Laufzeit und Kündigung -->
                        <section>
                            <h2 class="text-xl font-semibold mb-4">§ 5 Laufzeit und Kündigung</h2>
                            <div class="text-muted-foreground space-y-3">
                                <p>
                                    <span class="font-medium text-foreground">(1)</span> Der Vertrag wird als Abonnement
                                    mit einer Laufzeit von einem Monat geschlossen.
                                </p>
                                <p>
                                    <span class="font-medium text-foreground">(2)</span> Der Kunde kann das Abonnement
                                    jederzeit ohne Einhaltung einer Kündigungsfrist direkt über das Benutzer-Dashboard kündigen.
                                </p>
                                <p>
                                    <span class="font-medium text-foreground">(3)</span> Nach der Kündigung bleibt der
                                    Zugang zum Dashboard und zu den Google-Bewertungsfunktionen bis zum Ende des bereits
                                    bezahlten Monatsturnus bestehen.
                                </p>
                                <p>
                                    <span class="font-medium text-foreground">(4)</span> Es erfolgt keine automatische
                                    Verlängerung des Abonnements über den Zeitraum hinaus, in dem die Kündigung ausgesprochen wurde.
                                </p>
                            </div>
                        </section>

                        <!-- § 6 Haftungsbeschränkung -->
                        <section>
                            <h2 class="text-xl font-semibold mb-4">§ 6 Haftungsbeschränkung</h2>
                            <p class="text-muted-foreground">
                                Wir haften für Schäden nur bei Vorsatz oder grober Fahrlässigkeit. Wir haften insbesondere
                                nicht für die Löschung von Konten durch Google oder Ranking-Verluste, die durch die Nutzung
                                der Software oder die Art der Kundenantworten entstehen könnten.
                            </p>
                        </section>

                        <!-- § 7 Schlussbestimmungen -->
                        <section>
                            <h2 class="text-xl font-semibold mb-4">§ 7 Schlussbestimmungen</h2>
                            <div class="text-muted-foreground space-y-3">
                                <p>
                                    <span class="font-medium text-foreground">(1)</span> Es gilt das Recht der
                                    Bundesrepublik Deutschland.
                                </p>
                                <p>
                                    <span class="font-medium text-foreground">(2)</span> Gerichtsstand ist, soweit
                                    gesetzlich zulässig, Bingen am Rhein.
                                </p>
                                <p>
                                    <span class="font-medium text-foreground">(3)</span> Sollten einzelne Bestimmungen
                                    dieser AGB unwirksam sein oder werden, bleibt die Wirksamkeit der übrigen Bestimmungen
                                    unberührt.
                                </p>
                            </div>
                        </section>

                        <!-- Stand -->
                        <section class="pt-4 border-t">
                            <p class="text-sm text-muted-foreground">
                                Stand: Januar 2025
                            </p>
                        </section>
                    </CardContent>
                </Card>

                <!-- Links zu anderen Rechtstexten -->
                <div class="flex flex-wrap gap-4">
                    <Link href="/impressum">
                        <Button variant="outline">Impressum ansehen</Button>
                    </Link>
                    <Link href="/datenschutz">
                        <Button variant="outline">Datenschutzerklärung</Button>
                    </Link>
                </div>
            </div>
        </div>
    </div>
</template>
