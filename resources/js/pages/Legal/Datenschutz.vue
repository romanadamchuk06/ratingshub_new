<script setup>
/**
 * Datenschutzerklärung
 *
 * DSGVO-konforme Datenschutzerklärung für RatingsHub
 * Erklärt welche Daten wie verarbeitet werden
 */

import { Head, Link } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import AppLogo from '@/components/AppLogo.vue';
import { ArrowLeft, Shield, Database, Globe, Clock, UserCheck, AlertTriangle } from 'lucide-vue-next';
</script>

<template>
    <Head title="Datenschutzerklärung" />

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
                <h1 class="text-3xl font-bold mb-8">Datenschutzerklärung</h1>

                <Card class="mb-8">
                    <CardContent class="p-6 space-y-8">
                        <!-- 1. Datenschutz auf einen Blick -->
                        <section>
                            <h2 class="text-xl font-semibold mb-4 flex items-center gap-2">
                                <Shield class="h-5 w-5 text-primary" />
                                1. Datenschutz auf einen Blick
                            </h2>
                            <p class="text-muted-foreground">
                                Diese Datenschutzerklärung informiert Sie darüber, wie wir personenbezogene Daten
                                verarbeiten, wenn Sie unser Tool zur Verwaltung von Google-Bewertungen nutzen.
                            </p>
                        </section>

                        <!-- 2. Verantwortliche Stelle -->
                        <section>
                            <h2 class="text-xl font-semibold mb-4">2. Verantwortliche Stelle</h2>
                            <div class="text-muted-foreground">
                                <p>Verantwortlich für die Datenverarbeitung auf dieser Website ist:</p>
                                <div class="mt-2 p-4 bg-muted/50 rounded-lg">
                                    <p class="font-medium text-foreground">KuberaEcom</p>
                                    <p>Im Simmerling 18A</p>
                                    <p>55411 Bingen am Rhein</p>
                                    <p class="mt-2">
                                        E-Mail:
                                        <a href="mailto:kuberaecom25@gmail.com" class="text-primary hover:underline">
                                            kuberaecom25@gmail.com
                                        </a>
                                    </p>
                                </div>
                            </div>
                        </section>

                        <!-- 3. Datenerfassung -->
                        <section>
                            <h2 class="text-xl font-semibold mb-4 flex items-center gap-2">
                                <Database class="h-5 w-5 text-primary" />
                                3. Datenerfassung bei der Nutzung unserer Software
                            </h2>
                            <div class="text-muted-foreground space-y-4">
                                <p>Um den Dienst bereitzustellen, erfassen wir folgende Daten:</p>
                                <ul class="list-disc list-inside space-y-2 ml-4">
                                    <li>
                                        <span class="font-medium text-foreground">Anmeldedaten:</span>
                                        E-Mail-Adresse und Passwort.
                                    </li>
                                    <li>
                                        <span class="font-medium text-foreground">Google-Kontodaten:</span>
                                        Um die Software zu nutzen, verbinden Sie Ihr Google-Unternehmenskonto via OAuth.
                                        Wir erhalten Zugriff auf Ihre Standort-Informationen und die Rezensionen.
                                    </li>
                                    <li>
                                        <span class="font-medium text-foreground">Inhaltsdaten:</span>
                                        Die von Ihnen verfassten Antworten auf Rezensionen werden über unsere Server an
                                        Google weitergeleitet.
                                    </li>
                                </ul>
                            </div>
                        </section>

                        <!-- 4. Rechtsgrundlagen -->
                        <section>
                            <h2 class="text-xl font-semibold mb-4">4. Rechtsgrundlagen der Verarbeitung</h2>
                            <p class="text-muted-foreground">
                                Die Verarbeitung erfolgt auf Grundlage von <span class="font-medium text-foreground">
                                Art. 6 Abs. 1 lit. b DSGVO</span> (Vertragserfüllung). Die Nutzung der Google-Schnittstelle
                                ist notwendig, um die vertraglich vereinbarte Leistung zu erbringen.
                            </p>
                        </section>

                        <!-- 5. Google Dienste -->
                        <section>
                            <h2 class="text-xl font-semibold mb-4 flex items-center gap-2">
                                <Globe class="h-5 w-5 text-primary" />
                                5. Einbindung von Google Diensten (API)
                            </h2>
                            <div class="text-muted-foreground space-y-3">
                                <p>
                                    Unser Tool nutzt Google-APIs. Wenn Sie Ihr Konto verknüpfen, werden Daten zwischen
                                    unseren Servern und den Servern der Google Ireland Limited, Gordon House, Barrow Street,
                                    Dublin 4, Irland, übertragen.
                                </p>
                                <p>
                                    Google kann Daten auch in die USA übertragen. Wir weisen darauf hin, dass wir keinen
                                    Einfluss auf die weitere Datenverarbeitung durch Google haben.
                                </p>
                            </div>
                        </section>

                        <!-- 6. Auftragsverarbeitung -->
                        <section>
                            <h2 class="text-xl font-semibold mb-4 flex items-center gap-2">
                                <AlertTriangle class="h-5 w-5 text-primary" />
                                6. Auftragsverarbeitung (AVV)
                            </h2>
                            <div class="text-muted-foreground space-y-3">
                                <p>
                                    Da Sie über unser Tool auch personenbezogene Daten Dritter (die Namen und Texte der
                                    Personen, die Sie auf Google bewertet haben) verarbeiten, fungieren wir als Ihr
                                    Auftragsverarbeiter.
                                </p>
                                <div class="p-4 bg-primary/10 rounded-lg border border-primary/20">
                                    <p class="font-medium text-foreground">Wichtig:</p>
                                    <p class="mt-1">
                                        Wir stellen Ihnen im Kundenbereich einen Vertrag zur Auftragsverarbeitung (AVV)
                                        gemäß Art. 28 DSGVO zur Verfügung. Bitte schließen Sie diesen während des
                                        Registrierungsprozesses mit uns ab.
                                    </p>
                                </div>
                            </div>
                        </section>

                        <!-- 7. Speicherdauer -->
                        <section>
                            <h2 class="text-xl font-semibold mb-4 flex items-center gap-2">
                                <Clock class="h-5 w-5 text-primary" />
                                7. Speicherdauer
                            </h2>
                            <p class="text-muted-foreground">
                                Wir speichern Ihre Daten nur so lange, wie es für die Bereitstellung unseres Dienstes
                                oder aufgrund gesetzlicher Aufbewahrungsfristen (z. B. steuerrechtlich 10 Jahre für
                                Rechnungen) erforderlich ist.
                            </p>
                        </section>

                        <!-- 8. Ihre Rechte -->
                        <section>
                            <h2 class="text-xl font-semibold mb-4 flex items-center gap-2">
                                <UserCheck class="h-5 w-5 text-primary" />
                                8. Ihre Rechte
                            </h2>
                            <div class="text-muted-foreground">
                                <p>Sie haben jederzeit das Recht auf:</p>
                                <ul class="list-disc list-inside space-y-1 mt-2 ml-4">
                                    <li>Auskunft über Ihre gespeicherten Daten</li>
                                    <li>Berichtigung unrichtiger Daten</li>
                                    <li>Löschung Ihrer Daten</li>
                                    <li>Einschränkung der Verarbeitung</li>
                                    <li>Datenübertragbarkeit</li>
                                    <li>Widerspruch gegen die Verarbeitung</li>
                                </ul>
                                <p class="mt-4">
                                    Zur Ausübung Ihrer Rechte kontaktieren Sie uns unter:
                                    <a href="mailto:kuberaecom25@gmail.com" class="text-primary hover:underline">
                                        kuberaecom25@gmail.com
                                    </a>
                                </p>
                            </div>
                        </section>

                        <!-- 9. Beschwerderecht -->
                        <section>
                            <h2 class="text-xl font-semibold mb-4">9. Beschwerderecht bei der Aufsichtsbehörde</h2>
                            <p class="text-muted-foreground">
                                Wenn Sie der Ansicht sind, dass die Verarbeitung Ihrer personenbezogenen Daten gegen
                                die DSGVO verstößt, haben Sie das Recht, sich bei einer Datenschutz-Aufsichtsbehörde
                                zu beschweren. Die zuständige Aufsichtsbehörde für Rheinland-Pfalz ist:
                            </p>
                            <div class="mt-2 p-4 bg-muted/50 rounded-lg text-muted-foreground">
                                <p class="font-medium text-foreground">
                                    Der Landesbeauftragte für den Datenschutz und die Informationsfreiheit Rheinland-Pfalz
                                </p>
                                <p>Hintere Bleiche 34</p>
                                <p>55116 Mainz</p>
                                <p class="mt-2">
                                    Website:
                                    <a
                                        href="https://www.datenschutz.rlp.de"
                                        target="_blank"
                                        rel="noopener noreferrer"
                                        class="text-primary hover:underline"
                                    >
                                        www.datenschutz.rlp.de
                                    </a>
                                </p>
                            </div>
                        </section>

                        <!-- 10. Cookies -->
                        <section>
                            <h2 class="text-xl font-semibold mb-4">10. Cookies</h2>
                            <div class="text-muted-foreground space-y-3">
                                <p>
                                    Unsere Website verwendet Cookies. Diese sind kleine Textdateien, die auf Ihrem
                                    Endgerät gespeichert werden.
                                </p>
                                <p>
                                    <span class="font-medium text-foreground">Technisch notwendige Cookies:</span>
                                    Diese sind für den Betrieb der Website erforderlich (z. B. Session-Cookie für die
                                    Anmeldung). Rechtsgrundlage ist Art. 6 Abs. 1 lit. f DSGVO.
                                </p>
                            </div>
                        </section>

                        <!-- 11. SSL-Verschlüsselung -->
                        <section>
                            <h2 class="text-xl font-semibold mb-4">11. SSL-/TLS-Verschlüsselung</h2>
                            <p class="text-muted-foreground">
                                Diese Website nutzt aus Sicherheitsgründen und zum Schutz der Übertragung vertraulicher
                                Inhalte eine SSL-/TLS-Verschlüsselung. Eine verschlüsselte Verbindung erkennen Sie daran,
                                dass die Adresszeile des Browsers von "http://" auf "https://" wechselt und an dem
                                Schloss-Symbol in Ihrer Browserzeile.
                            </p>
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
                    <Link href="/agb">
                        <Button variant="outline">AGB ansehen</Button>
                    </Link>
                </div>
            </div>
        </div>
    </div>
</template>
