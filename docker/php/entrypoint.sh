#!/bin/sh
set -e

# Ensure essential Laravel directories exist
mkdir -p /var/www/storage/framework/cache/data
mkdir -p /var/www/storage/framework/sessions
mkdir -p /var/www/storage/framework/views
mkdir -p /var/www/storage/logs
mkdir -p /var/www/bootstrap/cache

# Ensure www-data owns storage and bootstrap/cache for PHP-FPM execution
chown -R www-data:www-data /var/www/storage /var/www/bootstrap/cache 2>/dev/null || true
chmod -R 775 /var/www/storage /var/www/bootstrap/cache 2>/dev/null || true

# Pre-discover packages if running on a fresh tmpfs mount
if [ ! -f /var/www/bootstrap/cache/packages.php ]; then
    su -s /bin/sh www-data -c "php /var/www/artisan package:discover --ansi" 2>/dev/null || true
fi

# Execute the main command passed to the container
exec "$@"
