# Déploiement : Totché (serveur de test)

Guide pour l'ops : deux dépôts, une seule commande. Rien d'autre à
configurer, l'application se prépare elle-même au premier démarrage
(migrations, données de démo, clé d'application).

## Prérequis sur le serveur

- Docker + Docker Compose (v2, la commande `docker compose`, pas `docker-compose`)
- Les deux dépôts clonés **côte à côte, dans le même dossier parent** (le
  frontend est construit depuis `../totche-front` par rapport à ce dépôt) :

```
un-dossier-quelconque/
├── justin_benin_tourisme_api/   (ce dépôt)
└── totche-front/
```

## Démarrage

```bash
cd justin_benin_tourisme_api
docker compose up -d --build
```

C'est tout. Cette seule commande :

1. Construit l'image de l'API (installe les dépendances PHP, `composer install` compris)
2. Construit l'image du frontend (build de production + Nginx)
3. Démarre MySQL, attend qu'il soit prêt
4. Démarre l'API : au premier démarrage seulement, crée le fichier
   `.env` depuis `.env.example` et génère la clé d'application
   automatiquement (rien à taper) ; à chaque démarrage : migrations et
   données de démo (idempotentes, sans risque de doublons)
5. Démarre le frontend

## Accès une fois démarré

- Frontend : `http://<adresse-du-serveur>/`
- API : `http://<adresse-du-serveur>:8000/api`
- Documentation Swagger : `http://<adresse-du-serveur>:8000/docs`
- Admin par défaut : téléphone `+22901000000`, mot de passe `admin123`
  (à changer une fois connecté, cf. "Mon Profil")

## Ce qui ne marchera pas tout de suite (par choix, pas un bug)

Ces fonctionnalités nécessitent une clé/un identifiant tiers que ce dépôt
ne peut pas contenir (secret) - elles répondent proprement ("pas encore
configuré") plutôt que de planter tant que ces valeurs ne sont pas
renseignées dans `.env` (sur le serveur, après le premier démarrage) puis
`docker compose restart app` :

- **Emails réels** (mot de passe oublié, notifications) : `MAIL_MAILER` est
  sur `log` par défaut (l'email est écrit dans les logs, jamais envoyé).
  Renseigner `MAIL_MAILER=smtp` + les identifiants d'un fournisseur (ex.
  Brevo, gratuit jusqu'à 300 emails/jour) pour un envoi réel.
- **Génération de circuit par IA** : `AI_BASE_URL`/`AI_API_KEY`/`AI_MODEL`
  vides par défaut.
- **Paiement Kkiapay** : `KKIAPAY_*` vides par défaut (créer un compte sur
  kkiapay.me pour des clés sandbox ou live).

## Si le domaine de test diffère de `localhost`

Une seule chose à changer avant de remettre la main à l'ops (pas après) :
dans `docker-compose.yml`, la section `frontend.build.args.VITE_API_URL`
(et `APP_URL`/`FRONTEND_URL` dans `.env.example`) pointent vers
`localhost` - à remplacer par le vrai domaine/IP du serveur de test avant
le premier `docker compose up --build` (l'URL de l'API est figée dans le
build du frontend, un simple changement de `.env` après coup ne suffit
pas, il faut refaire `docker compose up -d --build frontend`).

## Redémarrer après une mise à jour du code

```bash
git pull   # dans les deux dépôts
docker compose up -d --build
```
