# RatingsHub

## Quick Start

```bash
# Start containers
docker-compose up -d

# Install dependencies
docker-compose exec app composer install

# Run migrations
docker-compose exec app php artisan migrate

# Build frontend assets
docker-compose exec app npm install
docker-compose exec app npm run build

# Access application
# http://localhost
```

## Google OAuth Setup

1. Go to: https://console.cloud.google.com/
2. Create a new project (e.g., "RatingsHub")
3. Enable **Google My Business API**
4. Create OAuth 2.0 Credentials:
   - Application type: Web application
   - Authorized redirect URIs: `http://localhost/platforms/callback/google`
5. Add credentials to `.env`:

```env
GOOGLE_CLIENT_ID=your-client-id-here
GOOGLE_CLIENT_SECRET=your-client-secret-here
APP_URL=http://localhost
```

## Admin Setup

Um einen Benutzer als Administrator zu setzen, gibt es mehrere Möglichkeiten:

### Option 1: Via Laravel Tinker (Empfohlen)

```bash
# Öffne Tinker
docker-compose exec app php artisan tinker

# Setze Benutzer als Admin (in Tinker)
User::where('email', 'deine@email.com')->update(['is_admin' => true]);

# Oder direkt in einem Befehl
docker-compose exec app php artisan tinker --execute="App\Models\User::where('email', 'deine@email.com')->update(['is_admin' => true]);"
```

### Option 2: Direkt in der Datenbank

```bash
# Via MySQL CLI
docker-compose exec mysql mysql -u root -psecret ratingshub -e "UPDATE users SET is_admin = 1 WHERE email = 'deine@email.com';"
```

### Option 3: Artisan Command erstellen (Optional)

```bash
# Erstelle einen Command
docker-compose exec app php artisan make:command MakeUserAdmin

# Füge zum Command hinzu (app/Console/Commands/MakeUserAdmin.php):
# $this->argument('email')
# User::where('email', $email)->update(['is_admin' => true])

# Nutze den Command
docker-compose exec app php artisan user:make-admin deine@email.com
```

Nach dem Setzen als Admin erscheint der "Admin"-Link in der Navigation (Shield-Icon).

## Common Commands

```bash
# Stop containers
docker-compose down

# View logs
docker-compose logs -f

# Clear cache
docker-compose exec app php artisan config:clear

# Access container
docker-compose exec app bash
```
