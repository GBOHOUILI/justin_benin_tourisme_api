FROM php:8.3-apache

# ── Dépendances système ──────────────────────────────────────
RUN apt-get update && apt-get install -y \
    git curl libpng-dev libonig-dev libxml2-dev libzip-dev zip unzip \
    && docker-php-ext-install pdo pdo_mysql mbstring exif pcntl bcmath gd zip \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# ── Apache : activer mod_rewrite ────────────────────────────
RUN a2enmod rewrite

# ── Apache : VirtualHost Laravel ────────────────────────────
RUN echo '<VirtualHost *:80>\n\
    ServerAdmin webmaster@localhost\n\
    DocumentRoot /var/www/public\n\
    <Directory /var/www/public>\n\
    Options Indexes FollowSymLinks\n\
    AllowOverride All\n\
    Require all granted\n\
    </Directory>\n\
    ErrorLog ${APACHE_LOG_DIR}/error.log\n\
    CustomLog ${APACHE_LOG_DIR}/access.log combined\n\
    </VirtualHost>' > /etc/apache2/sites-available/000-default.conf

# ── OPcache ──────────────────────────────────────────────────
RUN echo "opcache.enable=1" >> /usr/local/etc/php/conf.d/opcache.ini \
    && echo "opcache.memory_consumption=256" >> /usr/local/etc/php/conf.d/opcache.ini \
    && echo "opcache.interned_strings_buffer=16" >> /usr/local/etc/php/conf.d/opcache.ini \
    && echo "opcache.max_accelerated_files=20000" >> /usr/local/etc/php/conf.d/opcache.ini \
    && echo "opcache.validate_timestamps=0" >> /usr/local/etc/php/conf.d/opcache.ini \
    && echo "opcache.revalidate_freq=0" >> /usr/local/etc/php/conf.d/opcache.ini \
    && echo "opcache.fast_shutdown=1" >> /usr/local/etc/php/conf.d/opcache.ini

# ── PHP Memory ───────────────────────────────────────────────
RUN echo "memory_limit=512M" > /usr/local/etc/php/conf.d/memory-limit.ini

# ── Composer ─────────────────────────────────────────────────
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www

# ── Dépendances PHP (cache layer séparé - composer.json/.lock d'abord,
# le reste du code ensuite, pour ne réinstaller les paquets que si l'un des
# deux change) ─────────────────────────────────────────────────
COPY composer.json composer.lock ./
RUN composer install --no-interaction --prefer-dist --optimize-autoloader --no-scripts

# ── Code source ──────────────────────────────────────────────
COPY . .

# ── Autoload ─────────────────────────────────────────────────
RUN composer dump-autoload --optimize

# ── Permissions ──────────────────────────────────────────────
RUN mkdir -p storage/framework/cache storage/framework/sessions \
    storage/framework/views bootstrap/cache \
    && chown -R www-data:www-data /var/www/storage /var/www/bootstrap/cache \
    && chmod -R 775 /var/www/storage /var/www/bootstrap/cache

RUN chmod +x docker/entrypoint.sh

EXPOSE 80

# ── Démarrage : voir docker/entrypoint.sh (bootstrap .env/APP_KEY sur un
# serveur neuf, migrations, seed idempotent, puis Apache). ────────────────
CMD ["docker/entrypoint.sh"]
