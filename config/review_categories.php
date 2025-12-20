<?php

/**
 * Review Kategorien für Sentiment-Analyse
 *
 * Diese Kategorien sind allgemein gehalten und passen auf verschiedene Business-Typen
 * (Restaurant, Hotel, Dienstleister, Einzelhandel, etc.)
 */
return [

    // 12 Kategorien für umfassende Review-Analyse
    'categories' => [
        // === HAUPTKRITERIEN ===
        'service' => [
            'name' => 'Service',
            'description' => 'Qualität und Umfang der erbrachten Dienstleistung',
            'keywords' => ['service', 'bedienung', 'betreuung', 'aufmerksamkeit', 'hilfsbereit', 'kundenservice'],
            'type' => 'main',
        ],
        'quality' => [
            'name' => 'Qualität',
            'description' => 'Qualität des Produkts oder der Dienstleistung',
            'keywords' => ['qualität', 'quality', 'gut', 'schlecht', 'hochwertig', 'minderwertig', 'produkt', 'ware', 'arbeit'],
            'type' => 'main',
        ],
        'price' => [
            'name' => 'Preis-Leistung',
            'description' => 'Verhältnis von Preis zur gebotenen Leistung',
            'keywords' => ['preis', 'teuer', 'günstig', 'wert', 'kosten', 'geld', 'preis-leistung', 'fair', 'überteuert'],
            'type' => 'main',
        ],
        'friendliness' => [
            'name' => 'Freundlichkeit',
            'description' => 'Freundlichkeit und Höflichkeit des Personals',
            'keywords' => ['freundlich', 'nett', 'höflich', 'unfreundlich', 'unhöflich', 'respektlos', 'personal', 'umgang'],
            'type' => 'main',
        ],

        // === WEITERE KRITERIEN ===
        'speed' => [
            'name' => 'Schnelligkeit',
            'description' => 'Wartezeit und Bearbeitungsgeschwindigkeit',
            'keywords' => ['schnell', 'langsam', 'wartezeit', 'warten', 'zeit', 'dauer', 'lieferung', 'montage', 'zügig'],
            'type' => 'secondary',
        ],
        'communication' => [
            'name' => 'Kommunikation',
            'description' => 'Qualität der Kommunikation und Information',
            'keywords' => ['kommunikation', 'information', 'erklärung', 'verständlich', 'nachfrage', 'beratung', 'erreichbar', 'telefonisch'],
            'type' => 'secondary',
        ],
        'reliability' => [
            'name' => 'Zuverlässigkeit',
            'description' => 'Einhaltung von Terminen, Versprechen und Erwartungen',
            'keywords' => ['zuverlässig', 'pünktlich', 'termin', 'versprochen', 'unzuverlässig', 'verlässlich', 'verlass', 'zusage'],
            'type' => 'secondary',
        ],
        'cleanliness' => [
            'name' => 'Sauberkeit',
            'description' => 'Sauberkeit und Ordnung der Räumlichkeiten/Produkte',
            'keywords' => ['sauber', 'schmutzig', 'hygiene', 'ordnung', 'gepflegt', 'dreckig', 'unsauber'],
            'type' => 'secondary',
        ],
        'competence' => [
            'name' => 'Kompetenz',
            'description' => 'Fachwissen und Professionalität',
            'keywords' => ['kompetent', 'fachlich', 'wissen', 'erfahrung', 'professionell', 'expertise', 'können', 'inkompetent'],
            'type' => 'secondary',
        ],
        'atmosphere' => [
            'name' => 'Atmosphäre',
            'description' => 'Ambiente und Wohlfühlfaktor',
            'keywords' => ['atmosphäre', 'ambiente', 'stimmung', 'gemütlich', 'einrichtung', 'design', 'umgebung'],
            'type' => 'secondary',
        ],
        'accessibility' => [
            'name' => 'Erreichbarkeit',
            'description' => 'Lage, Parkplätze, Öffnungszeiten',
            'keywords' => ['parkplatz', 'parken', 'lage', 'erreichbar', 'öffnungszeiten', 'standort', 'zugang', 'barrierefrei'],
            'type' => 'secondary',
        ],
        'recommendation' => [
            'name' => 'Weiterempfehlung',
            'description' => 'Ob der Kunde das Unternehmen weiterempfehlen würde',
            'keywords' => ['empfehlen', 'empfehlung', 'wiederkommen', 'wieder', 'nochmal', 'weiterempfehlen', 'weiter'],
            'type' => 'secondary',
        ],
    ],

    // Sentiment-Levels (Ampel-System: Rot/Gelb/Grün)
    'sentiments' => [
        'positive' => [
            'label' => 'Positiv',
            'color' => 'green', // Grün = Alles gut
            'icon' => '👍',
        ],
        'neutral' => [
            'label' => 'Neutral',
            'color' => 'yellow', // Gelb = Mittel/Achtung
            'icon' => '➖',
        ],
        'negative' => [
            'label' => 'Negativ',
            'color' => 'red', // Rot = Problem/Handlungsbedarf
            'icon' => '👎',
        ],
    ],

];
