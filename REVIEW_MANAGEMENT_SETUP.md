# Review Management System - Setup Guide

## 🎯 Übersicht

Das Review Management System ermöglicht es Usern:
- ✅ Bewertungen von verschiedenen Plattformen automatisch abzurufen
- ✅ Auf Bewertungen direkt über die App zu antworten
- ✅ Review-Status zu verwalten (pending, responded, archived)
- ✅ Statistiken über Bewertungen zu sehen
- ✅ Mehrere Standorte/Locations zu verwalten

---

## 📋 Unterstützte Plattformen

### 1. Google My Business ✅ (Fertig implementiert)
- **API Version:** Google My Business API v4
- **OAuth Scopes:** `https://www.googleapis.com/auth/business.manage`
- **Features:**
  - Reviews abrufen (mit Pagination)
  - Auf Reviews antworten
  - Antworten löschen
  - Automatisches Access Token Refresh

### 2. Trustpilot 🚧 (Noch nicht implementiert)
- **API Version:** Trustpilot API v1
- **TODO:** Service-Klasse erstellen
- **TODO:** OAuth-Flow implementieren

---

## 🏗️ Architektur

### Datenbank-Struktur

```
users
  └── connected_platforms (OAuth-Verbindungen)
       ├── provider (google, trustpilot, etc.)
       ├── access_token (OAuth Access Token)
       ├── refresh_token (OAuth Refresh Token)
       ├── expires_at (Token-Ablaufzeit)
       └── metadata (JSON: location_name, email, etc.)
            └── reviews (Bewertungen von dieser Plattform)
                 ├── rating (1-5)
                 ├── text (Bewertungstext)
                 ├── reviewer_name
                 ├── status (pending, responded, archived)
                 └── review_responses (Antworten auf Bewertungen)
                      ├── text (Antworttext)
                      ├── sent_at (Zeitpunkt des Sendens)
                      └── provider_response_id (ID von Plattform)
```

### Flow: Review Synchronisation

```
1. User klickt "Synchronisieren" Button
   ↓
2. Frontend sendet POST /reviews/sync
   ↓
3. Backend (ReviewController)
   - Identifiziert Provider (google, trustpilot, etc.)
   - Ruft entsprechenden Service auf
   ↓
4. Service (GoogleMyBusinessService)
   - Prüft Access Token (refresh falls nötig)
   - Ruft Google My Business API auf
   - Pagination: Ruft alle Reviews ab (50 pro Request)
   ↓
5. Reviews werden in DB gespeichert
   - updateOrCreate verhindert Duplikate
   - Verwendet provider_review_id als Unique Constraint
   ↓
6. Rückgabe: Anzahl neuer Reviews
   ↓
7. Frontend zeigt Erfolgsmeldung
```

### Flow: Review Antwort

```
1. User schreibt Antwort und klickt "Senden"
   ↓
2. Frontend sendet POST /reviews/{id}/respond
   ↓
3. Backend (ReviewController)
   - Validiert Antwort (min 10, max 2000 Zeichen)
   - Speichert Antwort in review_responses Tabelle
   ↓
4. Service (GoogleMyBusinessService)
   - Prüft Access Token
   - Sendet Antwort an Google via PUT Request
   ↓
5. Bei Erfolg:
   - Markiert Antwort als gesendet (sent_at = now())
   - Aktualisiert Review-Status auf "responded"
   ↓
6. Bei Fehler:
   - Antwort bleibt in DB (sent_at = null)
   - User erhält Fehlermeldung
   - Log-Eintrag für Debugging
```

---

## 🔧 Google My Business Setup

### 1. Google Cloud Console konfigurieren

#### 1.1 Projekt erstellen
1. Gehe zu: https://console.cloud.google.com/
2. Klicke "Neues Projekt" → Name eingeben → "Erstellen"

#### 1.2 Google My Business API aktivieren
1. Navigation → "APIs & Services" → "Library"
2. Suche nach "Google My Business API"
3. Klicke "Enable"

#### 1.3 OAuth 2.0 Credentials erstellen
1. "APIs & Services" → "Credentials"
2. "Create Credentials" → "OAuth 2.0 Client ID"
3. Application Type: **Web application**
4. Name: "RatingsHub Production" (oder ähnlich)
5. Authorized redirect URIs:
   ```
   https://deine-domain.com/platforms/callback/google
   http://localhost/platforms/callback/google (für Development)
   ```
