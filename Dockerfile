FROM serversideup/php:8.4-fpm-nginx

# Set the document root to Laravel's public directory
ENV WEB_DOCUMENT_ROOT=/var/www/html/public

# Copy the application code
COPY --chown=www-data:www-data . /var/www/html

# Install Composer dependencies
RUN composer install --no-interaction --optimize-autoloader --no-dev

# Ensure storage directories are writable
RUN chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache
