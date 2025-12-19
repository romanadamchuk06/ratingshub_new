# ⚡ Quick Commands Reference

Schnellreferenz für häufig verwendete Production Commands.

## 🚀 Deployment

```bash
# Automatisches Full Deployment
./deploy.sh

# Nur Code Pull + Build
git pull && npm run build

# Nur Backend Update
git pull && composer install --no-dev --optimize-autoloader

# Nur Frontend Build
npm run build
```

## 🗄️ Database

```bash
# Migrations ausführen
php artisan migrate --force

# Migrations Status
php artisan migrate:status

# Rollback letzte Migration
php artisan migrate:rollback --step=1

# Seeder ausführen
php artisan db:seed --class=PlanSeeder
```

## 💾 Cache

```bash
# Alle Caches löschen
php artisan optimize:clear

# Nur Config Cache
php artisan config:clear && php artisan config:cache

# Nur Route Cache
php artisan route:clear && php artisan route:cache

# Nur View Cache
php artisan view:clear && php artisan view:cache
```

## 🛠️ Maintenance

```bash
# Maintenance Mode EIN
php artisan down --render="errors::503"

# Maintenance Mode AUS
php artisan up

# Mit Secret (erlaubt dir Zugriff während Wartung)
php artisan down --secret="geheimer-token"
# Dann: https://deine-domain.com/geheimer-token
```

## 📊 Queue

```bash
# Queue Workers neu starten
php artisan queue:restart

# Failed Jobs anzeigen
php artisan queue:failed

# Failed Job erneut versuchen
php artisan queue:retry <job-id>

# Alle Failed Jobs erneut versuchen
php artisan queue:retry all
```

## 📝 Logs

```bash
# Laravel Logs (Echtzeit)
tail -f storage/logs/laravel.log

# Letzte 100 Zeilen
tail -100 storage/logs/laravel.log

# Nur Errors
tail -500 storage/logs/laravel.log | grep ERROR

# Logs löschen (VORSICHT!)
> storage/logs/laravel.log
```

## 🔍 Debugging

```bash
# Tinker (PHP REPL)
php artisan tinker

# Route Liste
php artisan route:list

# Config Werte prüfen
php artisan tinker
>>> config('app.env')
>>> config('database.connections.mysql')

# User erstellen (Tinker)
php artisan tinker
>>> $user = App\Models\User::create([...])
```

## 📦 Backup

```bash
# Datenbank Backup
mysqldump -u username -p database_name > backup_$(date +%Y%m%d_%H%M%S).sql

# Datenbank Backup (komprimiert)
mysqldump -u username -p database_name | gzip > backup_$(date +%Y%m%d).sql.gz

# Datenbank Restore
mysql -u username -p database_name < backup.sql
```

## 🔐 Permissions

```bash
# Storage & Cache Permissions
sudo chown -R www-data:www-data storage bootstrap/cache
sudo chmod -R 775 storage bootstrap/cache

# Alle Permissions setzen
sudo chown -R www-data:www-data /var/www/ratingshub
sudo chmod -R 755 /var/www/ratingshub
sudo chmod -R 775 /var/www/ratingshub/storage
sudo chmod -R 775 /var/www/ratingshub/bootstrap/cache
```

## 🔄 Updates

```bash
# Composer Dependencies Update (nur Security)
composer update --with-dependencies --prefer-stable

# NPM Dependencies Update
npm update

# NPM Audit (Security Check)
npm audit
npm audit fix
```

## ⚙️ Server

```bash
# PHP-FPM neu starten
sudo systemctl restart php8.2-fpm

# Nginx neu starten
sudo systemctl restart nginx

# Apache neu starten
sudo systemctl restart apache2

# Supervisor neu starten (für Queues)
sudo supervisorctl restart ratingshub-worker:*
```

## 📈 Performance

```bash
# OPcache löschen
php artisan opcache:clear

# Laravel Optimize
php artisan optimize

# Alle Caches neu erstellen
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

## 🔍 Health Checks

```bash
# App Status prüfen
curl -I https://your-domain.com

# Database Connection testen
php artisan tinker
>>> DB::connection()->getPdo();

# Disk Space prüfen
df -h

# Memory Usage
free -h

# CPU Usage
top
```