6. Klicke "Create"
7. **Kopiere Client ID und Client Secret!**

#### 1.4 OAuth Consent Screen konfigurieren
1. "APIs & Services" → "OAuth consent screen"
2. User Type: **External** (für öffentliche App)
3. App Name: "RatingsHub"
4. User Support Email: deine-email@domain.com
5. Developer Contact: deine-email@domain.com
6. Scopes → "Add or Remove Scopes"
   - Füge hinzu: `https://www.googleapis.com/auth/business.manage`
7. Test Users (falls App noch nicht verifiziert):
   - Füge Test-Gmail-Adressen hinzu
   - Diese können sich einloggen während App in Testing Mode ist

### 2. .env konfigurieren

```env
# Google OAuth (aus Schritt 1.3)
GOOGLE_CLIENT_ID=123456789-abc.apps.googleusercontent.com
GOOGLE_CLIENT_SECRET=GOCSPX-abc123def456
GOOGLE_REDIRECT_URI=https://deine-domain.com/platforms/callback/google

# App URL (für OAuth Redirects)
APP_URL=https://deine-domain.com
```

### 3. Socialite in config/services.php

```php
'google' => [
    'client_id' => env('GOOGLE_CLIENT_ID'),
    'client_secret' => env('GOOGLE_CLIENT_SECRET'),
    'redirect' => env('GOOGLE_REDIRECT_URI'),
],
```

---

## 🚀 Erste Schritte für User

### 1. Google My Business verbinden

```
1. User geht zu: /settings/platforms (oder Dashboard)
2. Klickt "Google My Business verbinden"
3. Wird zu Google OAuth umgeleitet
4. Gibt Google-Konto an (das Google My Business Zugriff hat)
5. Akzeptiert Berechtigungen
6. Wird zurück zur App umgeleitet
7. ConnectedPlatform wird in DB gespeichert
```

### 2. Location auswählen (WICHTIG!)

**Problem:** Nach OAuth-Callback wissen wir noch NICHT welche Location der User syncen will.

**Lösung:** User muss Location auswählen (z.B. via Dropdown)

**Implementierungs-Optionen:**

#### Option A: Automatische Location-Auswahl (wenn nur 1 Location)
```php
// In PlatformController::callback()
$service = app(GoogleMyBusinessService::class);
$accounts = $service->getAccounts($platform);

if (count($accounts) === 1) {
    $account = $accounts[0];
    $locations = $service->getLocations($platform, $account['name']);

    if (count($locations) === 1) {
        // Nur 1 Location → automatisch auswählen
        $platform->update([
            'metadata' => array_merge($platform->metadata, [
                'account_name' => $account['name'],
                'location_name' => $locations[0]['name'],
            ]),
        ]);
    }
}
```

#### Option B: Location-Auswahl-Dialog
```vue
<!-- SettingsPlatforms.vue -->
<template>
  <div v-if="platform.provider === 'google' && !platform.metadata.location_name">
    <h3>Wähle deine Location aus:</h3>
    <select v-model="selectedLocation" @change="saveLocation">
      <option v-for="location in locations" :value="location.name">
        {{ location.locationName }}
      </option>
    </select>
  </div>
</template>
```

**Für jetzt:** Location wird beim ersten Sync automatisch aus metadata geholt (falls vorhanden).

### 3. Reviews synchronisieren

```
1. User geht zu: /reviews
2. Klickt "Synchronisieren" Button
3. Backend ruft GoogleMyBusinessService::fetchReviews() auf
4. Reviews werden in DB gespeichert
5. Frontend zeigt neue Reviews an
```

### 4. Auf Review antworten

```
1. User klickt "Antworten" Button auf Review-Card
2. Schreibt Antworttext (min 10, max 2000 Zeichen)
3. Klickt "Antwort senden"
4. Backend speichert Antwort in DB
5. Backend sendet Antwort an Google via API
6. Review-Status wird auf "responded" gesetzt
7. User sieht Erfolgsmeldung
```

---

## 🧪 Testing

### Lokales Testen mit ngrok (für OAuth Callbacks)

