# Bug-Report System

## Übersicht

Das Bug-Report System ermöglicht es Benutzern, Bugs, Feature Requests, Verbesserungen und Fragen direkt aus der Anwendung zu melden. Admins erhalten automatisch Email-Benachrichtigungen mit allen Details.

## Features

### Für Benutzer

- **Bug melden** - Über User-Menü → "Bug melden" oder direkt `/bug-reports/create`
- **Meine Reports** - Eigene Bug-Reports ansehen unter `/bug-reports/my-reports`
- **Status-Tracking** - Sehen, welche Reports offen/in Bearbeitung/gelöst sind
- **Zuweisungs-Info** - Sehen, welcher Admin den Report bearbeitet

### Für Admins

- **Admin-Dashboard** - Alle Bug-Reports verwalten unter `/admin/bug-reports`
- **Status-Updates** - Status ändern (open → in_progress → resolved → closed)
- **Priority-Management** - Priorität setzen (low, medium, high, critical)
- **Admin-Zuweisung** - Reports an bestimmte Admins zuweisen
- **Admin-Notizen** - Interne Notizen hinzufügen
- **Email-Benachrichtigungen** - Automatische Emails bei neuen Reports

## Report-Typen

| Typ | Beschreibung | Icon |
|-----|-------------|------|
| **Bug** | Etwas funktioniert nicht | 🐛 |
| **Feature Request** | Neue Funktion gewünscht | 💡 |
| **Improvement** | Bestehende Funktion optimieren | 🔧 |
| **Question** | Hilfe benötigt | ❓ |

## Email-Benachrichtigungen

### Konfiguration

1. **Admin-Email in .env setzen:**
   ```env
   ADMIN_EMAIL=admin@example.com
   ```

2. **Mail-Server konfigurieren:**
   ```env
   MAIL_MAILER=smtp
   MAIL_HOST=smtp.mailtrap.io
   MAIL_PORT=2525
   MAIL_USERNAME=dein_username
   MAIL_PASSWORD=dein_password
   MAIL_FROM_ADDRESS="noreply@ratingshub.com"
   MAIL_FROM_NAME="RatingsHub"
   ```

### Fallback-Verhalten

Wenn `ADMIN_EMAIL` nicht gesetzt ist, werden Emails automatisch an **alle Admin-User** gesendet (User mit `is_admin = true`).

### Email-Inhalt

Die Email enthält:

- **Header** - Type und Priority als Badges
- **Titel** - Kurzbeschreibung des Problems
- **Beschreibung** - Detaillierte Problembeschreibung
- **Schritte zum Reproduzieren** - (nur bei Bugs)
- **User-Info** - Name und Email des Melders
- **Technische Details** - Browser, OS, Page URL
- **Zeitpunkt** - Wann wurde gemeldet
- **Button** - Direkter Link zum Admin-Panel

### Email-Subject Format

```
[RatingsHub] 🐛 Bug - "Titel des Reports"
[RatingsHub] 💡 Feature Request - "Titel des Reports"
[RatingsHub] 🔧 Verbesserung - "Titel des Reports"
[RatingsHub] ❓ Frage - "Titel des Reports"
```

## Automatisch erfasste Daten

Das System erfasst automatisch:

- **Browser** - Chrome, Firefox, Safari, Edge, Opera
- **Betriebssystem** - Windows, macOS, Linux, Android, iOS
- **Page URL** - Von welcher Seite wurde gemeldet
- **Zeitpunkt** - Datum und Uhrzeit

## Workflow

1. User klickt auf "Bug melden" im User-Menü
2. User wählt Type (Bug, Feature, Improvement, Question)
3. User gibt Titel und Beschreibung ein
4. Optional: Steps to reproduce (nur bei Bug)
5. System erfasst automatisch Browser, OS, Page URL
6. Bug-Report wird in DB gespeichert
7. **Email wird automatisch an Admin gesendet**
8. Admin erhält Email mit allen Details
9. Admin öffnet Report im Admin-Panel (via Link in Email)
10. Admin ändert Status, Priorität, fügt Notizen hinzu
11. User sieht Status-Update in "Meine Reports"

## Datenbank-Schema

