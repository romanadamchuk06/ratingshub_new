# Laravel Cloud Deployment Guide

## Übersicht

Diese Anleitung zeigt dir Schritt-für-Schritt, wie du RatingsHub auf Laravel Cloud deployest.

## Voraussetzungen

- ✅ GitHub Account
- ✅ Laravel Cloud Account (https://cloud.laravel.com)
- ✅ Git Repository (lokal)
- ✅ Stripe Account (für Zahlungen)
- ✅ Google OAuth Credentials (für Login & Reviews)

## Schritt 1: Projekt für Deployment vorbereiten

### 1.1 Git Repository erstellen (falls noch nicht vorhanden)

```bash
cd /Users/romanadamchuk/Developer/ratingshub

# Git initialisieren (falls noch nicht geschehen)
git init

# .gitignore prüfen
cat .gitignore

# Alle Dateien hinzufügen
git add .

# Commit erstellen
git commit -m "Initial commit: RatingsHub ready for deployment"
```

### 1.2 GitHub Repository erstellen

1. Gehe zu: https://github.com/new
2. Repository Name: `ratingshub`
3. Visibility: **Private** (wegen API-Keys)
4. **NICHT** "Initialize with README" anklicken
5. Klicke "Create repository"

### 1.3 Zu GitHub pushen

```bash
# GitHub Remote hinzufügen (ersetze USERNAME mit deinem GitHub Username)
git remote add origin https://github.com/USERNAME/ratingshub.git

# Pushen
git branch -M master
git push -u origin master
```

## Schritt 2: .env.example vorbereiten

Erstelle eine `.env.example` Datei mit allen benötigten Variablen (OHNE echte Werte):

```env
APP_NAME=RatingsHub
APP_ENV=production
APP_KEY=
APP_DEBUG=false
APP_URL=https://deine-domain.com

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=ratingshub
DB_USERNAME=root
DB_PASSWORD=

BROADCAST_CONNECTION=log
FILESYSTEM_DISK=local
QUEUE_CONNECTION=database

CACHE_STORE=database
SESSION_DRIVER=database

MAIL_MAILER=smtp
MAIL_HOST=smtp.mailtrap.io
MAIL_PORT=2525
MAIL_USERNAME=
MAIL_PASSWORD=
MAIL_FROM_ADDRESS="hello@example.com"
MAIL_FROM_NAME="${APP_NAME}"

GOOGLE_CLIENT_ID=
GOOGLE_CLIENT_SECRET=

STRIPE_KEY=
STRIPE_SECRET=
STRIPE_WEBHOOK_SECRET=
CASHIER_CURRENCY=eur

ADMIN_EMAIL=
```

Commit die Änderung:

```bash
git add .env.example
git commit -m "Add .env.example for deployment"
git push
```

## Schritt 3: Laravel Cloud Account einrichten

### 3.1 Account erstellen

1. Gehe zu: https://cloud.laravel.com
2. Klicke "Sign Up" oder "Get Started"
3. Registriere dich (mit GitHub empfohlen)
4. Verifiziere deine Email

### 3.2 Zahlungsmethode hinzufügen

1. Im Dashboard → "Billing"
2. Kreditkarte hinzufügen
3. **Hinweis:** Laravel Cloud kostet ca. $10-50/Monat je nach Plan

## Schritt 4: Projekt auf Laravel Cloud erstellen

### 4.1 Neues Projekt erstellen

1. Im Dashboard: Klicke "**Create Project**"
2. **Project Name:** `RatingsHub`
3. **Region:** Europe (Frankfurt oder Amsterdam empfohlen für DE)
4. **Repository:** Verbinde dein GitHub Repository
   - Klicke "Connect to GitHub"
   - Autorisiere Laravel Cloud
   - Wähle `USERNAME/ratingshub`
5. **Branch:** `master`
6. **Build Command:** (Standard lassen)
   ```bash
   composer install --no-dev --optimize-autoloader
   npm install
   npm run build
   ```
7. **Start Command:** (Standard lassen)
   ```bash
   php artisan serve --host=0.0.0.0 --port=8080
   ```
8. Klicke "**Create Project**"

### 4.2 Umgebungsvariablen konfigurieren

1. Im Projekt-Dashboard → "**Environment**"
2. Füge alle Variablen aus `.env.example` hinzu:

**Wichtige Variablen:**

```env
APP_NAME=RatingsHub
APP_ENV=production
APP_DEBUG=false
APP_URL=https://dein-projekt.laravel.cloud

# Database (wird von Laravel Cloud automatisch bereitgestellt)
DB_CONNECTION=mysql
DB_HOST=<wird von Laravel Cloud gesetzt>
DB_PORT=3306
DB_DATABASE=<wird von Laravel Cloud gesetzt>
DB_USERNAME=<wird von Laravel Cloud gesetzt>
DB_PASSWORD=<wird von Laravel Cloud gesetzt>

# Queue & Cache
QUEUE_CONNECTION=database
CACHE_STORE=database
SESSION_DRIVER=database

# Mail (für Produktion - z.B. mit Mailgun, SendGrid, oder Gmail)
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=deine@gmail.com
MAIL_PASSWORD=app-specific-password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS="noreply@ratingshub.com"
MAIL_FROM_NAME="RatingsHub"

# Google OAuth (ERSETZE MIT DEINEN ECHTEN WERTEN!)
GOOGLE_CLIENT_ID=deine-google-client-id.apps.googleusercontent.com
GOOGLE_CLIENT_SECRET=GOCSPX-dein-google-client-secret

# Stripe (Production Keys!)
STRIPE_KEY=pk_live_...  # NICHT pk_test_ !
STRIPE_SECRET=sk_live_... # NICHT sk_test_ !
STRIPE_WEBHOOK_SECRET=whsec_... # Wird später konfiguriert
CASHIER_CURRENCY=eur

# Admin Email
ADMIN_EMAIL=roman.adamchuk06@gmail.com
```

3. Klicke "**Save**"

### 4.3 APP_KEY generieren

Laravel Cloud generiert automatisch einen `APP_KEY` beim ersten Deployment.

Falls nicht:
```bash
php artisan key:generate --show
```
Dann in Environment Variables eintragen.

## Schritt 5: Datenbank konfigurieren

### 5.1 Datenbank erstellen

Laravel Cloud erstellt automatisch eine MySQL-Datenbank für dein Projekt.

### 5.2 Migrations ausführen

Nach dem ersten Deployment:

1. Im Dashboard → "**Deployments**"
2. Klicke auf die letzte Deployment
3. Öffne "**Terminal**" oder "**SSH**"
4. Führe aus:

```bash
php artisan migrate --force
```

**WICHTIG:** `--force` ist nötig, da `APP_ENV=production`

### 5.3 Seeders ausführen (Optional)

Falls du Test-Daten brauchst:

```bash
php artisan db:seed --class=PlanSeeder --force
php artisan db:seed --class=PromoCodeSeeder --force
```

## Schritt 6: Storage Link erstellen

Für Datei-Uploads:

```bash
php artisan storage:link
```

## Schritt 7: Google OAuth anpassen

### 7.1 Authorized Redirect URIs aktualisieren

1. Gehe zu: https://console.cloud.google.com/apis/credentials
2. Klicke auf deine OAuth Client ID
3. **Authorized redirect URIs** hinzufügen:
   ```
   https://dein-projekt.laravel.cloud/platforms/callback/google
   ```
4. Klicke "**Save**"

## Schritt 8: Stripe Webhooks konfigurieren

### 8.1 Webhook Endpoint erstellen

1. Gehe zu: https://dashboard.stripe.com/webhooks
2. Klicke "**Add endpoint**"
3. **Endpoint URL:**
   ```
   https://dein-projekt.laravel.cloud/stripe/webhook
   ```
4. **Events to send:**
   - `invoice.payment_succeeded`
   - `invoice.payment_failed`
   - `customer.subscription.updated`
   - `customer.subscription.deleted`
5. Klicke "**Add endpoint**"

### 8.2 Webhook Secret kopieren

1. Klicke auf den neu erstellten Webhook
2. **Signing secret** kopieren (beginnt mit `whsec_...`)
3. In Laravel Cloud Environment Variables:
   ```env
   STRIPE_WEBHOOK_SECRET=whsec_dein_secret
   ```

### 8.3 Production Keys verwenden

**WICHTIG:** Für Produktion musst du die **Live Keys** verwenden!

1. Stripe Dashboard → **Developers** → **API keys**
2. Schalte oben rechts von "**Test mode**" zu "**Live mode**"
3. Kopiere:
   - **Publishable key** → `STRIPE_KEY=pk_live_...`
   - **Secret key** → `STRIPE_SECRET=sk_live_...`
4. In Laravel Cloud Environment Variables eintragen

## Schritt 9: Custom Domain (Optional)

### 9.1 Domain hinzufügen

1. Im Projekt-Dashboard → "**Domains**"
2. Klicke "**Add Domain**"
3. Domain eingeben: `ratingshub.com`
4. Laravel Cloud zeigt dir DNS-Records

### 9.2 DNS konfigurieren

Bei deinem Domain-Provider (z.B. Namecheap, GoDaddy):

1. **A Record:**
   ```
   Type: A
   Host: @
   Value: <IP von Laravel Cloud>
   ```

2. **CNAME Record (www):**
   ```
   Type: CNAME
   Host: www
   Value: dein-projekt.laravel.cloud
   ```

3. Warte 5-30 Minuten (DNS-Propagation)

### 9.3 SSL/HTTPS

Laravel Cloud aktiviert automatisch kostenloses SSL (Let's Encrypt).

Sobald Domain aktiv ist, aktualisiere:

```env
APP_URL=https://ratingshub.com
```

## Schritt 10: Deployment-Script erstellen

Erstelle `deploy.sh` im Projekt-Root:

```bash
#!/bin/bash

# Composer Dependencies installieren
composer install --no-dev --optimize-autoloader

# NPM Dependencies installieren & Build
npm install
npm run build

# Cache leeren
php artisan config:clear
php artisan cache:clear
php artisan view:clear
php artisan route:clear

# Config & Routes cachen (Performance!)
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Migrations ausführen (mit --force für Production)
php artisan migrate --force

# Storage Link erstellen (falls noch nicht)
php artisan storage:link

echo "✅ Deployment erfolgreich!"
```

Mache es ausführbar:

```bash
chmod +x deploy.sh
```

Commit & Push:

```bash
git add deploy.sh
git commit -m "Add deployment script"
git push
```

## Schritt 11: Monitoring & Logs

### 11.1 Logs ansehen

1. Im Dashboard → "**Logs**"
2. Real-time Logs werden angezeigt

### 11.2 Performance Monitoring

Laravel Cloud bietet automatisch:
- ✅ Application Metrics
- ✅ Database Metrics
- ✅ Queue Metrics
- ✅ Error Tracking

### 11.3 Uptime Monitoring

Optional: UptimeRobot oder Pingdom einrichten:
- Monitor URL: `https://ratingshub.com`
- Intervall: 5 Minuten
- Email-Alerts bei Downtime

## Schritt 12: Scheduled Tasks (Cron)

Für automatische Review-Synchronisation:

### 12.1 Laravel Cloud Scheduler aktivieren

1. Im Dashboard → "**Scheduler**"
2. Laravel Cloud aktiviert automatisch `php artisan schedule:run` jede Minute

### 12.2 Schedule in `app/Console/Kernel.php` definieren

```php
protected function schedule(Schedule $schedule)
{
    // Jeden Tag um 2 Uhr: Reviews synchronisieren
    $schedule->call(function () {
        $platforms = ConnectedPlatform::where('is_active', true)
            ->where('provider', 'google')
            ->get();

        $service = app(GoogleMyBusinessService::class);

        foreach ($platforms as $platform) {
            try {
                $service->fetchReviews($platform);
            } catch (\Exception $e) {
                \Log::error('Scheduled review sync failed', [
                    'platform_id' => $platform->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    })->dailyAt('02:00');
}
```

## Troubleshooting

### Deployment schlägt fehl

**Fehler:** "Composer install failed"

**Lösung:**
- Prüfe `composer.json` auf Fehler
- Stelle sicher, dass alle Packages mit PHP 8.2+ kompatibel sind

**Fehler:** "npm run build failed"

**Lösung:**
- Prüfe `package.json`
- Teste lokal: `npm install && npm run build`

### 500 Internal Server Error

**Lösung:**
1. Logs prüfen: Dashboard → Logs
2. `APP_DEBUG=true` temporär aktivieren (NICHT dauerhaft!)
3. Migrations ausführen: `php artisan migrate --force`
4. Cache leeren: `php artisan cache:clear`

### Database Connection Error

**Lösung:**
1. Prüfe DB Credentials in Environment Variables
2. Laravel Cloud sollte DB automatisch konfigurieren
3. Falls nicht: Support kontaktieren

### Stripe Webhooks funktionieren nicht

**Lösung:**
1. Prüfe Webhook URL: `https://dein-projekt.laravel.cloud/stripe/webhook`
2. Prüfe `STRIPE_WEBHOOK_SECRET` in Environment Variables
3. Teste mit Stripe CLI: `stripe listen --forward-to https://...`

## Performance-Optimierungen

### 1. Config Caching

Immer in Production aktivieren:

```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

### 2. OPcache

Laravel Cloud aktiviert OPcache automatisch.

### 3. Database Indexe

Prüfe ob alle wichtigen Spalten indexiert sind:

```sql
-- In einer Migration:
$table->index('user_id');
$table->index('status');
$table->index('created_at');
```

### 4. Queue Workers

Für Email-Versand & lange Tasks:

```env
QUEUE_CONNECTION=database
```

Laravel Cloud startet automatisch Queue Workers.

## Kosten-Übersicht

### Laravel Cloud Preise (Stand 2024)

- **Starter:** ~$10/Monat
  - 1 GB RAM
  - 10 GB Storage
  - Geeignet für kleine Apps

- **Professional:** ~$50/Monat
  - 4 GB RAM
  - 50 GB Storage
  - Autoscaling
  - Empfohlen für Production

- **Enterprise:** Custom Pricing
  - Dedicated Resources
  - Premium Support

### Zusätzliche Kosten

- **Domain:** ~$10-15/Jahr (z.B. bei Namecheap)
- **Email-Service:** ~$10-35/Monat (Mailgun, SendGrid)
- **Monitoring:** $0-20/Monat (optional)

## Checkliste vor Go-Live

- [ ] Alle Environment Variables konfiguriert
- [ ] Stripe Production Keys aktiviert
- [ ] Stripe Webhooks eingerichtet
- [ ] Google OAuth Redirect URIs aktualisiert
- [ ] Database Migrations ausgeführt
- [ ] Storage Link erstellt
- [ ] SSL/HTTPS aktiv
- [ ] Custom Domain konfiguriert (optional)
- [ ] Email-Service konfiguriert & getestet
- [ ] Scheduled Tasks aktiviert
- [ ] Error-Monitoring aktiv
- [ ] Backup-Strategy definiert
- [ ] Logs regelmäßig prüfen

## Support

### Laravel Cloud Support

- Dokumentation: https://docs.laravel.com/cloud
- Support: support@laravel.com
- Community: Laravel Discord

### Eigene Projekt-Dokumentation

Siehe auch:
- `ARCHITECTURE.md` - System-Architektur
- `SUBSCRIPTION_SYSTEM.md` - Subscription & Payments
- `REVIEW_SYSTEM.md` - Review-Management
- `BUG_REPORT_SYSTEM.md` - Bug-Reports

---

**Viel Erfolg mit dem Deployment! 🚀**

Bei Fragen oder Problemen: Schau in die Laravel Cloud Docs oder frag mich!
