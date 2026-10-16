#!/bin/sh
set -e

# Ensure essential Laravel directories exist
mkdir -p /var/www/storage/framework/cache/data
mkdir -p /var/www/storage/framework/sessions
mkdir -p /var/www/storage/framework/views
mkdir -p /var/www/storage/logs
mkdir -p /var/www/bootstrap/cache

# Fix permissions for web server and PHP execution
chmod -R 775 /var/www/storage /var/www/bootstrap/cache

# Execute the main command passed to the container
exec "$@"
