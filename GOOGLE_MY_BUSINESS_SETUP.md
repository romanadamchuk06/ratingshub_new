# Google My Business Integration - Komplette Setup Anleitung

## ✅ Was bereits implementiert ist

Die Google My Business Integration ist **vollständig fertig** und umfasst:

- ✅ OAuth 2.0 Authentifizierung
- ✅ Automatisches Access Token Refresh
- ✅ Reviews von Google abrufen (mit Pagination)
- ✅ Auf Reviews antworten (direkt zu Google senden)
- ✅ Review-Antworten löschen
- ✅ Location-Auswahl per Dropdown
- ✅ Frontend für Review-Management
- ✅ Vollständige Fehlerbehandlung

---

## 🔧 Setup Schritt-für-Schritt

### Schritt 1: Google Cloud Console konfigurieren

#### 1.1 Projekt erstellen
1. Gehe zu: https://console.cloud.google.com/
2. Klicke oben links auf **"Projekt auswählen"**
3. Klicke **"NEUES PROJEKT"**
4. Name: z.B. "RatingsHub"
5. Klicke **"ERSTELLEN"**

#### 1.2 APIs aktivieren

Aktiviere diese APIs (alle sind wichtig!):

1. Gehe zu: https://console.cloud.google.com/apis/library
2. Suche und aktiviere:
   - **"Google My Business API"** → ENABLE
   - **"My Business Account Management API"** → ENABLE
   - **"My Business Business Information API"** → ENABLE
   - **"My Business Verifications API"** → ENABLE

**Screenshot:** Jede API sollte "API enabled" Status haben.

#### 1.3 OAuth 2.0 Credentials erstellen

1. Gehe zu: https://console.cloud.google.com/apis/credentials
2. Klicke **"+ CREATE CREDENTIALS"**
3. Wähle **"OAuth client ID"**
4. Falls gefordert: Konfiguriere zuerst den "OAuth consent screen" (siehe Schritt 1.4)
5. Application type: **Web application**
6. Name: "RatingsHub Production"
7. **Authorized redirect URIs** hinzufügen:
   ```
   http://localhost/platforms/callback/google
   https://deine-domain.com/platforms/callback/google
   ```
8. Klicke **"CREATE"**
9. Popup öffnet sich mit:
   - **Client ID** → Kopieren
   - **Client secret** → Kopieren

#### 1.4 OAuth Consent Screen konfigurieren

1. Gehe zu: https://console.cloud.google.com/apis/credentials/consent
2. User Type: **External**
3. Klicke **"CREATE"**
4. Fülle aus:
   - **App name:** RatingsHub
   - **User support email:** deine-email@domain.com
   - **Developer contact:** deine-email@domain.com
5. Klicke **"SAVE AND CONTINUE"**
6. **Scopes** → Klicke "ADD OR REMOVE SCOPES"
   - Suche: `business.manage`
   - Wähle: `https://www.googleapis.com/auth/business.manage`
   - Klicke **"UPDATE"**
7. Klicke **"SAVE AND CONTINUE"**
8. **Test users** (wichtig während Development!):
   - Klicke **"+ ADD USERS"**
   - Füge deine Gmail-Adresse hinzu (die du für Tests verwendest)
   - Klicke **"ADD"**
9. Klicke **"SAVE AND CONTINUE"**
10. Klicke **"BACK TO DASHBOARD"**

**Status:** App bleibt in "Testing" Mode bis du sie zur Verifizierung einreichst (nicht nötig für interne Nutzung).

---

### Schritt 2: .env Konfiguration

Füge die Credentials in deine `.env` ein:

```env
# Google OAuth Credentials (aus Schritt 1.3)
GOOGLE_CLIENT_ID=123456789-xxxxxxxxxxxxxxxxxxxxxxxx.apps.googleusercontent.com
GOOGLE_CLIENT_SECRET=GOCSPX-xxxxxxxxxxxxxxxxxxxx

# App URL (wichtig für OAuth Redirect)
APP_URL=http://localhost  # Development
# APP_URL=https://deine-domain.com  # Production
```

**Cache clearen:**
```bash
docker-compose exec app php artisan config:clear
```

---

### Schritt 3: Google My Business Profil vorbereiten

#### 3.1 Google My Business Profil erstellen

Falls du noch **kein Google My Business Profil** hast:

1. Gehe zu: https://business.google.com
2. Klicke **"Manage now"**
3. Folge dem Setup-Wizard:
   - Firmenname eingeben
   - Kategorie wählen (z.B. Restaurant, Laden, etc.)
   - Standort angeben
   - Kontaktdaten eingeben
4. **Verifizierung:** Google sendet Postkarte oder SMS
5. Nach Verifizierung: Profil ist aktiv

#### 3.2 Prüfe ob Bewertungen vorhanden sind

