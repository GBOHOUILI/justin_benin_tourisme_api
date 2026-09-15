# Roadmap

> Mis à jour à chaque tâche terminée. Ne pas cocher une case sans l'avoir vérifiée (route testée, test lancé, etc.).

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
- [ ] Bug trouvé en marge (non corrigé, hors périmètre de la tâche en cours) : `UtilisationController` valide avec `exists:tickets,id` (table inexistante, la vraie table est `ticket` singulier) → 500 systématique sur `POST /admin/utilisations`. Même classe de bug que celui déjà corrigé sur `AvisController`

### Backend — Prestataire (fiche minimale)

- [x] Table `prestataire` créée (`nom_entreprise`, `type_prestataire`, `status`) — pas de compte/flux d'inscription, conforme au périmètre minimal validé
- [x] FK `id_prestataire` (nullable, `nullOnDelete`) ajoutée sur `site` et `evenement`, relations `prestataire()`/`sites()`/`evenements()` sur les modèles — vérifié via tinker (relation chargée dans les deux sens)
- [x] Commande CLI `php artisan prestataire:create` pour peupler la table en attendant le module Prestataire complet (inscription, compte, dashboard) — testée en réel
- [x] `type_prestataire` contraint à un enum PHP natif `App\Enums\TypePrestataire` (site, evenement, hotel, restaurant, transport) — casté dans `Prestataire::$casts`, validé dans `prestataire:create` (rejet + message clair sur valeur hors enum, testé en réel). Colonne DB reste `string` (pas de contrainte au niveau SQL, uniquement applicative — pas demandé). Audit des lignes existantes hors enum fait via requête `DB::table()` brute (pas via Eloquent, qui lèverait une `ValueError` sur une valeur invalide à la lecture) : table vide, 0 ligne à traiter
- [ ] Pas de flux d'inscription/compte Prestataire — reporté au module Prestataire (hors périmètre Tourisme, décision déjà actée)
- [ ] Pas de sélecteur `id_prestataire` dans les formulaires admin `AdminSites.jsx`/`AdminEvenements.jsx` côté frontend — à ajouter si/quand le rattachement prestataire devient un besoin réel pendant la phase Tourisme (pas fait maintenant, non demandé)

### Backend — fonctionnel

- [ ] Recherche par proximité (géolocalisation) sur `GET /sites` et `GET /evenements` — actuellement listing brut sans filtre géographique
- [ ] Recherche par budget (fourchette de prix) sur `GET /sites` et `GET /evenements`
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
- [ ] Vérification navigateur réelle non faite (pas d'outil browser disponible dans cette session) — seulement `npm run build` (compile OK) + vérification du contrat JSON via API réelle. À confirmer visuellement à l'usage

### Frontend — flux manquants

- [ ] Formulaire de dépôt d'avis côté public : aucun appel à `avisApi.create` dans `src/pages/public` — bloqué par le workflow Avis backend à clarifier
- [ ] UI de recherche par proximité/budget — en attente des filtres backend correspondants
- [ ] Affichage QR code / ticket électronique — en attente de la fonctionnalité backend

### Frontend — à vérifier

- [ ] `Profil.jsx` (`usersApi.update(user.id, ...)`) après le fix IDOR backend — l'ID utilisé est bien celui de l'utilisateur connecté, a priori sans impact, à confirmer par un test manuel de mise à jour de profil
