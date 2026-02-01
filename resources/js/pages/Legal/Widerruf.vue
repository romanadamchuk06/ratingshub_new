<script setup>
/**
 * Widerrufsbelehrung
 *
 * Pflichtangabe für B2C-Verträge (Fernabsatzrecht)
 * Informiert Verbraucher über ihr 14-tägiges Widerrufsrecht
 *
 * Hinweis: Bei digitalen Dienstleistungen kann das Widerrufsrecht
 * vorzeitig erlöschen, wenn der Kunde der sofortigen Ausführung zustimmt.
 */

import { Head, Link } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import AppLogo from '@/components/AppLogo.vue';
import { ArrowLeft, AlertCircle, Clock, Mail, FileText } from 'lucide-vue-next';
</script>

<template>
    <Head title="Widerrufsbelehrung" />

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
                <h1 class="text-3xl font-bold mb-8">Widerrufsbelehrung</h1>

                <Card class="mb-8">
                    <CardContent class="p-6 space-y-8">
                        <!-- Widerrufsrecht -->
                        <section>
                            <h2 class="text-xl font-semibold mb-4 flex items-center gap-2">
                                <AlertCircle class="h-5 w-5 text-primary" />
                                Widerrufsrecht
                            </h2>
                            <div class="text-muted-foreground space-y-3">
                                <p>
                                    Sie haben das Recht, binnen vierzehn Tagen ohne Angabe von Gründen diesen Vertrag
                                    zu widerrufen.
                                </p>
                                <p>
                                    Die Widerrufsfrist beträgt vierzehn Tage ab dem Tag des Vertragsabschlusses.
                                </p>
                                <p>
                                    Um Ihr Widerrufsrecht auszuüben, müssen Sie uns
                                </p>
                                <div class="p-4 bg-muted/50 rounded-lg">
                                    <p class="font-medium text-foreground">KuberaEcom</p>
                                    <p>Antonio Ferraro</p>
                                    <p>Im Simmerling 18A</p>
                                    <p>55411 Bingen am Rhein</p>
                                    <p class="mt-2">
                                        E-Mail:
                                        <a href="mailto:kuberaecom25@gmail.com" class="text-primary hover:underline">
                                            kuberaecom25@gmail.com
                                        </a>
                                    </p>
                                </div>
                                <p>
                                    mittels einer eindeutigen Erklärung (z. B. ein mit der Post versandter Brief oder
                                    E-Mail) über Ihren Entschluss, diesen Vertrag zu widerrufen, informieren. Sie können
                                    dafür das beigefügte Muster-Widerrufsformular verwenden, das jedoch nicht vorgeschrieben ist.
                                </p>
                                <p>
                                    Zur Wahrung der Widerrufsfrist reicht es aus, dass Sie die Mitteilung über die
                                    Ausübung des Widerrufsrechts vor Ablauf der Widerrufsfrist absenden.
                                </p>
                            </div>
                        </section>

                        <!-- Folgen des Widerrufs -->
                        <section>
                            <h2 class="text-xl font-semibold mb-4 flex items-center gap-2">
                                <Clock class="h-5 w-5 text-primary" />
                                Folgen des Widerrufs
                            </h2>
                            <div class="text-muted-foreground space-y-3">
                                <p>
                                    Wenn Sie diesen Vertrag widerrufen, haben wir Ihnen alle Zahlungen, die wir von
                                    Ihnen erhalten haben, unverzüglich und spätestens binnen vierzehn Tagen ab dem Tag
                                    zurückzuzahlen, an dem die Mitteilung über Ihren Widerruf dieses Vertrags bei uns
                                    eingegangen ist.
                                </p>
                                <p>
                                    Für diese Rückzahlung verwenden wir dasselbe Zahlungsmittel, das Sie bei der
                                    ursprünglichen Transaktion eingesetzt haben, es sei denn, mit Ihnen wurde ausdrücklich
                                    etwas anderes vereinbart; in keinem Fall werden Ihnen wegen dieser Rückzahlung
                                    Entgelte berechnet.
                                </p>
                            </div>
                        </section>

                        <!-- Besonderer Hinweis -->
                        <section>
                            <h2 class="text-xl font-semibold mb-4 flex items-center gap-2">
                                <AlertCircle class="h-5 w-5 text-yellow-500" />
                                Besonderer Hinweis
                            </h2>
                            <div class="p-4 bg-yellow-500/10 rounded-lg border border-yellow-500/20 text-muted-foreground">
                                <p class="font-medium text-foreground mb-2">
                                    Vorzeitiges Erlöschen des Widerrufsrechts bei digitalen Inhalten:
                                </p>
                                <p>
                                    Das Widerrufsrecht erlischt bei einem Vertrag über die Lieferung von nicht auf einem
                                    körperlichen Datenträger befindlichen digitalen Inhalten auch dann, wenn der Unternehmer
                                    mit der Ausführung des Vertrags begonnen hat, nachdem der Verbraucher
                                </p>
                                <ul class="list-disc list-inside mt-2 space-y-1 ml-4">
                                    <li>
                                        ausdrücklich zugestimmt hat, dass der Unternehmer mit der Ausführung des
                                        Vertrags vor Ablauf der Widerrufsfrist beginnt, und
                                    </li>
                                    <li>
                                        seine Kenntnis davon bestätigt hat, dass er durch seine Zustimmung mit Beginn
                                        der Ausführung des Vertrags sein Widerrufsrecht verliert.
                                    </li>
                                </ul>
                            </div>
                        </section>

                        <!-- Muster-Widerrufsformular -->
                        <section>
                            <h2 class="text-xl font-semibold mb-4 flex items-center gap-2">
                                <FileText class="h-5 w-5 text-primary" />
                                Muster-Widerrufsformular
                            </h2>
                            <div class="text-muted-foreground">
                                <p class="mb-4">
                                    (Wenn Sie den Vertrag widerrufen wollen, dann füllen Sie bitte dieses Formular aus
                                    und senden Sie es zurück.)
                                </p>
                                <div class="p-4 bg-muted/50 rounded-lg border space-y-4">
                                    <p>An:</p>
                                    <p>
                                        KuberaEcom<br />
                                        Antonio Ferraro<br />
                                        Im Simmerling 18A<br />
                                        55411 Bingen am Rhein<br />
                                        E-Mail: kuberaecom25@gmail.com
                                    </p>
                                    <p>
                                        Hiermit widerrufe(n) ich/wir (*) den von mir/uns (*) abgeschlossenen Vertrag
                                        über die Erbringung der folgenden Dienstleistung (*)
                                    </p>
                                    <p>_______________________________________</p>
                                    <p>Bestellt am (*) / erhalten am (*)</p>
                                    <p>_______________________________________</p>
                                    <p>Name des/der Verbraucher(s)</p>
                                    <p>_______________________________________</p>
                                    <p>Anschrift des/der Verbraucher(s)</p>
                                    <p>_______________________________________</p>
                                    <p>_______________________________________</p>
                                    <p>Datum / Unterschrift des/der Verbraucher(s)</p>
                                    <p class="text-sm italic mt-4">
                                        (*) Unzutreffendes streichen.
                                    </p>
                                </div>
                            </div>
                        </section>

                        <!-- Widerruf per E-Mail -->
                        <section>
                            <h2 class="text-xl font-semibold mb-4 flex items-center gap-2">
                                <Mail class="h-5 w-5 text-primary" />
                                Einfacher Widerruf per E-Mail
                            </h2>
                            <p class="text-muted-foreground">
                                Sie können Ihren Widerruf auch formlos per E-Mail an
                                <a href="mailto:kuberaecom25@gmail.com" class="text-primary hover:underline">
                                    kuberaecom25@gmail.com
                                </a>
                                senden. Bitte geben Sie dabei Ihren Namen, E-Mail-Adresse und ggf. Bestelldatum an.
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
                    <Link href="/datenschutz">
                        <Button variant="outline">Datenschutzerklärung</Button>
                    </Link>
                </div>
            </div>
        </div>
    </div>
</template>
