# Fiches détail enrichies, localisation cartographique, témoignages plateforme

Statut : validé avec l'utilisateur, prêt pour plan d'implémentation.
Repos concernés : `justin_benin_tourisme_api` (backend) et `totche-front` (frontend).

## Contexte et objectif

L'utilisateur a montré la page détail d'un événement sur eventravel.fr (agence
de voyage) comme référence : description, "Moments forts", itinéraire jour
par jour, services inclus/non inclus, informations pratiques, galerie, fiche
technique (durée, groupe, langue, difficulté, départ), avis. Objectif :
donner aux visiteurs de Totché "toutes les réponses à leurs questions" sur
une fiche Site/Événement/Hôtel/Restaurant/Transport, adapté au contexte de
chaque type plutôt que copié tel quel (une fiche Musée n'est pas un package
tour-opérateur).

Trois chantiers, validés séparément avec l'utilisateur :

1. **Contenu enrichi + localisation** sur les 5 entités (Site, Événement,
   Hôtel, Restaurant, Transport) — le cœur de cette spec.
2. **Refonte du hero** des 5 pages détail spécifiquement (image + titre +
   ligne méta à icônes). Le hero des *autres* pages (Accueil, listes,
   À propos...) est explicitement **hors scope** — chantier séparé à
   brainstormer plus tard.
3. **Témoignages plateforme** : section "Ce que pensent nos utilisateurs"
   sur l'Accueil — sans rapport avec les avis Site/Événement déjà en place
   (ceux-là restent liés à une Réservation confirmée, cf. ROADMAP Module
   Tourisme). Ici il s'agit de témoignages sur Totché lui-même (expérience
   prestataire, touriste...), saisis à la main par l'admin.

## Hors scope (explicite)

- Hero des pages autres que les 5 fiches détail.
- Extension des avis (Site/Événement) à Hôtel/Restaurant/Transport : pas de
  flux de réservation pour ces trois entités aujourd'hui, donc pas de
  garde-fou "réservation confirmée" possible sans construire ça d'abord.
  Non traité ici.
- Témoignages liés à un vrai compte utilisateur (option 2 proposée puis
  écartée par l'utilisateur) — uniquement saisie libre par l'admin.
- Recherche d'adresse par autocomplétion (nécessiterait une clé API Google
  payante) — remplacé par clic sur carte + parsing de lien Google Maps.

## Chantier 1 — Contenu enrichi

### Modèle de données — socle commun

Nouvelles colonnes **nullable** sur `site`, `evenement`, `hotel`,
`restaurant`, `transport` (une seule migration touchant les 5 tables, sur le
modèle des migrations existantes qui ajoutent une colonne à plusieurs tables
à la fois, ex. `add_id_prestataire_to_site_and_evenement_tables`) :

| Colonne | Type | Description |
|---|---|---|
| `points_forts` | `json` | Liste de textes. Cast Eloquent `array` — pattern déjà utilisé une fois dans le projet (`Plan::$casts['fonctionnalites']`). |
| `inclus` | `json` | Liste de textes. Libellé de section adapté côté frontend par type ("Ce que le billet inclut" pour Site, "Services inclus" pour Hôtel, etc.) — même colonne partout, pas de branchement schéma. |
| `non_inclus` | `json` | Liste de textes. |
| `infos_pratiques` | `text` | Paragraphe libre. |
| `recommandations` | `text` | Paragraphe libre. |

Tous nullable : aucune fiche existante ne casse. Ajoutés au `$fillable` des
5 modèles. Éditables par Admin/Prestataire/Responsable au même titre que
`description` (pas de restriction d'ownership supplémentaire — ce sont des
champs de contenu, pas des champs de validation/statut).

### Champs spécifiques par entité

**Événement** (package multi-jours façon eventravel — le contexte le plus
proche de la référence) :

| Colonne | Type | Notes |
|---|---|---|
| `itineraire` | `json` | Liste de `{titre, description}`. Cast `array`. |
| `groupe_min` | `unsignedTinyInteger` nullable | |
| `groupe_max` | `unsignedTinyInteger` nullable | |
| `langue` | `string` nullable | Texte libre, ex. "Français, Anglais" |
| `difficulte` | `enum('facile','moderee','difficile')` nullable | |

**Migration séparée** : conversion de `date_debut`/`date_fin` de `date` vers
`datetime`. Actuellement ce sont des colonnes SQL `date` castées en
`datetime` côté modèle Eloquent (`Evenement::$casts`) — l'heure n'est donc
jamais réellement stockée, alors que la référence affiche "Départ le 2
janvier 2027 à 12:00". `doctrine/dbal` n'étant pas installé dans ce projet
(retiré/jamais présent, cf. migrations existantes qui utilisent déjà du SQL
brut pour ce type de changement), la conversion se fait en `DB::statement`
brut : `ALTER TABLE evenement MODIFY date_debut DATETIME NOT NULL` (et
`date_fin` en nullable, comme aujourd'hui). Vérifier au préalable via
`Schema::getColumnType` qu'aucune donnée existante n'a une heure à
`00:00:00` qui deviendrait trompeuse (sans conséquence en dev, pas de
donnée de prod).

**Site** :

| Colonne | Type | Notes |
|---|---|---|
| `duree_visite` | `string` nullable | Texte libre, ex. "2h", "Demi-journée" |
| `difficulte` | `enum('facile','moderee','difficile')` nullable | Pertinent pour site naturel/rando |

**Hôtel** :

| Colonne | Type | Notes |
|---|---|---|
| `heure_arrivee` | `time` nullable | Check-in |
| `heure_depart` | `time` nullable | Check-out |

**Restaurant** :

| Colonne | Type | Notes |
|---|---|---|
| `horaires` | `string` nullable | Texte libre (horaires coupés midi/soir fréquents — une paire ouverture/fermeture unique comme sur `site` serait insuffisante) |

**Transport** :

| Colonne | Type | Notes |
|---|---|---|
| `duree_trajet_estimee` | `string` nullable | `capacite` existe déjà sur `transport`, ne pas dupliquer avec un champ "groupe" |

### Validation backend

Chaque contrôleur (`SiteController`, `EvenementController`,
`HotelController`, `RestaurantController`, `TransportController`) ajoute les
nouveaux champs à `store`/`update` en `nullable|sometimes`, avec règles
spécifiques :
- `points_forts`, `inclus`, `non_inclus`, `itineraire` : `array`,
  `*.` selon le sous-type (`string` pour les listes simples,
  `array` avec `titre`/`description` requis pour `itineraire`).
- `difficulte` : `in:facile,moderee,difficile`.
- `groupe_min`/`groupe_max` : `integer|min:1`, `groupe_max` en
  `gte:groupe_min` si les deux sont fournis.

## Chantier 1bis — Localisation

### Composant `LocationPicker`

Nouveau composant `src/components/map/LocationPicker.jsx` (à côté de
`CircuitMap.jsx`, réutilise `react-leaflet` déjà en dépendance — aucune
nouvelle lib).

Props : `latitude`, `longitude`, `onChange({ latitude, longitude })`.

Rendu :
1. Champ texte "Coller un lien Google Maps" — au blur/submit, tente
   d'extraire des coordonnées via ces motifs, dans l'ordre :
   `/@(-?\d+\.\d+),(-?\d+\.\d+)/`, `/[?&]q=(-?\d+\.\d+),(-?\d+\.\d+)/`,
   `/[?&]ll=(-?\d+\.\d+),(-?\d+\.\d+)/`. Si aucun ne matche (cas des liens
   raccourcis `maps.app.goo.gl/...`, non résolubles côté client sans clé API
   payante), toast d'erreur explicite invitant à utiliser la carte.
2. Carte Leaflet, centrée sur la valeur existante ou sur le Bénin
   (`[9.3, 2.3]`, zoom 7) par défaut. Clic sur la carte → place/déplace un
   marqueur → appelle `onChange`.
3. Deux champs numériques latitude/longitude sous la carte, synchronisés
   avec le marqueur, éditables directement pour un ajustement fin.

Intégré dans les 15 formulaires de création/édition existants (5 entités ×
Admin/Prestataire/Responsable), en remplacement des deux champs numériques
`longitude`/`latitude` actuellement saisis à la main sans aide visuelle.

### Composants d'édition de listes

- `TagListInput` (`src/components/forms/TagListInput.jsx`) : éditeur de
  liste de textes répétable (ligne + bouton supprimer + bouton ajouter).
  Réutilisé pour `points_forts`, `inclus`, `non_inclus` partout.
- `ItineraryEditor` (`src/components/forms/ItineraryEditor.jsx`) : éditeur
  répétable de `{titre, description}`, utilisé uniquement dans les
  formulaires Événement (Admin/Prestataire/Responsable).

## Chantier 2 — Hero des pages détail

Sur `SiteDetail.jsx`, `EvenementDetail.jsx`, `HotelDetail.jsx`,
`RestaurantDetail.jsx`, `TransportDetail.jsx` uniquement : le hero existant
(image + overlay + titre, déjà en place depuis la refonte v2) gagne une
ligne méta à icônes sous le titre, inspirée de la référence : 📍 adresse,
📅 dates ou 🕐 durée selon le type, 👥 groupe (Événement seulement). Pas de
changement de structure DOM lourd, ajout ciblé dans le bloc
`.detail-hero__info` déjà existant.

Nouvelles sections dans `.detail-main`, affichées seulement si le champ
correspondant est renseigné (pas de section vide) :
- "Moments forts" (`points_forts`, liste à puces/icônes check)
- "Programme détaillé" (`itineraire`, Événement seulement, mise en page
  type timeline numérotée comme la référence)
- "Services inclus" / "À prévoir" (`inclus`/`non_inclus`, deux colonnes)
- "Informations pratiques" (`infos_pratiques` + `recommandations`)
- Avis (déjà en place sur Site/Événement — inchangé)

Sidebar : bloc fiche technique sous le tarif existant, aligné sur la
référence mais filtré aux champs pertinents par type (ex. Durée = 
`duree_visite` sur Site, dates sur Événement, `heure_arrivee`/`heure_depart`
sur Hôtel, `horaires` sur Restaurant, `duree_trajet_estimee` sur Transport ;
Groupe/Langue/Difficulté affichés seulement sur Événement, sauf
`difficulte` qui existe aussi sur Site).

## Chantier 3 — Témoignages plateforme

Nouvelle entité `Temoignage`, indépendante du reste.

### Backend

- Migration `create_temoignage_table` : `id`, `nom` (string), `role`
  (string, texte libre — "Prestataire", "Touriste", "Responsable
  régional"...), `message` (text), `photo` (string nullable, chemin de
  fichier, même pattern d'upload que `GalerieSite`), `actif` (boolean,
  défaut `true`), timestamps.
- `TemoignageController` : `index()` public (`GET /temoignages`, ne renvoie
  que `actif=true`, triés par `created_at desc` ou par un champ `ordre` si
  besoin de tri manuel — décidé en phase d'implémentation selon le besoin
  réel), CRUD complet sous `/admin/temoignages` (mirroir exact de
  `PlanController`, le contrôleur le plus simple du projet).
- Upload photo : `Storage::disk('public')->store('temoignages', ...)`,
  suppression du fichier physique au `destroy()` (même pattern que
  `GalerieSiteController::destroy`).

### Frontend

- `temoignagesApi` dans `services.js` (list/create/update/delete, mirroir
  `plansApi`).
- `AdminTemoignages.jsx` : nouvelle page (table + modale, mirroir
  `AdminPlans.jsx`), lien ajouté à la sidebar admin.
- `Home.jsx` : nouvelle section "Ce que pensent nos utilisateurs" — cartes
  citation (photo ou avatar par défaut, nom, rôle, message), layout à
  définir en phase d'implémentation selon l'espace disponible sur la page
  (grille ou carrousel horizontal, cohérent avec les patterns déjà en place
  sur l'Accueil comme les carrousels de sites/événements).

## Vérification

Cohérent avec la pratique déjà établie sur ce projet : chaque backend
touché est couvert par au moins un test Feature (validation des nouveaux
champs, enum `difficulte` rejeté si invalide, round-trip JSON), et chaque
étape frontend est vérifiée en réel via Playwright (formulaire rempli →
sauvegarde → rechargement → donnée persistée et affichée correctement),
avant de passer à l'étape suivante. Nettoyage des données de test après
chaque vérification, comme documenté dans le ROADMAP.

## Phasage suggéré (affiné en plan d'implémentation)

1. Backend chantier 1 (migrations + modèles + contrôleurs + tests + Swagger)
   pour les 5 entités.
2. Composants frontend partagés (`LocationPicker`, `TagListInput`,
   `ItineraryEditor`).
3. Câblage dans les 15 formulaires (5 entités × Admin/Prestataire/
   Responsable).
4. Refonte des 5 pages détail publiques (chantier 2 inclus).
5. Chantier 3 (témoignages) — indépendant, peut être fait en parallèle ou
   avant les autres si on veut une victoire rapide visible sur l'Accueil.
