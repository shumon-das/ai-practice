FROM php:8.2-fpm

# Install dependencies
RUN apt-get update && apt-get install -y \
    git zip unzip libpq-dev libicu-dev libzip-dev libxml2-dev libonig-dev \
    && docker-php-ext-install \
       pdo pdo_pgsql xml intl opcache mbstring bcmath \
    && docker-php-ext-enable opcache

# Install Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www/html

# Copy project files
COPY . .

# Permissions (use the existing www-data user)
RUN chown -R www-data:www-data /var/www/html

USER www-data

CMD ["php-fpm"]
