FROM php:8.3-apache

WORKDIR /var/www/html

# Install system deps
RUN apt-get update && apt-get install -y \
    git unzip libpng-dev libzip-dev libonig-dev libicu-dev \
    && docker-php-ext-install pdo pdo_mysql mbstring zip intl bcmath gd

# Enable Apache modules
RUN a2enmod rewrite headers

# 🔴 CRITICAL: Set DocumentRoot to /public
ENV APACHE_DOCUMENT_ROOT=/var/www/html/public

RUN sed -ri 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' \
    /etc/apache2/sites-available/*.conf \
    /etc/apache2/apache2.conf

# Copy project files
COPY . .

# Install Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

RUN composer install --no-dev --optimize-autoloader

# Permissions
RUN chown -R www-data:www-data storage bootstrap/cache

# Storage symlink
RUN php artisan storage:link || true

EXPOSE 80
CMD ["apache2-foreground"]
