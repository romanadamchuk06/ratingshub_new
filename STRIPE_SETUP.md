# Stripe Subscription System - Setup Guide

## 🎯 Übersicht

Dieses System handhabt automatisch:
- ✅ Zahlungsausfälle mit **3-Tage Grace Period**
- ✅ Automatische Benachrichtigungen per E-Mail
- ✅ Abo-Kündigungen und Reaktivierungen
- ✅ Trial-Perioden
- ✅ Webhook-Verarbeitung

---

## 📋 Voraussetzungen

1. **Stripe Account** mit Test- und Live-Keys
2. **Laravel Cashier** (bereits installiert: v16.0.3)
3. **Queue Worker** für asynchrone Benachrichtigungen
4. **Cronjob** für Scheduled Tasks

---

## 🔧 1. Stripe Dashboard konfigurieren

### 1.1 Webhook Secret erstellen

1. Gehe zu: https://dashboard.stripe.com/webhooks
2. Klicke auf **"Add endpoint"**
3. URL: `https://deine-domain.com/stripe/webhook`
4. Events auswählen:
   - ✅ `customer.subscription.updated`
   - ✅ `customer.subscription.deleted`
   - ✅ `invoice.payment_succeeded`
   - ✅ `invoice.payment_failed`
5. Webhook Secret kopieren (beginnt mit `whsec_...`)

### 1.2 Subscription-Pläne erstellen

1. Gehe zu: https://dashboard.stripe.com/products
2. Erstelle für jeden Plan in deiner DB ein Stripe Product
3. Füge einen Price hinzu (z.B. 29.99 EUR/Monat)
4. Kopiere die **Price ID** (beginnt mit `price_...`)
5. Trage die Price ID in deine DB ein (`plans.stripe_plan_id`)

---

## ⚙️ 2. Umgebungsvariablen (.env)

```env
# Stripe Keys (aus Dashboard → Developers → API keys)
STRIPE_KEY=pk_test_...
STRIPE_SECRET=sk_test_...

# Webhook Secret (aus Schritt 1.1)
STRIPE_WEBHOOK_SECRET=whsec_...

# Währung
CASHIER_CURRENCY=eur
CASHIER_CURRENCY_LOCALE=de

# Admin E-Mail für Fehlerbenachrichtigungen
ADMIN_EMAIL=admin@deine-domain.com

# Queue Driver (für asynchrone Jobs)
QUEUE_CONNECTION=database  # oder redis
```

**WICHTIG:** Für Production die `pk_live_...` und `sk_live_...` Keys verwenden!

---

## 🏃 3. Setup-Befehle ausführen

```bash
# Migration ausführen (Grace Period Feld)
php artisan migrate

# Queue Worker starten (in Production via Supervisor)
php artisan queue:work --tries=3

# Scheduler testen (sollte täglich laufen)
php artisan schedule:run

# Grace Period Cleanup manuell testen
php artisan subscription:cleanup-grace-periods --dry-run
```

---

## 📅 4. Cronjob einrichten (Production)

Füge folgende Zeile in deinen Crontab ein:

```bash
* * * * * cd /pfad/zu/deiner/app && php artisan schedule:run >> /dev/null 2>&1
```

Dieser Cron läuft jede Minute und führt automatisch:
- **Täglich 02:00 Uhr:** Grace Period Cleanup

---

## 🔄 5. Flow: Zahlungsausfall

### Was passiert automatisch?

#### **Tag 0: Zahlung schlägt fehl**
```
1. Stripe sendet Event → invoice.payment_failed
2. Webhook Handler startet Grace Period (3 Tage)
3. User wird per E-Mail benachrichtigt:
   "⚠️ Zahlung fehlgeschlagen - Du hast noch 3 Tage Zugriff"
4. User kann weiterhin alle Features nutzen
```

#### **Tag 1-3: Grace Period**
```
- User hat vollen Zugriff
- Middleware prüft: ends_grace_period_at > now() ✅
- User sollte Zahlungsmethode aktualisieren
```

#### **Tag 3: Grace Period abgelaufen**
```
1. Scheduled Command läuft: subscription:cleanup-grace-periods
2. Prüft: Hat User mittlerweile bezahlt?
   - JA → Grace Period entfernen, alles gut
   - NEIN → Abo deaktivieren, Benachrichtigung senden
3. User verliert Zugriff auf Features
4. Middleware leitet zu /subscription weiter
```

---