```bash
# ngrok installieren (falls noch nicht geschehen)
brew install ngrok

# ngrok starten (Port 80 = dein Laravel App Port)
ngrok http 80

# Kopiere die HTTPS-URL (z.B. https://abc123.ngrok.io)
# Füge sie in Google Cloud Console als Redirect URI hinzu:
# https://abc123.ngrok.io/platforms/callback/google

# Aktualisiere .env:
GOOGLE_REDIRECT_URI=https://abc123.ngrok.io/platforms/callback/google
APP_URL=https://abc123.ngrok.io

# Cache clearen
php artisan config:clear

# Jetzt kannst du Google OAuth testen!
```

### Test-Accounts

Für Testing benötigst du:
1. Ein Google-Konto mit Google My Business Zugriff
2. Ein bestehendes Google My Business Profil (z.B. Restaurant, Laden)
3. Mindestens 1 Review auf dem Profil (zum Testen)

### Manuelles Testen

```bash
# 1. Google verbinden
# Browser: http://localhost/platforms/connect/google

# 2. Reviews syncen (via Tinker)
php artisan tinker
$platform = \App\Models\ConnectedPlatform::first();
$service = app(\App\Services\GoogleMyBusinessService::class);

# Accounts holen
$accounts = $service->getAccounts($platform);

# Locations holen
$locations = $service->getLocations($platform, $accounts[0]['name']);

# Location in metadata speichern
$platform->update([
    'metadata' => array_merge($platform->metadata, [
        'account_name' => $accounts[0]['name'],
        'location_name' => $locations[0]['name'],
    ])
]);

# Reviews abrufen
$count = $service->fetchReviews($platform);
echo "Neue Reviews: $count";

# 3. Auf Review antworten (via Tinker)
$review = \App\Models\Review::first();
$service->replyToReview($review, 'Vielen Dank für deine Bewertung!');
```

---

## 📊 API Rate Limits

### Google My Business API
- **Quota:** 100,000 requests/day (Standard)
- **Rate Limit:** 10 requests/second
- **Empfehlung:** Sync alle 30-60 Minuten via Scheduled Job

### Best Practices
- Verwende Pagination für große Datenmengen
- Cache Accounts & Locations (ändern sich selten)
- Log alle API-Fehler für Debugging
- Nutze Queue für API-Calls (nicht synchron im Request)

---

## 🔍 Debugging & Logs

### Wichtige Log-Einträge

```bash
# Google API Calls
tail -f storage/logs/laravel.log | grep "Google"

# Review Sync
tail -f storage/logs/laravel.log | grep "Review"

# OAuth Token Refresh
tail -f storage/logs/laravel.log | grep "Token Refresh"
```

### Häufige Fehler

**Fehler: "Token expired"**
- Ursache: Access Token ist abgelaufen
- Lösung: Automatisch durch `refreshAccessToken()` gefixt
- Falls es nicht funktioniert: User muss Platform neu verbinden

**Fehler: "Location name not found"**
- Ursache: `metadata.location_name` fehlt
- Lösung: User muss Location auswählen (siehe Option B oben)

**Fehler: "Insufficient permissions"**
- Ursache: OAuth Scope fehlt oder User hat keinen Zugriff auf Location
- Lösung: Prüfe Scopes in PlatformController::connect()

---

## 🚀 Production Checklist

Vor dem Go-Live:

- [ ] Google Cloud Console OAuth Consent Screen verifiziert
- [ ] Production Redirect URI in Google Console eingetragen
- [ ] .env mit Production Credentials konfiguriert
- [ ] Location-Auswahl implementiert (Option A oder B)
- [ ] Scheduled Job für automatischen Review-Sync eingerichtet
- [ ] Error Tracking aktiv (z.B. Sentry)
- [ ] API Rate Limits überwacht
- [ ] Test-User haben erfolgreich Reviews synchronisiert und beantwortet

---

## 📞 Support & Weiterführende Dokumentation

- [Google My Business API Docs](https://developers.google.com/my-business/reference/rest)
- [Google OAuth 2.0 Guide](https://developers.google.com/identity/protocols/oauth2)
- [Laravel Socialite Docs](https://laravel.com/docs/socialite)

---

**Erstellt am:** 2025-12-03
**Version:** 1.0
**Autor:** Claude Code Assistant 🤖
