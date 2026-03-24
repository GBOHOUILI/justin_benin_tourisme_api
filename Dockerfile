FROM php:8.3-cli

# Extensions PHP nécessaires pour Laravel + MySQL
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    libzip-dev \
    zip \
    unzip \
    && docker-php-ext-install \
        pdo \
        pdo_mysql \
        mbstring \
        exif \
        pcntl \
        bcmath \
        gd \
        zip \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer


WORKDIR /var/www

# Copier uniquement les fichiers de configuration d'abord pour optimiser le cache
COPY composer.json composer.lock ./

# Installer avec --no-scripts pour éviter que Laravel ne tente de lancer 
# des commandes artisan avant que tout ne soit copié
RUN composer install --no-interaction --no-scripts --no-autoloader --prefer-dist

# Maintenant on copie le reste du projet
COPY . .

# Finaliser l'autoloader
RUN composer dump-autoload --optimize

# Créer les dossiers s'ils n'existent pas et fixer les permissions
RUN mkdir -p storage/framework/cache storage/framework/sessions storage/framework/views bootstrap/cache \
    && chown -R www-data:www-data /var/www/storage /var/www/bootstrap/cache \
    && chmod -R 775 /var/www/storage /var/www/bootstrap/cache

EXPOSE 8000