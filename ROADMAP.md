# Roadmap

> Mis à jour à chaque tâche terminée. Ne pas cocher une case sans l'avoir vérifiée (route testée, test lancé, etc.).

## Module Refonte UX/UI (totche-front)

Démarré le 2026-09-16 (nuit, travail autonome). Branche `feature/refonte-ux-ui` (pas mergée — à valider par l'utilisateur). Périmètre strict : CSS/layout/composants visuels uniquement, **aucune logique métier, appel API ou controller backend touché**. Vérification Playwright (visuel + fonctionnel : boutons cliquent, formulaires soumettent) après chaque page, commit ciblé (fichiers concernés uniquement) avant de passer à la suivante.

### Constat avant refonte

L'existant (`src/index.css`, 566 lignes, seul fichier de style global) est un habillage "SaaS générique" compétent mais peu distinctif : Playfair Display + DM Sans (pairing extrêmement courant), rouge #E63946 (rouge CTA générique de template), even radius partout (8px/16px sur boutons/cards/badges/inputs sans hiérarchie), cards identiques avec la même ombre grise, badges en pilules partout, hero avec grande barre de recherche blanche arrondie flottante (motif "clone Airbnb"). Structurellement solide (variables CSS, BEM-like classes cohérentes), donc on garde l'architecture et on retravaille les tokens + composants par-dessus.

### Direction v1 (indigo/brique/or/palmier) — SUPERSÉDÉE le 2026-09-16

La direction couleur v1 ci-dessous a été abandonnée sur instruction explicite de l'utilisateur après revue : retour au **rouge d'origine comme couleur primaire**, et refonte plus profonde de la composition (pas seulement des couleurs). Conservée ici pour l'historique ; voir "Direction v2" plus bas pour ce qui est réellement appliqué.

<details>
<summary>Détail v1 (abandonné)</summary>

Couleurs : `--ink #1C1B18`, `--indigo #202C46` (sections sombres), `--brick #B8432E`/`--brick-dark #96331F` (accent primaire), `--gold #CC9A3A`, `--palm #3D6B4F`, `--sand #F7EFDF`, `--paper #FFFCF6`, `--stone #E4D9C4`/`--stone-600 #7A6F5D`. Typographie Besley + Karla. Coins nets, motif filet doré + losange, ombre retirée au repos.

</details>

### Direction v2 — analyse de références réelles (armenia.travel + visitpa.com)

Étudiés en réel via Playwright (screenshots + `getComputedStyle` sur les deux sites, pas une impression visuelle vague) le 2026-09-16, sur demande explicite de l'utilisateur : "éviter d'inventer les proportions CSS à l'aveugle". Chiffres et patterns ci-dessous viennent directement des deux sites, pas d'une estimation.

**Mesures relevées (viewport 1440px)** :
| | armenia.travel | visitpa.com |
|---|---|---|
| H1 | 48px / line-height 62px (1.3×) / poids 400 / largeur bridée ~986px | 54px / line-height 60px (1.11×) / poids 700 / largeur bridée ~960px |
| Corps de texte | 20px / line-height 32px (1.6×) / poids 500 | 16px / line-height 24px (1.5×) / poids 400 |
| Header | 81px de haut | 90px de haut |
| Conteneur de contenu | ~1192px (marges ~124px de chaque côté à 1440px) | similaire, colonnes ~960px pour le texte éditorial |
| Lien de carte (CTA) | — | 16px / poids 700 / **rgb(189,37,41)** — un rouge proche de celui qu'on restaure / majuscules / pas de fond, juste un lien |
| Image de carte "liste" (Things to Do) | — | ratio 3:2 (490×327), **border-radius: 0** |
| Radius | 0 sur la quasi-totalité des images | 0 sur les images de liste |

**Patterns structurels observés, à reprendre (pas à copier au pixel)** :
1. **Hero** : soit image plein cadre pure sans texte dessus (visitpa — le H1 arrive dans la section suivante, sur fond blanc), soit image plein cadre avec un masque bas arrondi organique puis titre+sous-titre en deux colonnes très inégales juste en dessous (armenia — pas de titre superposé sur la photo elle-même).
2. **Cartes de liste/catalogue** (le plus transférable pour Sites/Événements) : **aucun cadre** — pas de bordure, pas d'ombre, pas de radius. Image plate (ratio 3:2), *sous* l'image (jamais dessus) : titre (19px/poids 500), 2–3 lignes de description courte, puis un **lien texte rouge en majuscules** ("EN SAVOIR PLUS →"), jamais un bouton plein. C'est la référence directe pour refaire `SiteCard`/`EventCard`.
3. **Tuiles de navigation par catégorie** (différent des cartes de liste) : image + dégradé bas + un seul label blanc en gras superposé, aucune description — utilisé uniquement pour des liens de navigation généraux ("Choses à faire", "Événements"), pas pour lister des items individuels.
4. **Carrousels horizontaux** (flèches rondes) utilisés à la place d'une grille qui wrap, pour éviter l'effet "grille uniforme" quand le nombre d'items est variable.
5. **Rythme des sections** : alternance section pleine largeur immersive (photo ou fond sombre) / section contenue sur fond clair, jamais deux sections consécutives avec la même structure.
6. **Rouge utilisé avec parcimonie** : lien de carte, bouton outline ("PLONGEZ DANS L'AUTOMNE" — contour rouge, fond transparent), un bloc "MENU" plein rouge isolé dans la nav (visitpa). Jamais en fond de section, jamais sur plusieurs boutons à la fois.
7. **Deux familles typographiques distinctes** dans les deux cas (une plus expressive pour les grands titres émotionnels, une neutre pour le fonctionnel/UI) — confirme qu'un vrai pairing reste justifié, mais la hiérarchie vient de la taille/graisse/interlignage, pas du choix de police seul.

**Couleur retenue pour Totché** : rouge d'origine (`--red: #E63946`, valeur pré-refonte) comme couleur primaire — CTA, liens de carte, états actifs, quelques accents graphiques. Base neutre claire (blanc/gris très clair) pour laisser respirer les photos, pas de fond coloré systématique. `--black`/`--gray-*` repassent sur une échelle neutre classique (pas indigo).

**Typographie retenue** : on garde une combinaison à deux polices (conforme à la demande : pas de police système), mais recalibrée sur les proportions mesurées plutôt que sur un choix esthétique seul — voir mise en œuvre par page ci-dessous pour les valeurs exactes utilisées.

**Ce qu'on arrête de faire (rappel instructions)** : cards identiques répétées, grille uniforme, badges/pills en excès, boutons tous identiques, gros blocs de texte, radius systématique, ombre sur toutes les cartes, sections toutes structurées pareil, décoration sans fonction.

### Ordre de priorité imposé (2026-09-16, instruction explicite)

1. Accueil, 2. Liste des sites, 3. Liste des événements, 4. Détail d'un site, 5. Détail d'un événement, 6. Réservation. Admin en dernier.

- [x] **1. Accueil** — voir avancement ci-dessous
- [x] **2. Liste des sites** (`Sites.jsx`) — zéro changement de code nécessaire, hérite entièrement de `SiteCard`/`.page-hero`/`.filters`. Vérifié en réel (Playwright, filtres cliqués, 0 erreur console)
- [x] **3. Liste des événements** (`Evenements.jsx`) — idem, zéro changement de code, hérite de `EventCard`. Vérifié en réel
- [x] **4. Détail d'un site** (`SiteDetail.jsx`) — analysé en entier ; composition déjà conforme à la direction v2 (héritée du passage v1→v2 sur `index.css` : hero plein cadre + overlay + méta, layout éditorial 2 colonnes, cards plates sans ombre pour la sidebar). Un seul résidu visuel trouvé et corrigé (border-radius codé en dur sur le bloc "Total"). Vérifié en réel avec vraies photos (voir plus haut) : hero, carrousel, galerie de miniatures, ouverture/fermeture du formulaire de réservation (toggle bouton RÉSERVER/ANNULER), 0 erreur console. Commit `3430c2b`. Gap fonctionnel constaté et documenté séparément : pas d'affichage des avis existants (nécessiterait un nouvel appel API, hors périmètre)
- [ ] **5. Détail d'un événement** (`EvenementDetail.jsx`) — à faire
- [ ] **6. Réservation** — à identifier précisément (formulaire inline déjà présent sur les pages détail + `MesReservations.jsx`) et traiter

### Avancement

- [x] **Note d'implémentation** : les variables CSS ont gardé leurs noms d'origine (`--red`, `--black`, `--gray-100/300/500/700/900`, `--white`) plutôt que d'être renommées `--brick`/`--indigo`/etc. comme envisagé dans le plan initial — uniquement leurs *valeurs* ont changé. Choix délibéré : la majorité des pages contiennent des styles inline (`style={{ color: 'var(--gray-500)', ... }}`) référençant ces noms ; les garder a permis à tout le reste du frontend d'hériter automatiquement de la nouvelle palette sans avoir à toucher chaque fichier. `--sand` et `--radius-full` sont les deux seules variables réellement nouvelles.
- [x] `src/index.css` réécrit intégralement (mêmes sélecteurs/classes, valeurs et quelques composants retravaillés — cards sans ombre au repos, badges à bordure gauche, page-hero avec motif filet+losange, hero search rectangulaire). Commit `aeeae04`.
- [x] Couleurs codées en dur (hors `index.css`) harmonisées : `App.jsx` (Toaster global), `AProposContact.jsx` (dégradé hero + typo "d'Totché"→"de Totché"), `Sites.jsx`/`Evenements.jsx` (bug préexistant corrigé : `var(--gray-200)` inexistante rendait les champs Prix min/max sans bordure visible ; select de rayon sans style du tout). Commit `32e17bc`.
- [x] Pages admin harmonisées : `Dashboard.jsx` (stat cards vert/bleu/violet/orange Tailwind → palette de marque, badges pilules → `.status-badge`), `AdminAvis.jsx`, `AdminAdmins.jsx`, `AdminTickets.jsx` (couleurs vertes/rouges codées en dur → tokens), 6 fichiers avec des `var(--gray-50/200/400/600/800)` invalides (variables inexistantes, styles silencieusement ignorés) corrigés vers les vraies variables. `AdminLogin.jsx` : fond quasi-noir + icône rouge codés en dur → indigo/brique. Commit `78c18f7`.
- [x] Placeholder d'image des cartes (`.card__img-placeholder`) enrichi d'un dégradé sable→or au lieu d'un gris plat, pour les sites/événements sans photo.
- [x] Petit fix visuel groupé avec le travail Kkiapay/Billetterie non commité (`MesReservations.jsx`, modale QR) : même bug `var(--gray-50)` inexistante — sera inclus dans le prochain commit de ce lot fonctionnel, pas isolé exprès (fichier déjà en cours de modification pour la Billetterie).
- [x] Pages vérifiées visuellement ET fonctionnellement en réel (Playwright, avec de vraies données de démo — 4 sites et 2 événements aux noms réels du Bénin ajoutés en base de dev pour la QA, ex. Palais Royal d'Abomey, Route des Esclaves de Ouidah, Cité Lacustre de Ganvié) : Accueil, Sites (liste + détail + réservation), Événements (liste + détail), Inscription, Connexion, Mes Réservations (+ billet QR), Profil, Contact, À Propos, Connexion admin, Dashboard admin, Modération avis, Administrateurs. Aucune fonctionnalité cassée à aucune étape (navigation, inscription, réservation, paiement du flux existant, affichage billet).
- [x] Spot-check visuel de `AdminSites.jsx` et `AdminUsers.jsx` (liste, tableau, badges "Actif") après coup : déjà cohérents sans retouche supplémentaire, car ces pages utilisent directement les classes partagées (`.admin-table`, `.status-badge`) plutôt que des styles inline — elles ont donc hérité automatiquement de la nouvelle palette via `index.css`.
- [ ] **Pas fait / laissé pour une prochaine session** : `AdminEvenements.jsx`/`AdminCategories.jsx`/`AdminPrix.jsx`/`AdminUtilisateurs` n'ont pas été ouvertes individuellement cette nuit (probablement déjà correctes par le même mécanisme d'héritage, à confirmer). Les puces de filtre de `AdminAvis.jsx` restent en forme de pilule (pas alignées sur le nouveau style rectangulaire des filtres publics).
- [ ] Compte de test créé cette nuit pour la QA (`design-qa-nuit@test.local`, avec une réservation confirmée sur le Palais Royal d'Abomey) laissé en base de dev, dans le même esprit que les 4 sites/2 événements de démo — à nettoyer ou garder selon préférence.
- ⚠️ **Bug préexistant repéré, non corrigé (hors périmètre visuel)** : `AdminAdmins.jsx` compare `a.status === 'actif'` alors que l'API renvoie un booléen/entier — le badge affiche la valeur brute (`1`) au lieu de "Actif"/"Inactif". C'est un bug de logique/mapping de données, pas un problème CSS.
- ⚠️ **Observation, pas un bug de cette session** : se connecter avec un token admin puis naviguer vers une route touriste (`/mes-reservations`, réservation…) renvoie un 401 qui redirige vers `/admin/login` au lieu de `/connexion` — comportement de l'intercepteur axios global, pas lié au visuel, remarqué pendant les tests fonctionnels.
- [x] **Photographie corrigée (2026-09-16)** : les 4 sites et 2 événements de démo n'avaient AUCUNE image en base (`galerie_site`/`gallerie_evnmt` vides) — chaque carte/hero affichait un placeholder gris uni, ce qui rendait impossible de juger la direction "photographie centrale" du brief. Recherché et vérifié visuellement (téléchargé + inspecté chaque image avant usage, pas de choix à l'aveugle) de vraies photos libres de droits sur Wikimedia Commons, spécifiques à chaque lieu réel : Palais Royal d'Abomey (bas-reliefs/architecture), Route des Esclaves de Ouidah (esplanade côté océan), Cité Lacustre de Ganvié (village sur pilotis, marché flottant), Plage de Grand-Popo, Festival Vodun Days de Ouidah (procession en tenue traditionnelle), Foire de l'Artisanat (marché Dantokpa, Cotonou — proxy faute de photo dédiée à cet événement précis). Seedées via `GalerieSite`/`GallerieEvnmt::create()` en base de dev (URLs Wikimedia directes en `url_fichier`, pas de copie de fichier). Tuile CTA "Évènements" de l'accueil corrigée en même temps (`--cta-block__vodun` pointait vers une texture Unsplash quasi noire invisible → vraie photo Vodun Days). Impact visuel majeur, à conserver comme données de démo QA au même titre que les sites/événements eux-mêmes.

**Branche `feature/refonte-ux-ui`** : 3 commits, non mergée sur `main` — à relire et valider avant merge (`--no-ff` comme convenu).

## Module Tourisme

Périmètre actuel : sites touristiques + événements (catégories, galeries, prix, réservations, tickets, avis, gestion admin). C'est un sous-ensemble du cahier des charges SaaS complet (voir audit du 2026-09-15) — Prestataire, Hotel/Restaurant/Transport, Circuit IA, Commande/Paiement, Abonnement/Plan et Notifications sont hors périmètre de ce module et ne sont pas listés ici.

### Backend — sécurité

- [x] IDOR `UserController::show/update/destroy` — vérification `$user->id === $request->user()->id` (commit `8660fd1`)
- [x] IDOR `AvisController::store/update/destroy` — vérification de propriété via `avis → utilisation → ticket → reservation → id_user` (commit `8660fd1`)
- [x] Bug de validation `exists:utilisations,id` → `exists:utilisation,id` (mauvais nom de table, 500 systématique sur `POST /avis`) (commit `8660fd1`)
- [x] Retrait du champ `status` de la validation `store`/`update` d'`AvisController` — un touriste ne peut plus s'auto-approuver un avis (commit `8660fd1`)
- [x] `composer update` — 38 advisories résolues, `composer audit` propre (commit `52a544e`)
- [x] Endpoint admin `DELETE /admin/users/{user}` ajouté (`routes/api.php` + `UserController::destroy` rendu guard-aware : ownership vérifiée seulement quand l'appelant est un `User`, pas un `Admin`) — testé en réel : admin supprime n'importe quel compte (200), touriste toujours bloqué sur autrui (403) via `DELETE /users/{id}`
- [ ] Centraliser le contrôle d'ownership (actuellement dupliqué à la main dans `ReservationController`, `UserController`, `AvisController`) — risque de récidive du même bug IDOR sur un futur contrôleur si le pattern n'est pas généralisé (policy ou trait)
- [ ] `doctrine/annotations` signalé abandonné par `composer audit` — évaluer un remplacement ou documenter qu'on l'assume tel quel
- [x] `UtilisationController` validait avec `exists:tickets,id` (table inexistante, la vraie table est `ticket` singulier) → 500 systématique sur `POST /admin/utilisations`. Corrigé en `exists:ticket,id` (même classe de bug que celui déjà corrigé sur `AvisController`). Vérifié en réel via la vraie route HTTP (201, flux complet reservation → ticket → utilisation). Piège rencontré en testant : `opcache.validate_timestamps=0` (Dockerfile) fige le bytecode compilé — un `docker restart` du conteneur app est nécessaire après toute modification de fichier PHP pour qu'elle soit prise en compte, un simple `docker start`/bind-mount ne suffit pas

### Backend — Prestataire (fiche minimale)

- [x] Table `prestataire` créée (`nom_entreprise`, `type_prestataire`, `status`) — pas de compte/flux d'inscription, conforme au périmètre minimal validé
- [x] FK `id_prestataire` (nullable, `nullOnDelete`) ajoutée sur `site` et `evenement`, relations `prestataire()`/`sites()`/`evenements()` sur les modèles — vérifié via tinker (relation chargée dans les deux sens)
- [x] Commande CLI `php artisan prestataire:create` pour peupler la table en attendant le module Prestataire complet (inscription, compte, dashboard) — testée en réel
- [x] `type_prestataire` contraint à un enum PHP natif `App\Enums\TypePrestataire` (site, evenement, hotel, restaurant, transport) — casté dans `Prestataire::$casts`, validé dans `prestataire:create` (rejet + message clair sur valeur hors enum, testé en réel). Colonne DB reste `string` (pas de contrainte au niveau SQL, uniquement applicative — pas demandé). Audit des lignes existantes hors enum fait via requête `DB::table()` brute (pas via Eloquent, qui lèverait une `ValueError` sur une valeur invalide à la lecture) : table vide, 0 ligne à traiter
- [ ] Pas de flux d'inscription/compte Prestataire — reporté au module Prestataire (hors périmètre Tourisme, décision déjà actée)
- [ ] Pas de sélecteur `id_prestataire` dans les formulaires admin `AdminSites.jsx`/`AdminEvenements.jsx` côté frontend — à ajouter si/quand le rattachement prestataire devient un besoin réel pendant la phase Tourisme (pas fait maintenant, non demandé)

### Backend — fonctionnel

- [x] Recherche par proximité (géolocalisation) sur `GET /sites` et `GET /evenements` — filtres `lat`/`lng`/`radius`, formule Haversine en `whereRaw`/`orderByRaw` (pas de `having` sur alias, pour rester compatible avec `paginate()`), colonne `distance_km` exposée via `selectRaw`. Vérifié en réel avec 3 sites à distances connues (Cotonou/Porto-Novo/Natitingou) : rayon, tri et valeurs de distance corrects
- [x] Recherche par budget (fourchette de prix) sur `GET /sites` et `GET /evenements` — filtres `prix_min`/`prix_max` via `whereHas('prix', ...)`. Vérifié en réel
- [ ] Workflow de validation sur `Site` (actuellement `status` booléen simple) à aligner sur celui d'`Evenement` (`en_attente/valide/rejete/suspendu`)
- [ ] Trancher le workflow Avis : aujourd'hui un avis exige une `Utilisation` (visite déjà enregistrée par un admin) préexistante — décider si c'est voulu ou si `Avis` doit pouvoir se rattacher directement à un service comme dans le MCD cible
- [ ] QR code sur `Ticket` (colonne `code_qr` absente, aucune génération)

### Backend — tests

- [ ] Aucun test automatisé au-delà des 2 stubs Laravel par défaut
- [ ] Test de non-régression sur le fix IDOR (`UserController`, `AvisController`)
- [ ] Couverture feature minimale : Auth (register/login/admin login), Reservation (ownership), Avis (ownership + statut)

### Frontend (totche-front) — bugs bloquants (dette existante)

- [x] `AdminAvis.jsx` appelait `avisApi.approve()`/`avisApi.reject()` (inexistants) → renommé en `avisApi.approuver()`/`avisApi.rejeter()` (les vrais exports de `services.js`)
- [x] `AdminAvis.jsx` attendait `note`/`contenu`/`user`/`site` à plat sur l'Avis → remplacé par les helpers `avisUser()`/`avisCible()` qui lisent la forme réelle `utilisation.ticket.reservation.{user,site,evenement}` ; colonne/bloc "Note" retiré (pas de champ note dans le modèle Avis actuel — si une notation chiffrée est voulue, c'est une nouvelle fonctionnalité à traiter séparément, pas ce bug). Backend : eager-load étendu dans `AvisController::index/show` pour inclure `site`/`evenement` (absents avant, seul `user` était chargé). Vérifié en réel : flux complet reservation → ticket → utilisation → avis → `GET /avis`, JSON confirmé conforme aux chemins utilisés par le composant
- [x] `AdminUsers.jsx` appelait `usersApi.delete(id)` sur la route sanctum (401 depuis une session admin) → repointé sur `usersApi.delete` = `DELETE /admin/users/{id}` (nouvelle route admin, cf. section Backend — sécurité ci-dessus) ; `usersApi.deleteSelf` ajouté séparément pour un futur flux self-service
- [x] Vérification navigateur réelle (Playwright, 2026-09-15) : login admin → `/admin/avis` → clic Approuver (statut passe à "Approuvé", bouton disparaît) → clic Rejeter (statut passe à "Rejeté") sur un avis de test réel (chaîne user → reservation → ticket → utilisation → avis). 0 erreur console. Données de test supprimées après vérification

### Frontend — flux manquants

- [ ] Formulaire de dépôt d'avis côté public : aucun appel à `avisApi.create` dans `src/pages/public` — bloqué par le workflow Avis backend à clarifier
- [ ] **Découvert pendant la refonte v2 (page Site détail, 2026-09-16)** : `SiteDetail.jsx` n'affiche non plus AUCUN avis existant (ni liste, ni note moyenne) — vérifié en seedant un avis `approuve` réel via tinker (chaîne User → Reservation → Ticket → Utilisation → Avis sur "Palais Royal d'Abomey") : rien ne s'affiche après rechargement, `grep avis` sur le fichier ne remonte aucune occurrence. Le composant `Stars` est importé mais jamais utilisé (mort). Non traité dans cette passe : afficher les avis nécessiterait un nouvel appel API (`avisApi.list` côté public n'existe pas encore côté `src/pages/public`), ce qui sort du périmètre strictement visuel de la refonte v2 (« ne modifie aucun appel API »). Donnée de test nettoyée après vérification.
- [x] UI de recherche par proximité/budget sur `Sites.jsx`/`Evenements.jsx` : bouton "Près de moi" (`navigator.geolocation`) + select de rayon, inputs prix min/max. Distance affichée sur `SiteCard`/`EventCard` (`· X km`) quand `distance_km` est présent dans la réponse. **Vérifié en réel (Playwright, 2026-09-15)** avec 3 sites QA à coordonnées/prix connus (Cotonou/Porto-Novo/Natitingou) : rayon 25km n'affiche que Cotonou (0.0 km), rayon 50km ajoute Porto-Novo (29.9 km) triés par distance croissante ; filtre prix_min/prix_max exclut correctement les sites hors fourchette ; requêtes `GET /api/sites?lat=...&lng=...&radius=...` et `?prix_min=...&prix_max=...` toutes 200 OK, 0 erreur console. `Evenements.jsx` vérifié en parallèle (mêmes contrôles présents, requête `GET /api/evenements?lat=...` bien envoyée, 0 erreur ; pas de données événements en base pour vérifier le tri par distance, logique identique à `Sites.jsx` donc considérée couverte). Données de test supprimées après vérification
- [ ] Affichage QR code / ticket électronique — en attente de la fonctionnalité backend

### Frontend — à vérifier

- [ ] `Profil.jsx` (`usersApi.update(user.id, ...)`) après le fix IDOR backend — l'ID utilisé est bien celui de l'utilisateur connecté, a priori sans impact, à confirmer par un test manuel de mise à jour de profil

## Module Commande + Paiement

Démarré le 2026-09-15. Provider : Kkiapay (mobile money + carte). Règles produit actées : paiement obligatoire seulement si le tarif (`Prix`) associé a un montant > 0 ; échelonnement possible mais optionnel, piloté par `Prix.echelonnable` ; une `Commande` regroupe potentiellement plusieurs `Reservation` payantes en un seul flux de paiement (une ligne `Paiement` par tentative/échéance, pas de duplication de Commande).

### Backend — schéma

- [x] Alter `prix` : `echelonnable` (bool, default false) + `nombre_echeances` (tinyint nullable, rempli seulement si échelonnable) — migration `2026_09_15_160000`
- [x] Table `commande` (`reference`, `id_user`, `montant_total`, `statut` enum `en_attente/payee/echouee/annulee`) — migration `2026_09_15_160001`
- [x] Table `paiement` (`id_commande`, `reference_transaction`, `montant`, `moyen`, `statut` enum `en_attente/reussi/echoue`, `numero_echeance` nullable, `payload_webhook` json, `paid_at`) — migration `2026_09_15_160002`
- [x] Alter `reservation` : `id_prix` (FK nullable → `prix`, lien vers le tarif exact choisi — absent auparavant, `reservation.prix` n'était qu'un decimal libre saisi côté client), `id_commande` (FK nullable → `commande`), `statut` enum `confirmee/en_attente_paiement/annulee` — migration `2026_09_15_160003`
- [x] Modèles Eloquent `Commande` (relations `user`, `reservations`, `paiements`) et `Paiement` (relation `commande`, cast `payload_webhook` → array, `paid_at` → datetime) créés ; `Prix` (relation `reservations`, cast `echelonnable` → bool), `Reservation` (relations `tarif`, `commande`), `User` (relation `commandes`) mis à jour
- [x] Vérifié en réel (2026-09-15) : `migrate --force` appliqué sans erreur (4/4 migrations `Ran`), colonnes confirmées via `Schema::getColumnListing` sur les 4 tables. Test tinker en transaction (`DB::beginTransaction`/`rollBack`) : création Site → Prix (échelonnable, 2 échéances) → Reservation (`statut=en_attente_paiement`, `id_prix` lié) → Commande → Paiement (échéance 1/2), vérification des relations dans les deux sens (`commande->reservations`, `commande->paiements`, `reservation->tarif`, `reservation->commande`, `user->commandes`, `prix->reservations`), puis simulation d'un webhook Kkiapay (`statut → reussi`, `payload_webhook` json décodé correctement). Rollback : base revenue à 0 ligne sur les 5 tables concernées, aucune donnée de test résiduelle

### Backend — intégration Kkiapay

- [x] `config/services.php` : bloc `kkiapay` (`public_key`/`private_key`/`secret`/`sandbox`) lu depuis `.env`. Vérifié : `.env` confirmé hors git (`git check-ignore`) sur les deux repos (api + totche-front), clés chargées via `config('services.kkiapay.*')`
- [x] `CommandeController` (`index`/`store`/`show`, routes `auth:sanctum`) : `store` ne regroupe que des `Reservation` de l'utilisateur connecté, `statut=en_attente_paiement`, sans `id_commande` existant ; génère 1 ligne `Paiement` (ou N si `echelonner=true` et tarif unique `echelonnable`, montant réparti avec arrondi absorbé par la dernière échéance)
- [x] `PaiementController::verifier` (PATCH `/paiements/{id}/verifier`, `auth:sanctum`) : confirmation synchrone déclenchée côté client après le widget — revérifie la transaction directement auprès de l'API Kkiapay (`POST /api/v1/transactions/status`, jamais sur la seule foi du client), compare montant/statut, cascade `paiement→reussi` puis `commande→payee` + `reservation→confirmee` si toutes les échéances sont réglées. Idempotent si déjà `reussi`
- [x] `PaiementController::webhook` (POST `/webhooks/kkiapay`, public) : authenticité vérifiée via header `x-kkiapay-secret` (`hash_equals`, pas de HMAC côté Kkiapay — c'est le secret partagé brut, conforme à leur doc officielle), résolution du `Paiement` local par `reference_transaction` puis par repli sur `data`/`stateData` transmis à l'ouverture du widget, re-vérification systématique auprès de l'API Kkiapay avant d'acter quoi que ce soit (jamais confiance aveugle dans le payload webhook), réponse 200 même sur paiement introuvable pour éviter les 5 relances Kkiapay
- [x] Vérifié en réel en sandbox (2026-09-15), bout en bout : appel HTTP réel vers `api-sandbox.kkiapay.me` (réponse authentique `TRANSACTION_NOT_FOUND` sur un id bidon, prouvant la connectivité/auth), rejet webhook sur secret invalide (401), résolution webhook par `stateData`/`data`, anti double-booking d'une réservation déjà en commande (422), puis **paiement sandbox réel via le widget JS** (numéro de test MTN `97000000`) piloté par Playwright : transaction confirmée par Kkiapay (`G_TA7q1qZ`), `PATCH /paiements/{id}/verifier` a authentifié la transaction directement auprès de Kkiapay (payload complet retourné : `status=SUCCESS`, `source=MOBILE_MONEY`, `source_common_name=mtn-benin`, montant exact), cascade `paiement=reussi` → `commande=payee` → `reservation=confirmee` constatée en base, ré-appel idempotent confirmé des deux côtés (verifier + webhook). Toutes les données de test supprimées après vérification

### Frontend (totche-front) — Kkiapay

- [x] Script `<script src="https://cdn.kkiapay.me/k.js">` ajouté dans `index.html`
- [x] `VITE_KKIAPAY_PUBLIC_KEY`/`VITE_KKIAPAY_SANDBOX` ajoutés au `.env` (clé publique copiée depuis le backend — non sensible par design, faite pour être embarquée côté client). `.env` confirmé hors git
- [x] `commandesApi`/`paiementsApi` ajoutés à `src/api/services.js`
- [x] `MesReservations.jsx` : bouton "Payer" affiché uniquement si `reservation.statut === 'en_attente_paiement'`, déclenche `commandesApi.create` puis `window.openKkiapayWidget` (montant/clé fournis par le serveur, jamais recalculés côté client), `addSuccessListener` appelle `paiementsApi.verifier` pour la confirmation serveur
- [x] Vérifié en réel (Playwright, 2026-09-15) : widget Kkiapay s'ouvre correctement en mode sandbox (bandeau "Vous êtes en mode Sandbox" visible), formulaire Mobile Money rempli et soumis avec le numéro de test MTN, écran "Paiement réussi !" affiché par Kkiapay, bouton "Payer" disparaît de l'UI une fois la réservation confirmée après rechargement. 0 erreur console liée au widget
- [x] Bug préexistant corrigé : `MesReservations.jsx` affichait un badge de statut basé sur `r.status` (valeurs `confirme`/`annule`/`en_attente`), un champ qui n'a jamais existé sur `Reservation` — retombait toujours sur "En attente" par défaut. `STATUS_LABEL`/`STATUS_COLOR` alignés sur les vraies valeurs de `r.statut` (`confirmee`/`en_attente_paiement`/`annulee`). Vérifié en réel (Playwright, 2026-09-15) : une réservation gratuite affiche "Confirmé" (vert), une réservation payante non réglée affiche "En Attente de Paiement" (jaune)

- [x] `ReservationController::store` branché sur `id_prix`/`statut` : le client n'envoie plus de `prix` libre — le montant est résolu depuis le `Prix` référencé par `id_prix` (nullable). Contrôle d'intégrité : le tarif doit appartenir au `id_site`/`id_evnmt` de la réservation (422 sinon). `montant > 0` → `statut=en_attente_paiement`, aucun `Ticket` créé ; `montant == 0` ou pas de tarif → `statut=confirmee`, `Ticket` généré immédiatement (comportement gratuit inchangé)
- [x] `PaiementController::reconcilierCommande` complété en conséquence : génère désormais les `Ticket` (idempotent) au moment où la `Commande` passe `payee`, puisque `ReservationController::store` ne les crée plus à la volée pour le payant — sans ce complément les réservations payées ne recevaient jamais de ticket
- [x] Vérifié en réel via de vraies requêtes HTTP (2026-09-15), pas de tinker pour le flux lui-même : `POST /register` (vrai utilisateur) → `POST /reservations` avec `id_prix` d'un tarif à 4000 → `statut=en_attente_paiement`, `tickets:[]`, montant résolu serveur (jamais celui envoyé par le client, qui n'est plus accepté) ; `POST /reservations` sans `id_prix` → `statut=confirmee`, tickets générés immédiatement (régression gratuite OK) ; tarif d'un autre site rejeté (422) ; connexion réelle dans le navigateur (Playwright) → bouton "Payer" visible sur la réservation payante → clic → widget Kkiapay sandbox ouvert directement par le bouton → paiement complété avec le numéro de test MTN → confirmation automatique côté serveur (`paiementsApi.verifier` déclenché par `addSuccessListener`) → réservation passée à "Confirmé" dans l'UI sans rechargement manuel → `Ticket` bien généré en base à ce moment précis (pas avant). Réservation non payée en parallèle : toujours 0 ticket. Toutes les données de test supprimées après vérification

- [x] Retry après paiement échoué (option retenue : libérer `id_commande`, pas de retenter-sur-place — évaluée contre l'option "retenter la même Commande", plus fidèle à l'échelonnement mais plus complexe pour un besoin non encore réel) : `PaiementController::marquerEchoue` remet `reservation.id_commande = null` pour toutes les réservations de la commande échouée, qui redeviennent immédiatement éligibles à `POST /commandes` (déjà filtré `whereNull('id_commande')`). Aucun changement frontend : le bouton "Payer" existant refonctionne tel quel. La commande/paiement échoués restent en base comme trace de la tentative ratée
- [x] Vérifié en réel via de vraies requêtes HTTP (2026-09-15) : réservation payante → commande 7 → paiement échoué (vrai appel Kkiapay sandbox, `TRANSACTION_NOT_FOUND`) → `id_commande` de la réservation repassé à `null`, commande 7 reste `echouee` en base → nouvelle `POST /commandes` acceptée immédiatement (commande 8) → paiement complété pour de vrai via le widget Kkiapay sandbox (numéro de test MTN) → `PATCH /paiements/8/verifier` confirmé (`SUCCESS`, montant exact) → réservation `confirmee`, ticket généré, commande 8 `payee`, commande 7 toujours `echouee` intacte comme historique. Données de test supprimées
- ⚠️ Limite assumée (discutée et actée avec l'utilisateur, non traitée) : pour une future commande échelonnée, l'échec d'une échéance intermédiaire libère *toutes* les réservations du groupe (perte du fractionnement en cours) plutôt que de ne retenter que l'échéance ratée. À revoir si l'échelonnement devient un flux réel

### Backend — reste à faire

- [ ] Tests automatisés — mis de côté volontairement, priorité aux modules Circuit et Billetterie avant de revenir dessus une fois le MVP fonctionnel complet

## Module Circuit (sans IA)

Démarré le 2026-09-16. Un Circuit est un itinéraire personnalisé construit manuellement par le touriste (`genere_par_ia` toujours `false` pour l'instant — le champ existe pour préparer le futur module IA, mais n'est jamais accepté depuis le client). Chaque étape référence un `Site` ou un `Evenement` (jamais les deux), peut être réservée immédiatement ou plus tard en réutilisant tel quel le flux `Reservation`/`Commande`/`Paiement` existant — aucune duplication de logique de réservation.

### Backend — schéma

- [x] État vérifié avant migration : aucune table/modèle/route `circuit` n'existait (27 tables en base, aucune mention "circuit" dans le code)
- [x] Table `circuit` (`libelle`, `description`, `id_user` FK `cascadeOnDelete`, `genere_par_ia` bool default false) — migration `2026_09_16_090000`
- [x] Table `etape_circuit` (`id_circuit` FK `cascadeOnDelete`, `id_site`/`id_evnmt` FK nullable `nullOnDelete`, `ordre` unsigned int, `id_reservation` FK nullable `nullOnDelete`) — migration `2026_09_16_090001`. Pas de contrainte unique SQL sur `(id_circuit, ordre)` : le tri est géré en application pour éviter les collisions transitoires pendant un réordonnancement
- [x] Modèles `Circuit` (relations `user`, `etapes` triées par `ordre`) et `EtapeCircuit` (relations `circuit`, `site`, `evenement`, `reservation`) ; `User` complété avec `circuits()`

### Backend — controllers & routes

- [x] `CircuitController` (`index`/`store`/`show`/`update`/`destroy`, `apiResource`, tout sous `auth:sanctum`, ownership par `id_user` — pas de volet admin, comme `Reservation`)
- [x] `EtapeCircuitController::store` (`POST /circuits/{circuit}/etapes`) : exige exactement un `id_site` OU un `id_evnmt` (422 sinon), `ordre` auto (max existant + 1) si omis
- [x] `EtapeCircuitController::update` (`PUT /etapes/{id}`) : sert à la fois l'ajustement d'`ordre` unitaire et la liaison d'une `Reservation` existante — vérifie que la réservation appartient à l'utilisateur (403 sinon) et référence le même site/événement que l'étape (422 sinon)
- [x] `EtapeCircuitController::destroy` : supprime uniquement le rattachement à l'étape, ne touche jamais à la `Reservation` liée (peut déjà être payée)
- [x] `EtapeCircuitController::reordonner` (`PATCH /circuits/{circuit}/etapes/reordonner`) : réécrit `ordre` en transaction depuis une liste d'id fournie, rejette (422) toute liste ne correspondant pas exactement aux étapes du circuit
- [x] Documentation Swagger (`OA\...`) ajoutée sur les deux controllers, régénérée sans erreur
- [x] Vérifié en réel via de vraies requêtes HTTP (2026-09-16), pas de tinker pour le flux lui-même : inscription réelle → création circuit (`genere_par_ia:true` envoyé par le client, forcé à `false` en réponse) → 3 étapes ajoutées (2 sites + 1 événement, ordre auto 1/2/3) → étape site+événement simultanés rejetée (422) → réordonnancement `[3,1,2]` appliqué et confirmé → liste de réordonnancement incomplète rejetée (422) → réservation classique créée via `POST /reservations` existant puis liée à l'étape via `PUT /etapes/{id}` (`reservation.statut` visible directement sous l'étape) → liaison d'une réservation au mauvais site rejetée (422) → isolation totale vérifiée avec un second utilisateur réel (403 sur show/store/update d'un circuit qui n'est pas le sien) → suppression d'étape puis du circuit entier : les réservations liées (`en_attente_paiement` et `confirmee`) restent intactes en base après coup. Toutes les données de test supprimées

### Frontend — reste à faire

- [ ] Interface carte du circuit (Leaflet + OpenStreetMap, choisi car gratuit et sans clé API — aucune contrainte identifiée ne l'exclut ; seule réserve : les tuiles publiques `tile.openstreetmap.org` ne sont pas taillées pour un fort trafic de production, à revoir seulement si le volume augmente) : affichage des étapes en marqueurs + tracé, ajout/retrait/réordonnancement (drag & drop à définir, pas encore choisi), affichage clair réservée/non réservée par étape. Pas commencé — traité juste après la Billetterie

## Module Billetterie (QR code)

Démarré le 2026-09-16. Décisions produit actées : QR généré côté frontend uniquement (`qrcode.react`, encode `ticket.numero`, aucune dépendance/colonne backend) ; pas de PDF téléchargeable pour l'instant ; scan côté staff via caméra (`html5-qrcode`) qui réutilise `POST /tickets/verifier` existant — **`verifier()` reste un contrôle pur, la création d'`Utilisation` reste une action explicite et séparée du staff, jamais un effet de bord du scan/de la vérification** (confirmé par relecture ligne à ligne avant toute modification, et vérifié en réel après coup).

### Backend

- [x] Aucun changement de schéma ni de controller nécessaire — tout existait déjà et était déjà correctement séparé : `TicketController::verifier` ne fait que `->first()`/`->exists()` (aucun `create()`/`update()`), `UtilisationController::store` reste le seul point de création, déjà protégé contre le double-usage (422 si déjà utilisé)

### Frontend

- [x] Dépendances ajoutées : `qrcode.react` (génération QR) et `html5-qrcode` (scan caméra + fallback "scanner une image"), toutes deux propres (aucune vulnérabilité, versions figées 4.2.0 et 2.3.8)
- [x] Bug bloquant préexistant corrigé dans `AdminTickets.jsx` : la page appelait `ticketsApi.verify()`/`ticketsApi.use()`, deux méthodes inexistantes dans `services.js` (seul `ticketsApi.verifier` existait), et affichait un champ `t.utilise` qui n'existe pas côté API — la page plantait dès qu'un membre du staff tentait de vérifier ou valider un ticket. Corrigé : `ticketsApi.verifier()` (bon nom), `utilisationsApi.create({id_ticket, date_visite, heure})` pour "Valider l'entrée", statut "utilisé" calculé via un helper `estUtilise()` à partir des vraies données (`utilisations.length > 0` ou `deja_utilise`)
- [x] Mode "Scanner un QR" ajouté sur la même page : bascule vers l'UI caméra de `html5-qrcode`, décode le QR puis réutilise exactement le même chemin (`rechercherNumero`) qu'une saisie manuelle — aucun nouvel appel réseau, aucune logique dupliquée
- [x] Affichage du billet électronique côté touriste dans `MesReservations.jsx` (bouton "Mon billet" → modale avec QR + numero par ticket, à partir des données déjà chargées, zéro appel API supplémentaire)
- [x] Vérifié en réel (Playwright, 2026-09-16), conditions réelles avec un vrai ticket généré via le flux normal (réservation gratuite → ticket immédiat) :
  - Côté touriste : QR affiché correctement dans la modale "Mon billet"
  - Côté admin, saisie manuelle : vérification affiche "Valide" sans crash (bug confirmé disparu) → clic "Valider l'entrée" → toast succès → statut passe à "Utilisé" dans la liste et en re-vérification → une seule `Utilisation` créée en base avec la bonne date/heure, aucun doublon
  - Côté admin, scan : le mode caméra s'ouvre proprement (0 erreur console) ; caméra physique non simulable dans cet environnement (pas de contrôle des flags de lancement Chromium pour un fake device), donc testé via le fallback "Scan an Image File" de la même librairie avec une **vraie image PNG du QR** généré par le composant `qrcode.react` côté touriste (capturée dans l'app, pas une image externe) : décodage correct de `TCK-BQ8FC5IT`, fermeture auto du scanner, résultat "Utilisé" affiché — même moteur de décodage que la caméra, donc preuve solide du pipeline scan → vérification
  - Toutes les données de test supprimées après vérification
- ⚠️ Découverte annexe, non corrigée (hors périmètre) : `npm audit` signale des vulnérabilités (dont une critique sur `swiper`) sur des dépendances **préexistantes** (axios, react-router-dom, vite, swiper...), aucune liée aux deux nouvelles libs. À traiter séparément, pas introduit par ce lot