1. Gehe zu deinem Google My Business Profil
2. Menü → **"Bewertungen"**
3. Du solltest mindestens **1 Bewertung** sehen

**Falls keine Bewertungen:** Du kannst das System trotzdem testen, aber es werden 0 Reviews synchronisiert.

#### 3.3 Prüfe deine Rolle

Du musst **Owner** oder **Manager** des Google My Business Profils sein!

Prüfen:
1. https://business.google.com
2. Wähle dein Profil
3. Menü → **"Nutzer"**
4. Deine E-Mail sollte **"Inhaber"** oder **"Manager"** sein

---

## 🚀 Erste Nutzung (User-Perspektive)

### Schritt 1: Google My Business verbinden

1. Öffne deine App: `http://localhost` (eingeloggt als User)
2. Gehe zu: **/reviews**
3. Falls noch keine Plattform verbunden:
   - Klicke **"Plattform verbinden"**
4. Du wirst zu Google OAuth umgeleitet
5. Wähle dein Google-Konto (das Google My Business Zugriff hat)
6. Akzeptiere Berechtigungen:
   - "Manage your Business Profile locations on Google"
7. Du wirst zurück zur App umgeleitet
8. **ConnectedPlatform** ist jetzt in der Datenbank gespeichert

### Schritt 2: Standort auswählen

Nach dem OAuth-Callback siehst du:

```
⚠️ Standort auswählen erforderlich

Bevor du Bewertungen synchronisieren kannst, musst du
deinen Google My Business Standort auswählen.

[Dropdown mit deinen Locations]
[Standort speichern]
```

1. Klicke auf das **Dropdown**
2. Du siehst alle deine Google My Business Locations
3. Wähle den Standort aus
4. Klicke **"Standort speichern"**
5. Page lädt neu → Location ist gesetzt

### Schritt 3: Reviews synchronisieren

1. Klicke auf **"Synchronisieren"** Button (oben rechts)
2. Backend ruft Google API auf
3. Reviews werden in Datenbank gespeichert
4. Du siehst Erfolgsmeldung: "✅ 5 neue Bewertungen synchronisiert!"
5. Reviews werden in der Liste angezeigt

### Schritt 4: Auf Review antworten

1. Wähle einen Review aus der Liste
2. Klicke **"Antworten"**
3. Schreibe deine Antwort (min 10, max 2000 Zeichen)
4. Klicke **"Antwort senden"**
5. Antwort wird:
   - In Datenbank gespeichert
   - An Google gesendet (via Google My Business API)
   - Review-Status wird auf "Beantwortet" gesetzt
6. Du siehst: "✅ Deine Antwort wurde gesendet!"

---

## 🧪 Testing & Debugging

### Test 1: OAuth funktioniert?

```bash
# Prüfe ob Plattform verbunden wurde
docker-compose exec app php artisan tinker --execute="
echo 'Verbundene Plattformen: ' . \App\Models\ConnectedPlatform::count() . PHP_EOL;
\App\Models\ConnectedPlatform::all()->each(function(\$p) {
    echo 'Provider: ' . \$p->provider . PHP_EOL;
    echo 'Email: ' . (\$p->metadata['email'] ?? 'N/A') . PHP_EOL;
    echo 'Access Token: ' . substr(\$p->access_token, 0, 20) . '...' . PHP_EOL;
    echo PHP_EOL;
});
"
```

**Erwartete Ausgabe:**
```
Verbundene Plattformen: 1
Provider: google
Email: deine-email@gmail.com
Access Token: ya29.a0AfB_byC...
```

### Test 2: Locations abrufen

```bash
docker-compose exec app php artisan tinker --execute="
\$platform = \App\Models\ConnectedPlatform::where('provider', 'google')->first();
\$service = app(\App\Services\GoogleMyBusinessService::class);

echo 'Hole Accounts...' . PHP_EOL;
\$accounts = \$service->getAccounts(\$platform);
echo 'Accounts gefunden: ' . count(\$accounts) . PHP_EOL . PHP_EOL;

foreach(\$accounts as \$account) {
    echo 'Account: ' . (\$account['accountName'] ?? \$account['name']) . PHP_EOL;

    \$locations = \$service->getLocations(\$platform, \$account['name']);
    echo 'Locations: ' . count(\$locations) . PHP_EOL;

    foreach(\$locations as \$loc) {
        echo '  → ' . (\$loc['locationName'] ?? \$loc['name']) . PHP_EOL;
    }
    echo PHP_EOL;
}
"
```

**Erwartete Ausgabe:**
```
Hole Accounts...
Accounts gefunden: 1

Account: Mein Restaurant
Locations: 1
  → Mein Restaurant München
```

### Test 3: Reviews synchronisieren

**Voraussetzung:** Location muss in metadata gesetzt sein!

