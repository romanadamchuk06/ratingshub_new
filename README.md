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
