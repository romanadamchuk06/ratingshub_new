# Google Business Profile APIs aktivieren

## 🎯 Diese APIs brauchst du

Für die **Google Business Profile API** (Reviews) musst du diese APIs aktivieren:

### 1. My Business API v4 ✅
- **Name:** Google My Business API
- **Nutzen:** Reviews abrufen, antworten, löschen
- **Status:** Wir nutzen diese bereits im Code

### 2. My Business Account Management API ✅
- **Name:** My Business Account Management API
- **Nutzen:** Accounts abrufen

### 3. My Business Business Information API ✅
- **Name:** My Business Business Information API
- **Nutzen:** Locations abrufen

### 4. My Business Verifications API
- **Name:** My Business Verifications API
- **Nutzen:** (Optional) Location-Verifizierung

---

## 📋 Schritt-für-Schritt Aktivierung

### Schritt 1: Google Cloud Console öffnen

1. Gehe zu: https://console.cloud.google.com/apis/library
2. Stelle sicher dass dein **Projekt ausgewählt** ist (oben links)

### Schritt 2: Jede API einzeln aktivieren

#### API 1: Google My Business API
```
1. Suche in der Library: "Google My Business API"
2. Klicke auf das Ergebnis
3. Klicke "ENABLE" (falls noch nicht aktiviert)
4. Warte bis Status = "API enabled" ✅
```

#### API 2: My Business Account Management API
```
1. Suche: "My Business Account Management API"
2. Klicke drauf
3. Klicke "ENABLE"
4. Warte auf Bestätigung
```

#### API 3: My Business Business Information API
```
1. Suche: "My Business Business Information API"
2. Klicke drauf
3. Klicke "ENABLE"
4. Warte auf Bestätigung
```

#### API 4: My Business Verifications API (Optional)
```
1. Suche: "My Business Verifications API"
2. Klicke drauf
3. Klicke "ENABLE"
```

### Schritt 3: Überprüfen

Gehe zu: https://console.cloud.google.com/apis/dashboard

Du solltest sehen:
```
✅ Google My Business API - Enabled
✅ My Business Account Management API - Enabled
✅ My Business Business Information API - Enabled
✅ My Business Verifications API - Enabled
```

---

## ⚡ Quick Links für dein Projekt

Ersetze `DEIN_PROJEKT_ID` mit deiner tatsächlichen Projekt-ID:

```
# API Library
https://console.cloud.google.com/apis/library?project=DEIN_PROJEKT_ID

# Dashboard (Status checken)
https://console.cloud.google.com/apis/dashboard?project=DEIN_PROJEKT_ID

# Credentials
https://console.cloud.google.com/apis/credentials?project=DEIN_PROJEKT_ID
```

---

## 🧪 Nach der Aktivierung testen

```bash
# 1. App öffnen
http://localhost/reviews

# 2. Location auswählen (Dropdown)

# 3. "Synchronisieren" klicken

# 4. Falls Fehler:
# Logs checken
docker-compose exec app tail -f storage/logs/laravel.log | grep Google
```

---

## ❌ Häufige Fehler

### Fehler: "API has not been used in project"

**Lösung:** API noch nicht aktiviert
→ Gehe zu API Library und aktiviere sie

### Fehler: "This API is not enabled for this application"

**Lösung:** Falsches Projekt ausgewählt
→ Prüfe ob das richtige Projekt oben links ausgewählt ist

### Fehler: "Access not granted or expired"

**Lösung:** OAuth Consent Screen nicht konfiguriert
→ Gehe zu: https://console.cloud.google.com/apis/credentials/consent

---

## 📊 API Endpunkte die wir nutzen

```php
// Base URL
https://mybusiness.googleapis.com/v4

// Accounts abrufen
GET /accounts

// Locations abrufen
GET /accounts/{accountId}/locations

// Reviews abrufen
GET /accounts/{accountId}/locations/{locationId}/reviews

// Auf Review antworten
PUT /accounts/{accountId}/locations/{locationId}/reviews/{reviewId}/reply

// Antwort löschen
DELETE /accounts/{accountId}/locations/{locationId}/reviews/{reviewId}/reply
```

Alle diese Endpunkte funktionieren **NUR** wenn die APIs aktiviert sind!

---

## 📞 Quellen

- [Google My Business API Reference](https://developers.google.com/my-business/reference/rest)
- [Work with review data](https://developers.google.com/my-business/content/review-data)
- [Business Profile APIs](https://developers.google.com/my-business)