```bash
docker-compose exec app php artisan tinker --execute="
\$platform = \App\Models\ConnectedPlatform::where('provider', 'google')->first();

# Location setzen (falls noch nicht)
\$platform->update([
    'metadata' => array_merge(\$platform->metadata ?? [], [
        'location_name' => 'accounts/DEINE_ACCOUNT_ID/locations/DEINE_LOCATION_ID'
    ])
]);

\$service = app(\App\Services\GoogleMyBusinessService::class);
\$count = \$service->fetchReviews(\$platform);

echo 'Neue Reviews: ' . \$count . PHP_EOL;
echo 'Gesamt Reviews: ' . \App\Models\Review::count() . PHP_EOL;
"
```

### Test 4: Review-Antwort senden

```bash
docker-compose exec app php artisan tinker --execute="
\$review = \App\Models\Review::first();

if (!\$review) {
    echo 'Keine Reviews gefunden. Erst synchronisieren!' . PHP_EOL;
    exit;
}

\$service = app(\App\Services\GoogleMyBusinessService::class);
\$service->replyToReview(\$review, 'Vielen Dank für deine Bewertung! 🙏');

echo 'Antwort erfolgreich gesendet!' . PHP_EOL;
"
```

---

## ❌ Häufige Fehler & Lösungen

### Fehler 1: "Token expired"

**Symptom:** Reviews können nicht synchronisiert werden

**Ursache:** Access Token ist abgelaufen

**Lösung:** Der Service refreshed automatisch! Falls es nicht funktioniert:
```bash
# Plattform neu verbinden
# Browser: /platforms/connect/google
```

### Fehler 2: "Location name not found"

**Symptom:** Fehler beim Synchronisieren: "Location Name nicht gefunden"

**Ursache:** `metadata.location_name` ist nicht gesetzt

**Lösung:**
1. Gehe zu /reviews
2. Wähle Location aus Dropdown
3. Klicke "Standort speichern"

### Fehler 3: "Insufficient permissions"

**Symptom:** OAuth funktioniert, aber API-Calls schlagen fehl

**Ursache:** Falscher OAuth Scope oder keine Berechtigung für Location

**Lösung:**
1. Prüfe ob Scope `business.manage` in Google Cloud Console gesetzt ist
2. Prüfe ob du Owner/Manager des Google My Business Profils bist
3. Plattform trennen und neu verbinden

### Fehler 4: "API not enabled"

**Symptom:** API Error 403 "Google My Business API has not been used"

**Lösung:**
1. Gehe zu: https://console.cloud.google.com/apis/library
2. Aktiviere **alle** My Business APIs (siehe Schritt 1.2)

### Fehler 5: "No reviews found" (aber Profil hat Reviews)

**Mögliche Ursachen:**
1. **Falsche Location:** Du hast mehrere Locations, aber die falsche ausgewählt
2. **Review-Filter:** Google zeigt nur öffentliche Reviews über API
3. **Berechtigungen:** Du bist nicht Owner/Manager

**Debug:**
```bash
# Prüfe welche Location gesetzt ist
docker-compose exec app php artisan tinker --execute="
\$platform = \App\Models\ConnectedPlatform::first();
echo 'Location: ' . (\$platform->metadata['location_name'] ?? 'NICHT GESETZT') . PHP_EOL;
"
```

---

## 📊 API Limits & Best Practices

### Google My Business API Limits

- **Quota:** 100,000 requests/day
- **Rate Limit:** 10 requests/second
- **Reviews per Request:** 50 (mit Pagination)

### Empfehlungen

1. **Scheduled Job für Auto-Sync:**
   ```php
   // routes/console.php
   Schedule::command('reviews:sync-all')
       ->everyThirtyMinutes()
       ->timezone('Europe/Berlin');
   ```

2. **Queue für API-Calls:**
   ```php
   // ReviewController::sync()
   dispatch(new SyncReviewsJob($connectedPlatform));
   ```

3. **Cache für Accounts & Locations:**
   ```php
   Cache::remember("google_locations_{$platform->id}", 3600, function() {
       return $service->getLocations($platform);
   });
   ```

---

## 🎯 Checkliste für Go-Live

- [ ] Alle 4 Google APIs aktiviert
- [ ] OAuth Credentials korrekt in .env
- [ ] OAuth Consent Screen konfiguriert
- [ ] Production Redirect URI in Google Console
- [ ] Test-User erfolgreich verbunden
- [ ] Test-User hat Location ausgewählt
- [ ] Reviews erfolgreich synchronisiert
- [ ] Antwort erfolgreich an Google gesendet
- [ ] Error Logging aktiv (Sentry, etc.)

---

## 📞 Support & Links

- **Google My Business API Docs:** https://developers.google.com/my-business/content/review-data
- **Google OAuth 2.0 Guide:** https://developers.google.com/identity/protocols/oauth2
- **Google Cloud Console:** https://console.cloud.google.com

---

**Erstellt am:** 2025-12-03
**Version:** 1.0
**Status:** Production Ready ✅
