#!/bin/bash
set -e

echo "===== Stock Analysis Application - Container Starting ====="

# Environment variables
APP_ROOT=${APP_ROOT:-/var/www/stock-analysis}
export APP_ROOT

# Create necessary directories if they don't exist
mkdir -p ${APP_ROOT}/logs
mkdir -p ${APP_ROOT}/data
mkdir -p ${APP_ROOT}/temp
mkdir -p ${APP_ROOT}/uploads
mkdir -p ${APP_ROOT}/data/cache
mkdir -p ${APP_ROOT}/data/sessions

# Set permissions
chown -R www-data:www-data ${APP_ROOT}/logs
chown -R www-data:www-data ${APP_ROOT}/data
chown -R www-data:www-data ${APP_ROOT}/temp
chown -R www-data:www-data ${APP_ROOT}/uploads

chmod -R 775 ${APP_ROOT}/logs
chmod -R 775 ${APP_ROOT}/data
chmod -R 775 ${APP_ROOT}/temp
chmod -R 775 ${APP_ROOT}/uploads

# Check if .env exists, if not create from example
if [ ! -f ${APP_ROOT}/.env ] && [ -f ${APP_ROOT}/.env.example ]; then
    echo "Creating .env from .env.example..."
    cp ${APP_ROOT}/.env.example ${APP_ROOT}/.env
    chown www-data:www-data ${APP_ROOT}/.env
    chmod 600 ${APP_ROOT}/.env
fi

# Wait for MySQL to be ready (if DB_HOST is set)
if [ ! -z "$DB_HOST" ] && [ "$DB_HOST" != "localhost" ]; then
    echo "Waiting for MySQL at $DB_HOST:${DB_PORT:-3306}..."
    timeout=60
    while ! mysqladmin ping -h"$DB_HOST" -P"${DB_PORT:-3306}" --silent 2>/dev/null; do
        timeout=$((timeout - 1))
        if [ $timeout -le 0 ]; then
            echo "ERROR: MySQL not available after 60 seconds"
            exit 1
        fi
        echo "Waiting for MySQL... ($timeout seconds remaining)"
        sleep 1
    done
    echo "MySQL is ready!"
fi

# Run database migrations if requested
if [ "$RUN_MIGRATIONS" = "true" ] && [ -f ${APP_ROOT}/scripts/migrate.php ]; then
    echo "Running database migrations..."
    su -s /bin/bash www-data -c "cd ${APP_ROOT} && php scripts/migrate.php"
fi

# Check Python environment
echo "Checking Python environment..."
if [ -f ${APP_ROOT}/venv/bin/python ]; then
    ${APP_ROOT}/venv/bin/python --version
    echo "Python packages installed:"
    ${APP_ROOT}/venv/bin/pip list | grep -E "(pandas|numpy|ta)"
else
    echo "WARNING: Python virtual environment not found at ${APP_ROOT}/venv"
fi

# Check PHP configuration
echo "PHP version:"
php -v | head -n 1

echo "PHP modules loaded:"
php -m | grep -E "(mysql|pdo|curl|json|mbstring)"

# Test Apache configuration
echo "Testing Apache configuration..."
apache2ctl configtest

echo "===== Starting services ====="

# Execute the main container command
exec "$@"
