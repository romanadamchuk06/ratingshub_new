# 🚀 RatingsHub - Deployment Guide

Dieser Guide beschreibt alle Schritte für ein Production Deployment von RatingsHub.

## 📋 Inhaltsverzeichnis

- [Schnellstart](#schnellstart)
- [Voraussetzungen](#voraussetzungen)
- [Deployment-Schritte](#deployment-schritte)
- [Manuelle Deployment-Befehle](#manuelle-deployment-befehle)
- [Rollback](#rollback)
- [Troubleshooting](#troubleshooting)
- [Checklisten](#checklisten)

---

## ⚡ Schnellstart

**Automatisches Deployment (Empfohlen):**

```bash
./deploy.sh
```

Das Script führt automatisch alle notwendigen Schritte aus.

---

## 🔧 Voraussetzungen

### Server-Anforderungen

- **PHP:** 8.2 oder höher
- **Composer:** 2.x
- **Node.js:** 18.x oder höher
- **NPM:** 9.x oder höher
- **MySQL/MariaDB:** 8.0+ / 10.3+
- **Git:** Installiert und konfiguriert

---

## 📝 Deployment-Schritte

### Automatisches Deployment

```bash
ssh user@your-server.com
cd /var/www/ratingshub
./deploy.sh
```

### Manuelle Schritte

```bash
# 1. Maintenance Mode
php artisan down

# 2. Git Pull
git pull origin master

# 3. Dependencies
composer install --no-dev --optimize-autoloader
npm ci && npm run build

# 4. Database
php artisan migrate --force

# 5. Cache
php artisan config:cache
php artisan route:cache
php artisan view:cache

# 6. Queue
php artisan queue:restart

# 7. Maintenance Mode Off
php artisan up
```

---

## ⏮️ Rollback

```bash
git reset --hard PREVIOUS_COMMIT
./deploy.sh
```

---

## ✅ Post-Deployment Checklist

- [ ] Website erreichbar
- [ ] Login funktioniert
- [ ] Dashboard lädt
- [ ] Pricing Toggle funktioniert (monatlich/jährlich)
- [ ] Google OAuth funktioniert
- [ ] Stripe Checkout funktioniert
- [ ] Keine Fehler in Logs

---

**Letztes Update:** 2025-12-16
