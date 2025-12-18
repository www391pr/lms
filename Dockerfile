FROM php:8.3-apache

WORKDIR /var/www/html

# Install system dependencies
RUN apt-get update && apt-get install -y \
    git unzip libpng-dev libzip-dev libonig-dev libicu-dev \
    && docker-php-ext-install pdo pdo_mysql mbstring zip intl bcmath gd

# Enable Apache rewrite
RUN a2enmod rewrite

# Copy project files
COPY . .

# Install Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Install PHP dependencies
RUN composer install --no-dev --optimize-autoloader

# Laravel permissions
RUN chown -R www-data:www-data storage bootstrap/cache

# Laravel storage link
RUN php artisan storage:link || true

EXPOSE 80
CMD ["apache2-foreground"]
