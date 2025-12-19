# 🤖 AI Integration - Review Response Generator

RatingsHub nutzt OpenAI GPT-4 um automatisch professionelle Antworten auf Kundenbewertungen zu generieren.

## 🌟 Features

- ✅ **5 Antwort-Stile:** Professional, Friendly, Concise, Enthusiastic, Empathetic
- ✅ **Editierbar:** Jede AI-generierte Antwort kann vor dem Senden bearbeitet werden
- ✅ **Kontext-bewusst:** Berücksichtigt Rating, Bewertungstext, Standort und Business-Name
- ✅ **Schnell:** Generiert Antworten in 2-5 Sekunden
- ✅ **Mehrsprachig:** Aktuell Deutsch, leicht erweiterbar

---

## 🚀 Setup

### 1. OpenAI API Key besorgen

1. Gehe zu [OpenAI Platform](https://platform.openai.com/)
2. Erstelle einen Account (falls noch nicht vorhanden)
3. Navigiere zu **API Keys**
4. Klicke auf **Create new secret key**
5. Kopiere den Key (beginnt mit `sk-proj-...`)

### 2. API Key in .env eintragen

```bash
# In deiner .env Datei:
OPENAI_API_KEY=sk-proj-DEIN_API_KEY_HIER
```

### 3. Config Cache leeren

```bash
php artisan config:clear
```

Das war's! Die AI-Integration ist jetzt aktiv.

---

## 💡 Verwendung

### In der ReviewCard

1. Klicke auf **"Antworten"** Button bei einem Review
2. Klicke auf **"Stil wählen"** (optional)
3. Wähle deinen bevorzugten Stil aus
4. Klicke auf **"Antwort mit AI generieren"**
5. Warte 2-5 Sekunden
6. Die generierte Antwort erscheint im Textfeld
7. **Bearbeite** die Antwort nach Belieben
8. Klicke auf **"Antwort senden"**

---

## 🎨 Verfügbare Stile

### 1. Professionell
**Beschreibung:** Formell und geschäftsmäßig
**Beispiel:**
> "Vielen Dank für Ihre Bewertung. Wir freuen uns sehr über Ihr positives Feedback bezüglich unseres Services. Wir werden auch in Zukunft unser Bestes geben, um Ihre Erwartungen zu erfüllen."

### 2. Freundlich (Standard)
**Beschreibung:** Warm und persönlich
**Beispiel:**
> "Vielen lieben Dank für deine tolle Bewertung! 😊 Es freut uns riesig, dass dir unser Service gefallen hat. Wir freuen uns schon darauf, dich bald wiederzusehen!"

### 3. Kurz & Knapp
**Beschreibung:** Prägnant und auf den Punkt
**Beispiel:**
> "Danke für dein Feedback! Freut uns sehr. Bis bald!"

### 4. Enthusiastisch
**Beschreibung:** Energiegeladen und positiv
**Beispiel:**
> "WOW, vielen Dank für diese fantastische Bewertung! 🌟 Wir sind total begeistert, dass es dir bei uns so gut gefallen hat! Komm bald wieder vorbei!"

### 5. Empathisch
**Beschreibung:** Verständnisvoll und mitfühlend
**Beispiel:**
> "Vielen Dank, dass du dir die Zeit genommen hast, uns Feedback zu geben. Wir verstehen deine Punkte und arbeiten bereits daran, uns zu verbessern. Dein Wohlbefinden ist uns wichtig."

---

## 🔧 Technische Details

### Service-Architektur

```
ReviewCard.vue (Frontend)
    ↓
AIResponseController.php (API Endpoint)
    ↓
AIResponseService.php (Business Logic)
    ↓
OpenAI GPT-4o-mini API
```

### API Endpoint

```http
POST /reviews/{review}/ai-response
Content-Type: application/json

{
    "style": "friendly",
    "context": {
        "business_name": "Mein Restaurant",
        "location_name": "München"
    }
}
```

**Response:**
```json
{
    "success": true,
    "response": "Generierte Antwort...",
    "style": "friendly",
    "review_id": 123
}
```

### Verwendetes Modell

**Model:** `gpt-4o-mini`
- Schneller und günstiger als GPT-4
- Perfekt für kurze Texte wie Review-Antworten
- Hohe Qualität bei niedrigen Kosten

### Kosten

Geschätzte Kosten pro AI-Antwort: **~0.001€** (0.1 Cent)
- 1000 Antworten ≈ 1€
- Sehr kostengünstig für hohen Mehrwert

---

## 🛡️ Sicherheit & Best Practices

### API Key Sicherheit

- ✅ API Key NUR in .env speichern
- ✅ .env NIEMALS in Git committen
- ✅ Unterschiedliche Keys für Dev/Staging/Production
- ✅ API Key regelmäßig rotieren

### Rate Limiting

OpenAI hat Rate Limits:
- **Free Tier:** 3 Requests/Minute
- **Tier 1:** 500 Requests/Minute (nach $5 Guthaben)
- **Tier 2+:** 5000+ Requests/Minute

RatingsHub Implementation:
- Frontend zeigt Loading State während Generierung
- Backend fängt Rate-Limit-Errors ab
- User bekommt klare Fehlermeldung

---

## 🧪 Testing

### Manueller Test

```bash
# In Tinker
php artisan tinker

$service = app(\App\Services\AIResponseService::class);
$review = \App\Models\Review::first();

$response = $service->generateResponse($review, 'friendly');
echo $response;
```

### API Test via cURL

```bash
curl -X POST http://localhost/reviews/1/ai-response \
  -H "Content-Type: application/json" \
  -H "X-CSRF-TOKEN: YOUR_TOKEN" \
  -d '{
    "style": "friendly"
  }'
```

---

## 🐛 Troubleshooting

### Fehler: "OpenAI API Key nicht konfiguriert"

**Lösung:**
```bash
# 1. .env prüfen
cat .env | grep OPENAI_API_KEY

# 2. Config Cache leeren
php artisan config:clear

# 3. Server neu starten
php artisan serve
```

### Fehler: "Rate limit exceeded"

**Lösung:**
- Warte 1 Minute und versuche es erneut
- Upgrade deinen OpenAI Plan
- Implementiere Caching für häufige Anfragen

### Fehler: "Unauthorized"

**Lösung:**
- Prüfe ob API Key korrekt ist
- Prüfe ob API Key aktiv ist (OpenAI Dashboard)
- Erstelle neuen API Key falls nötig

---

## 📊 Monitoring

### Logs

Alle AI-Anfragen werden geloggt:

```bash
# Logs anschauen
tail -f storage/logs/laravel.log | grep "AI Response"
```

### Metriken die geloggt werden

- Review ID
- Verwendeter Stil
- Fehler (falls vorhanden)
- Timestamp

---

## 🚀 Erweiterungsmöglichkeiten

### Weitere Stile hinzufügen

In `AIResponseService.php`:

```php
const STYLES = [
    // ... existing styles
    'humorous' => [
        'name' => 'Humorvoll',
        'description' => 'Witzig und unterhaltsam',
        'tone' => 'humorous, funny, lighthearted',
    ],
];
```

### Andere AI-Modelle

```php
// In AIResponseService::generateResponse()
'model' => 'gpt-4', // Höhere Qualität, höhere Kosten
```

### Multi-Language Support

```php
// Context erweitern
$context = [
    'language' => 'de', // oder 'en', 'fr', etc.
];
```

---

## 📝 Weitere Ressourcen

- [OpenAI API Docs](https://platform.openai.com/docs)
- [OpenAI Pricing](https://openai.com/pricing)
- [Rate Limits](https://platform.openai.com/docs/guides/rate-limits)
- [Best Practices](https://platform.openai.com/docs/guides/production-best-practices)
