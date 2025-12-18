FROM php:8.3-fpm-alpine

WORKDIR /app

# Install system dependencies
RUN apk add --no-cache \
    git \
    unzip \
    libpng-dev \
    libzip-dev \
    oniguruma-dev \
    icu-dev \
    sqlite \
    sqlite-dev

# Install PHP extensions
RUN docker-php-ext-install \
    pdo \
    pdo_sqlite \
    mbstring \
    zip \
    intl \
    bcmath \
    gd

# Install Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Create Laravel project as root
RUN composer create-project laravel/laravel .

# Create app user
RUN addgroup -S app && adduser -S -G app app

# Fix permissions BEFORE switching user
RUN chown -R app:app /app

# Switch to non-root user
USER app

EXPOSE 9000
CMD ["php-fpm"]


# FROM php:7.4-fpm-alpine  
# RUN docker-php-ext-install pdo pdo_mysql sockets
# RUN curl -sS https://getcomposer.org/installer​ | php -- \      
#     --install-dir=/usr/local/bin --filename=composer  
# COPY --from=composer:latest /usr/bin/composer /usr/bin/composer 
# WORKDIR /app 
# COPY . . 
# RUN composer install
# EXPOSE 9000
# CMD ["php-fpm"]