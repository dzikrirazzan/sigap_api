web: vendor/bin/heroku-php-apache2 public/
worker: php artisan queue:work --tries=3 --timeout=60
release: php artisan migrate --force && php artisan config:cache && php artisan route:cache
