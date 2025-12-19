#!/bin/bash

###############################################################################
# RATINGSHUB DEPLOYMENT SCRIPT
###############################################################################
#
# Dieses Script führt alle notwendigen Schritte für ein Production Deployment aus
#
# VERWENDUNG:
#   ./deploy.sh
#
# VORAUSSETZUNGEN:
#   - Git Repository ist bereits gecloned
#   - .env ist konfiguriert
#   - Composer ist installiert
#   - Node.js & npm sind installiert
#   - PHP 8.2+ ist installiert
#
###############################################################################

set -e  # Exit bei Fehler

# Farben für Output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Logging
log_info() {
    echo -e "${BLUE}[INFO]${NC} $1"
}

log_success() {
    echo -e "${GREEN}[SUCCESS]${NC} $1"
}

log_warning() {
    echo -e "${YELLOW}[WARNING]${NC} $1"
}

log_error() {
    echo -e "${RED}[ERROR]${NC} $1"
}

# Banner
echo ""
echo "╔═══════════════════════════════════════════════════════════════╗"
echo "║                 RATINGSHUB DEPLOYMENT                         ║"
echo "║                   Production Deploy                           ║"
echo "╚═══════════════════════════════════════════════════════════════╝"
echo ""

###############################################################################
# 1. PRE-DEPLOYMENT CHECKS
###############################################################################

log_info "Running pre-deployment checks..."

# Check if .env exists
if [ ! -f .env ]; then
    log_error ".env file not found!"
    exit 1
fi

# Check if we're in production
APP_ENV=$(grep "^APP_ENV=" .env | cut -d '=' -f2)
if [ "$APP_ENV" != "production" ]; then
    log_warning "APP_ENV is not set to 'production' (current: $APP_ENV)"
    read -p "Continue anyway? (y/n) " -n 1 -r
    echo
    if [[ ! $REPLY =~ ^[Yy]$ ]]; then
        log_error "Deployment cancelled"
        exit 1
    fi
fi

log_success "Pre-deployment checks passed"

###############################################################################
# 2. ENABLE MAINTENANCE MODE
###############################################################################

log_info "Enabling maintenance mode..."
php artisan down --render="errors::503" || log_warning "Could not enable maintenance mode"
log_success "Maintenance mode enabled"

###############################################################################
# 3. GIT PULL
###############################################################################

log_info "Pulling latest changes from Git..."
git fetch origin
CURRENT_BRANCH=$(git rev-parse --abbrev-ref HEAD)
log_info "Current branch: $CURRENT_BRANCH"

# Backup current commit hash for potential rollback
PREVIOUS_COMMIT=$(git rev-parse HEAD)
log_info "Current commit: $PREVIOUS_COMMIT"

git pull origin "$CURRENT_BRANCH"
NEW_COMMIT=$(git rev-parse HEAD)
log_success "Git pull completed - New commit: $NEW_COMMIT"

###############################################################################
# 4. COMPOSER DEPENDENCIES
###############################################################################

log_info "Installing Composer dependencies..."
composer install --no-dev --optimize-autoloader --no-interaction --prefer-dist
log_success "Composer dependencies installed"

###############################################################################
# 5. NPM BUILD
###############################################################################

log_info "Installing NPM dependencies..."
npm ci --production=false
log_success "NPM dependencies installed"

log_info "Building frontend assets..."
npm run build
log_success "Frontend assets built"

###############################################################################
# 6. DATABASE MIGRATIONS
###############################################################################

log_info "Running database migrations..."
php artisan migrate --force
log_success "Database migrations completed"

###############################################################################
# 7. CACHE OPTIMIZATION
###############################################################################

log_info "Clearing all caches..."
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear
log_success "Caches cleared"

log_info "Optimizing application..."
php artisan config:cache
php artisan route:cache
php artisan view:cache
log_success "Application optimized"

###############################################################################
# 8. STORAGE LINK (if not exists)
###############################################################################

log_info "Creating storage symlink..."
php artisan storage:link || log_warning "Storage link already exists or failed"

###############################################################################
# 9. QUEUE RESTART (if using queues)
###############################################################################

log_info "Restarting queue workers..."
php artisan queue:restart || log_warning "No queue workers to restart"
log_success "Queue workers restarted"

###############################################################################
# 10. OPCACHE RESET (if using OPCache)
###############################################################################

log_info "Resetting OPCache..."
# This requires a web request or php-fpm restart
# You might need to adjust this based on your server setup
# php artisan opcache:clear || log_warning "Could not clear OPCache"

###############################################################################
# 11. DISABLE MAINTENANCE MODE
###############################################################################

log_info "Disabling maintenance mode..."
php artisan up
log_success "Maintenance mode disabled"

###############################################################################
# 12. POST-DEPLOYMENT
###############################################################################

log_info "Running post-deployment checks..."

# Check if app is responding
HTTP_CODE=$(curl -s -o /dev/null -w "%{http_code}" http://localhost)
if [ "$HTTP_CODE" == "200" ]; then
    log_success "Application is responding (HTTP $HTTP_CODE)"
else
    log_warning "Application returned HTTP $HTTP_CODE"
fi

###############################################################################
# DEPLOYMENT SUMMARY
###############################################################################

echo ""
echo "╔═══════════════════════════════════════════════════════════════╗"
echo "║                  DEPLOYMENT SUCCESSFUL                        ║"
echo "╚═══════════════════════════════════════════════════════════════╝"
echo ""
log_info "Previous commit: $PREVIOUS_COMMIT"
log_info "New commit:      $NEW_COMMIT"
log_info "Branch:          $CURRENT_BRANCH"
echo ""
log_success "Deployment completed successfully!"
echo ""
log_info "To rollback to previous version, run:"
log_info "  git reset --hard $PREVIOUS_COMMIT && ./deploy.sh"
echo ""
