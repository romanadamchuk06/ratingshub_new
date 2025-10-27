# RatingsHub

## Quick Start

```bash
# Start containers
docker-compose up -d

# Install dependencies
docker-compose exec app composer install

# Run migrations
docker-compose exec app php artisan migrate

# Access application
# http://localhost
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