```sql
bug_reports
├── id
├── user_id (Foreign Key zu users)
├── title (String, max 255)
├── description (Text, min 10 Zeichen)
├── type (Enum: bug, feature, improvement, question)
├── priority (Enum: low, medium, high, critical)
├── status (Enum: open, in_progress, resolved, closed)
├── page_url (String, nullable)
├── browser (String, nullable)
├── os (String, nullable)
├── steps_to_reproduce (Text, nullable)
├── admin_notes (Text, nullable)
├── assigned_to (Foreign Key zu users, nullable)
├── resolved_at (Timestamp, nullable)
├── created_at
└── updated_at
```

## API Endpoints

### User Routes

```php
GET  /bug-reports/create        // Formular anzeigen
POST /bug-reports               // Report erstellen
GET  /bug-reports/my-reports    // Eigene Reports anzeigen
```

### Admin Routes

```php
GET    /admin/bug-reports                 // Alle Reports
GET    /admin/bug-reports/{id}            // Report Details
PATCH  /admin/bug-reports/{id}            // Report aktualisieren
DELETE /admin/bug-reports/{id}            // Report löschen
```

## Testing Email-Versand

### Lokal (Log-Datei)

Für lokale Entwicklung ist standardmäßig `MAIL_MAILER=log` gesetzt. Emails werden in `storage/logs/laravel.log` gespeichert.

### Mit Mailtrap

1. Account erstellen auf [mailtrap.io](https://mailtrap.io)
2. SMTP-Credentials in `.env` eintragen:
   ```env
   MAIL_MAILER=smtp
   MAIL_HOST=sandbox.smtp.mailtrap.io
   MAIL_PORT=2525
   MAIL_USERNAME=dein_mailtrap_username
   MAIL_PASSWORD=dein_mailtrap_password
   ```
3. Bug-Report erstellen
4. Email in Mailtrap Inbox prüfen

### Produktiv (z.B. Gmail, SendGrid)

Für Produktion einen echten Mail-Service verwenden:

**Gmail:**
```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=deine@gmail.com
MAIL_PASSWORD=app_specific_password
MAIL_ENCRYPTION=tls
```

**SendGrid:**
```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.sendgrid.net
MAIL_PORT=587
MAIL_USERNAME=apikey
MAIL_PASSWORD=dein_sendgrid_api_key
```

## Sicherheit

- ✅ Nur authentifizierte User können Reports erstellen
- ✅ User sehen nur ihre eigenen Reports
- ✅ Admin-Panel nur für Admins zugänglich
- ✅ XSS-Protection durch Blade-Escaping
- ✅ CSRF-Protection auf allen POST-Requests
- ✅ Email-Validierung für User-Email

## Performance

- **Queue-Support** - Emails können via Queue versendet werden (optional)
- **Eager Loading** - Relations werden effizient geladen
- **Pagination** - Reports werden paginiert (20 pro Seite)
- **Caching** - Browser-Detection wird nicht gecacht (User-Agent kann sich ändern)

## Queues für Emails (Optional)

Um Emails asynchron zu versenden:

1. **Mailable als ShouldQueue markieren:**
   ```php
   class BugReportCreated extends Mailable implements ShouldQueue
   ```

2. **Queue-Worker starten:**
   ```bash
   php artisan queue:work
   ```

3. **In Docker:**
   ```bash
   docker-compose exec app php artisan queue:work
   ```

## Troubleshooting

### Keine Email erhalten

1. Prüfe `ADMIN_EMAIL` in `.env`
2. Prüfe `MAIL_MAILER` Konfiguration
3. Prüfe `storage/logs/laravel.log` für Fehler
4. Teste mit `php artisan tinker`:
   ```php
   Mail::raw('Test', function($msg) {
       $msg->to('admin@example.com')->subject('Test');
   });
   ```

### Email kommt als Spam an

1. SPF/DKIM Records konfigurieren
2. Vertrauenswürdigen Mail-Service verwenden (SendGrid, Mailgun)
3. MAIL_FROM_ADDRESS auf eigene Domain setzen

### Queue-Worker läuft nicht

```bash
# Queue-Worker prüfen
php artisan queue:work --tries=3

# Failed Jobs anzeigen
php artisan queue:failed
```

## Nächste Schritte

- [ ] Screenshots zu Bug-Reports hinzufügen (File-Upload)
- [ ] User-Feedback nach Report-Lösung (Email-Benachrichtigung)
- [ ] Bug-Report Statistiken im Admin-Dashboard
- [ ] Export von Bug-Reports (CSV, PDF)
- [ ] Public Roadmap für Feature Requests
