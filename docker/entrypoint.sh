#!/bin/sh
set -e

# ── Bootstrap .env (zéro configuration manuelle pour l'ops) ──────────────
# Si .env n'existe pas encore (premier démarrage sur un serveur neuf), on
# le crée depuis .env.example puis on génère une APP_KEY - sans ça Laravel
# refuse de démarrer. Sur un redémarrage normal, .env existe déjà (monté en
# volume, cf. docker-compose.yml) et cette étape ne fait rien.
if [ ! -f .env ]; then
    echo "→ Aucun .env trouvé, création depuis .env.example"
    cp .env.example .env
fi

if ! grep -q "^APP_KEY=base64:" .env; then
    echo "→ Génération de APP_KEY"
    php artisan key:generate --force
fi

# ATTENTION : ne JAMAIS ajouter `config:cache` ici. Une fois le config Laravel
# mis en cache, env() n'est plus jamais rappelé au runtime - les variables
# d'environnement de PHPUnit (DB_CONNECTION=mysql/benin_tourisme_test pour les
# tests) sont alors silencieusement ignorées et l'app retombe sur les valeurs
# de .env figées au moment du cache (mysql/benin_tourisme, la vraie base de
# dev). C'est ce qui a causé la destruction répétée de la base de dev par
# RefreshDatabase (migrate:fresh) avant d'être diagnostiqué.
php artisan config:clear

# Le healthcheck MySQL de docker-compose peut passer "healthy" quelques
# secondes avant que le serveur accepte réellement de nouvelles connexions
# au tout premier démarrage sur un volume vide (constaté : la toute première
# tentative de migrate échoue avec "Connection refused", rattrapée seulement
# par le redémarrage automatique du conteneur - fragile pour un premier
# déploiement). On attend ici que la connexion PDO réponde avant de continuer.
echo "→ Attente de la disponibilité de MySQL..."
tries=0
until php artisan migrate:status > /dev/null 2>&1 || [ "$tries" -ge 15 ]; do
    tries=$((tries + 1))
    sleep 2
done

php artisan migrate --force

# Idempotents (firstOrCreate) - sûrs à rejouer à chaque démarrage, y compris
# sur un serveur déjà peuplé (redémarrage, mise à jour de l'image...).
php artisan db:seed --class=DatabaseSeeder --force
php artisan db:seed --class=DemoContentSeeder --force

php artisan storage:link || true
php artisan route:cache
php artisan view:cache

exec apache2-foreground
