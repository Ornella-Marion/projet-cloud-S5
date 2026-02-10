FROM php:8.2-fpm

# Install extensions
RUN apt-get update && apt-get install -y \
    zip unzip curl libpng-dev libjpeg-dev libfreetype6-dev libonig-dev libxml2-dev libpq-dev \
    && docker-php-ext-install pdo pdo_pgsql mbstring gd \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Set working directory
WORKDIR /var/www/html

# Copy composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Copier d'abord les fichiers de dépendances (cache Docker)
COPY composer.json composer.lock* ./

# Install Laravel dependencies (sans dev pour la production)
RUN composer install --no-scripts --no-autoloader --no-dev || composer install --no-scripts --no-autoloader

# Copier le reste du code source
COPY . .

# Finaliser l'autoloader (--no-scripts pour éviter l'erreur L5Swagger en prod)
RUN composer dump-autoload --optimize --no-scripts

# Permissions
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache || true

# Create storage link inside container (since public/storage junction is excluded)
RUN ln -sf /var/www/html/storage/app/public /var/www/html/public/storage || true

# Configure PHP-FPM to listen on all interfaces
RUN sed -i 's/listen = .*/listen = 0.0.0.0:9000/' /usr/local/etc/php-fpm.d/www.conf

EXPOSE 9000
CMD ["php-fpm"]