## 🧪 6. Testing

### Lokales Testen mit Stripe CLI

```bash
# Stripe CLI installieren
brew install stripe/stripe-cli/stripe

# Login
stripe login

# Webhook Forwarding (für lokale Entwicklung)
stripe listen --forward-to http://localhost/stripe/webhook

# Test-Event senden
stripe trigger payment_intent.succeeded
stripe trigger invoice.payment_failed
stripe trigger customer.subscription.deleted
```

### Test-Kreditkarten

- ✅ **Erfolg:** `4242 4242 4242 4242`
- ❌ **Fehler:** `4000 0000 0000 0002`
- ⚠️ **Requires Auth:** `4000 0027 6000 3184`

---

## 📧 7. E-Mail Notifications

Zwei Notifications werden automatisch versendet:

### 7.1 PaymentFailed
- **Wann:** Sofort nach fehlgeschlagener Zahlung
- **Inhalt:** "Zahlung fehlgeschlagen, 3 Tage Grace Period"
- **Action:** "Zahlungsmethode aktualisieren"

### 7.2 SubscriptionCancelled
- **Wann:** Nach Ablauf der Grace Period
- **Inhalt:** "Dein Abo wurde gekündigt"
- **Action:** "Abo reaktivieren"

---

## 🛠️ 8. Monitoring & Logs

### Wichtige Log-Einträge prüfen

```bash
# Laravel Logs
tail -f storage/logs/laravel.log

# Webhook Events (mit Stripe-Daten)
grep "Stripe Webhook" storage/logs/laravel.log

# Grace Period Aktionen
grep "Grace Period" storage/logs/laravel.log
```

### Stripe Dashboard

- **Webhooks:** https://dashboard.stripe.com/webhooks → Delivery logs
- **Events:** https://dashboard.stripe.com/events → Alle Events anschauen
- **Customers:** https://dashboard.stripe.com/customers → User-Abos prüfen

---

## 🔐 9. Sicherheit

### Webhook Signatur-Verifizierung

Laravel Cashier verifiziert automatisch alle Webhooks mit dem `STRIPE_WEBHOOK_SECRET`.

**Niemals** den Webhook Secret im Code hardcoden!

### IP Whitelisting (Optional)

Falls zusätzliche Sicherheit gewünscht:
```php
// In StripeWebhookController:
protected function isValidSource($request) {
    $allowedIPs = [
        '3.18.12.63',    // Stripe IP
        '3.130.192.231', // Stripe IP
        // ... weitere Stripe IPs
    ];
    return in_array($request->ip(), $allowedIPs);
}
```

---

## 🚀 10. Production Checklist

Vor dem Go-Live:

- [ ] Live-Keys in `.env` eintragen (`pk_live_...`, `sk_live_...`)
- [ ] Webhook in Stripe Dashboard mit Live-Mode URL erstellen
- [ ] Cron eingerichtet und läuft
- [ ] Queue Worker läuft (via Supervisor)
- [ ] E-Mail Versand funktioniert (Test-Mail senden)
- [ ] Grace Period mit Testuser durchgespielt
- [ ] Stripe Dashboard Events überwachen
- [ ] Error Tracking aktiv (z.B. Sentry)

---

## 📞 Support & Debugging

### Häufige Probleme

**Problem:** Webhooks kommen nicht an
- ✅ URL korrekt in Stripe Dashboard?
- ✅ HTTPS aktiv? (Stripe sendet nur zu HTTPS)
- ✅ Route registriert? (`php artisan route:list | grep webhook`)

**Problem:** Grace Period wird nicht gesetzt
- ✅ Migration gelaufen? (`php artisan migrate:status`)
- ✅ Webhook Secret korrekt in `.env`?
- ✅ Logs prüfen: `grep "Grace Period" storage/logs/laravel.log`

**Problem:** Benachrichtigungen kommen nicht an
- ✅ Queue Worker läuft?
- ✅ E-Mail-Konfiguration korrekt?
- ✅ Jobs in `failed_jobs` Tabelle? (`php artisan queue:failed`)

---

## 🎓 Weiterführende Dokumentation

- [Laravel Cashier Docs](https://laravel.com/docs/billing)
- [Stripe API Reference](https://stripe.com/docs/api)
- [Stripe Webhooks Guide](https://stripe.com/docs/webhooks)

---

**Erstellt am:** 2025-12-03
**Version:** 1.0
**Autor:** Claude Code Assistant 🤖
