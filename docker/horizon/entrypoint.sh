#!/bin/sh
set -e

# Forward termination signals gracefully to Horizon
trap 'echo "Terminating Horizon gracefully..."; php /var/www/artisan horizon:terminate; exit 0' SIGTERM SIGINT

echo "Starting Laravel Horizon..."
exec php /var/www/artisan horizon
