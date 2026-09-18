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

# # ── Composer ─────────────────────────────────────────────────
# COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www

# # ── Dépendances PHP (cache layer séparé) ─────────────────────
# COPY composer.json composer.lock ./
# RUN composer install --no-interaction --prefer-dist --optimize-autoloader --no-scripts

# # ── Code source ──────────────────────────────────────────────
COPY . .

# # ── Autoload ─────────────────────────────────────────────────
# RUN composer dump-autoload --optimize

# ── Permissions ──────────────────────────────────────────────
RUN mkdir -p storage/framework/cache storage/framework/sessions \
    storage/framework/views bootstrap/cache \
    && chown -R www-data:www-data /var/www/storage /var/www/bootstrap/cache \
    && chmod -R 775 /var/www/storage /var/www/bootstrap/cache

EXPOSE 80

# ── Démarrage ────────────────────────────────────────────────
# Ordre :
# 1. config:clear      → purge tout cache de config résiduel (voir avertissement ci-dessous)
# 2. migrate --force   → crée/met à jour les tables
# 3. storage:link      → lien public/storage pour les uploads
# 4. route:cache       → cache les routes
# 5. view:cache        → cache les vues Blade
# 6. apache2-foreground → démarre le serveur web
#
# ATTENTION : ne JAMAIS ajouter `config:cache` ici. Une fois le config Laravel
# mis en cache, env() n'est plus jamais rappelé au runtime - les variables
# d'environnement de PHPUnit (DB_CONNECTION=mysql/benin_tourisme_test pour les
# tests) sont alors silencieusement ignorées et l'app retombe sur les valeurs
# de .env figées au moment du cache (mysql/benin_tourisme, la vraie base de
# dev). C'est ce qui a causé la destruction répétée de la base de dev par
# RefreshDatabase (migrate:fresh) avant d'être diagnostiqué - voir le commit
# "fix(critique): les tests détruisaient la vraie base de dev à chaque
# exécution" pour le détail complet.
CMD ["sh", "-c", "php artisan config:clear && php artisan migrate --force && php artisan storage:link || true && php artisan route:cache && php artisan view:cache && apache2-foreground"]
