#!/bin/sh
set -eu
php /var/www/html/bin/integration-worker.php &
exec apache2-foreground
