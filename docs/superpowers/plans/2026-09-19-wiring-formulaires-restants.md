# Wiring des composants partagés dans les 14 formulaires restants — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Câbler `LocationPicker`/`TagListInput`/`ItineraryEditor` (livrés par le Plan 2) et les nouveaux champs de contenu enrichi (livrés côté backend par le Plan 1) dans les 14 formulaires Admin/Prestataire/Responsable restants pour Evenement/Hotel/Restaurant/Transport, plus les 2 formulaires Site (Prestataire/Responsable) et un rattrapage sur `AdminSites.jsx` (2 champs backend jamais exposés : `infos_pratiques`/`recommandations`).

**Architecture:** Aucun composant nouveau, aucun changement backend/service (confirmé par audit : `services.js` fait du passthrough pur, sans whitelist client). Pur câblage frontend, répété 5 fois (une fois par type d'entité) sur 2-3 variantes de portail quasi identiques. Une tâche par type d'entité — chaque tâche dispatche UN seul implémenteur sur 2-3 fichiers de même forme (batch, cf. `subagent-driven-development` "Batch small same-shape work").

**Tech Stack:** React 18, composants existants (`src/components/map/LocationPicker.jsx`, `src/components/forms/TagListInput.jsx`, `src/components/forms/ItineraryEditor.jsx`). Pas de nouvelle dépendance.

**Spec:** `docs/superpowers/specs/2026-09-18-fiches-detail-enrichies-design.md` (Chantier 1). Référence directe : `docs/superpowers/plans/2026-09-19-composants-partages-frontend.md` (Plan 2 — a livré les 3 composants + `src/pages/admin/AdminSites.jsx` comme preuve d'intégration, le gabarit exact à reproduire).

## Global Constraints

- **Composants (déjà implémentés dans `totche-front`, NE PAS MODIFIER leur code interne) :**
  - `LocationPicker({ latitude, longitude, onChange })` — `onChange` reçoit `{ latitude, longitude }` (nombres arrondis à 7 décimales si origine carte/lien Google Maps, ou valeur brute de l'input si saisie manuelle). Rend toujours ses 2 champs `required` en interne. Remplace intégralement le bloc `<div className="admin-form__row">` contenant les 2 `<input type="number">` latitude/longitude existants (le composant n'est PAS lui-même enveloppé dans `admin-form__row`/`admin-form__field` — il gère son propre layout, comme dans `AdminSites.jsx`).
  - `TagListInput({ label, value, onChange, placeholder })` — `value: string[]`, `onChange(nextArray)`.
  - `ItineraryEditor({ value, onChange, label? })` — `value: {titre, description}[]`, `onChange(nextArray)`. Le formulaire appelant DOIT filtrer les étapes incomplètes avant l'envoi (le composant ne le fait pas lui-même) : `form.itineraire.filter(s => s.titre?.trim() && s.description?.trim())` — sinon 422 du backend (`itineraire.*.titre`/`description` sont `required_with:itineraire`).
- **Imports à ajouter** (chemin identique dans les 3 dossiers `src/pages/{admin,prestataire,responsable}/`, même profondeur) :
  ```js
  import LocationPicker from '../../components/map/LocationPicker'
  import TagListInput from '../../components/forms/TagListInput'
  import ItineraryEditor from '../../components/forms/ItineraryEditor' // Evenement uniquement
  ```
- **Filtrage des tableaux de tags avant envoi** (même pattern que `AdminSites.jsx`) : `champ: form.champ.filter(v => v.trim())`.
- **Correctif "zéro exact" obligatoire** partout où le bloc latitude/longitude brut est remplacé par `LocationPicker` : ne JAMAIS utiliser `form.latitude ? parseFloat(...) : undefined` (un `0` exact est falsy en JS, donc perdu) — utiliser systématiquement :
  ```js
  latitude: form.latitude !== '' && form.latitude != null ? parseFloat(form.latitude) : undefined,
  longitude: form.longitude !== '' && form.longitude != null ? parseFloat(form.longitude) : undefined,
  ```
  (`PrestataireSites.jsx`/`ResponsableSites.jsx` ont encore l'ancien pattern buggé — Task 1 le corrige au passage.)
- **Champs texte libres `infos_pratiques`/`recommandations`** : identiques sur les 5 entités, toujours `<textarea>` simple (PAS un tableau, PAS de `TagListInput`) :
  ```jsx
  <div className="admin-form__field"><label>Infos pratiques</label>
    <textarea rows={3} value={form.infos_pratiques} onChange={e => setForm(f => ({ ...f, infos_pratiques: e.target.value }))} placeholder="Ex: Prévoir de l'eau, chaussures fermées recommandées" /></div>
  <div className="admin-form__field"><label>Recommandations</label>
    <textarea rows={3} value={form.recommandations} onChange={e => setForm(f => ({ ...f, recommandations: e.target.value }))} placeholder="Ex: Meilleure période : novembre à février" /></div>
  ```
  Payload : `infos_pratiques: form.infos_pratiques || undefined, recommandations: form.recommandations || undefined,` (backend : `nullable|string`, pas de contrainte de longueur).
- **3× `TagListInput` communs à toutes les entités** (labels génériques — `AdminSites.jsx` utilise "Ce que le billet inclut", mais "billet" ne fait sens que pour Site/Evenement ; pour Hotel/Restaurant/Transport utiliser le libellé générique ci-dessous) :
  ```jsx
  <TagListInput
    label="Points forts"
    value={form.points_forts}
    onChange={v => setForm(f => ({ ...f, points_forts: v }))}
    placeholder="Ex: Vue imprenable sur la lagune"
  />
  <TagListInput
    label="Ce qui est inclus"
    value={form.inclus}
    onChange={v => setForm(f => ({ ...f, inclus: v }))}
    placeholder="Ex: Accès au site, parking"
  />
  <TagListInput
    label="Non inclus"
    value={form.non_inclus}
    onChange={v => setForm(f => ({ ...f, non_inclus: v }))}
    placeholder="Ex: Guide privé, transport"
  />
  ```
  (Pour Site : garder le libellé existant "Ce que le billet inclut" tel quel, ne pas le renommer — seul Task 1 sur `PrestataireSites.jsx`/`ResponsableSites.jsx` doit répliquer EXACTEMENT les labels déjà utilisés dans `AdminSites.jsx`, pas les labels génériques ci-dessus.)
- **Ordre d'insertion dans le JSX** (constant, à respecter dans toutes les tâches) : bloc Nom/Adresse (intact) → catégorie/région (intact) → `LocationPicker` (remplace lat/lng) → champs date/heure déjà existants propres à l'entité (intacts) → `status` select (Admin) / paragraphe disclaimer (Prestataire/Responsable) — intact, ne pas déplacer → 3× `TagListInput` → `ItineraryEditor` (Evenement seulement) → nouveaux champs spécifiques à l'entité (voir chaque tâche) → textarea `infos_pratiques` → textarea `recommandations` → textarea `description` (déjà existante, intacte, reste en dernier avant le footer) → footer (intact).
- **Ne jamais toucher** : `status`/disclaimer, le pattern région `estGlobal` (Responsable), les modals secondaires (Galerie/Chambres/Plats/Trajets/Tarifs) et leurs handlers, `handleValider`/`handleRejeter`/`handleDelete`, les noms des fonctions `xxxApi.create*/update*` (seul leur payload change).
- **Vérification obligatoire par tâche** (règle du `CLAUDE.md` du repo API, section "Vérification frontend") : `npm run build` doit passer sans erreur, PUIS vérification Playwright réelle sur AU MOINS un portail de la tâche (snapshot avant/après, remplissage des nouveaux champs y compris un clic sur la carte OU un lien Google Maps collé dans `LocationPicker`, soumission, réouverture en édition pour confirmer la persistance des nouvelles valeurs, console sans erreur JS). Le simple succès de `npm run build` ne suffit jamais.
- **1 commit minimum par tâche**, message `feat(contenu-enrichi): câble <composants> dans <Entité> (<portails>)`.

---

### Task 1: Site — rattrapage AdminSites.jsx + wiring Prestataire/Responsable

**Files:**
- Modify: `src/pages/admin/AdminSites.jsx` (rattrapage seul : ajoute `infos_pratiques`/`recommandations`, rien d'autre)
- Modify: `src/pages/prestataire/PrestataireSites.jsx` (wiring complet)
- Modify: `src/pages/responsable/ResponsableSites.jsx` (wiring complet, identique à Prestataire)

**Interfaces:**
- Consumes: `LocationPicker`, `TagListInput` (voir Global Constraints) — pas d'`ItineraryEditor` (Site n'a pas d'itinéraire).
- Produces: rien consommé par une tâche ultérieure (page terminale).

- [ ] **Step 1: Rattraper `AdminSites.jsx` (2 champs manquants)**

  `AdminSites.jsx` a déjà été câblé au Plan 2 pour `points_forts`/`inclus`/`non_inclus`/`duree_visite`/`difficulte`, mais **jamais** pour `infos_pratiques`/`recommandations` alors que le backend les valide pour Site (`SiteController::store`, `infos_pratiques`/`recommandations` en `nullable|string`) — un oubli du Plan 2 à corriger ici.

  Dans `emptyForm` (ligne 9-14), ajouter après `difficulte: '',` :
  ```js
  infos_pratiques: '', recommandations: '',
  ```
  Dans `openEdit` (ligne 46-56), ajouter après `difficulte: site.difficulte || '',` :
  ```js
  infos_pratiques: site.infos_pratiques || '', recommandations: site.recommandations || '',
  ```
  Dans `handleSubmit` (ligne 60-71), ajouter après `difficulte: form.difficulte || undefined,` :
  ```js
  infos_pratiques: form.infos_pratiques || undefined,
  recommandations: form.recommandations || undefined,
  ```
  Dans le JSX, insérer les 2 textareas (bloc exact donné dans Global Constraints) juste après le bloc `admin-form__row` "Durée de visite"/"Difficulté" (ligne 210-220) et avant le champ `Description` (ligne 221-222).

- [ ] **Step 2: Build**

  Run: `npm run build` — attendu : 0 erreur.

- [ ] **Step 3: Commit du rattrapage**

  ```bash
  git add src/pages/admin/AdminSites.jsx
  git commit -m "feat(contenu-enrichi): ajoute infos_pratiques/recommandations à AdminSites.jsx (oubli Plan 2)"
  ```

- [ ] **Step 4: Wirer `PrestataireSites.jsx` et `ResponsableSites.jsx`**

  Les deux fichiers sont structurellement identiques (import `prestatairesApi`/`responsablesApi` respectivement ; `ResponsableSites.jsx` a en plus le pattern région `estGlobal` — ne pas y toucher).

  Ajouter les imports (après la ligne `import toast from 'react-hot-toast'`) :
  ```js
  import LocationPicker from '../../components/map/LocationPicker'
  import TagListInput from '../../components/forms/TagListInput'
  ```

  `emptyForm` actuel (identique dans les 2 fichiers) :
  ```js
  const emptyForm = {
    libelle: '', adresse: '', description: '',
    id_cat_site: '', latitude: '', longitude: '',
    ouverture: '', fermeture: '', id_region: '',
  }
  ```
  Remplacer par :
  ```js
  const emptyForm = {
    libelle: '', adresse: '', description: '',
    id_cat_site: '', latitude: '', longitude: '',
    ouverture: '', fermeture: '', id_region: '',
    points_forts: [], inclus: [], non_inclus: [], duree_visite: '', difficulte: '',
    infos_pratiques: '', recommandations: '',
  }
  ```

  `openEdit` actuel (identique dans les 2 fichiers) :
  ```js
  const openEdit = (site) => {
    setForm({
      libelle: site.libelle || '', adresse: site.adresse || '',
      description: site.description || '', id_cat_site: site.id_cat_site || '',
      latitude: site.latitude || '', longitude: site.longitude || '',
      ouverture: site.ouverture || '', fermeture: site.fermeture || '',
      id_region: site.id_region || '',
    })
    setModal(site)
  }
  ```
  Remplacer par :
  ```js
  const openEdit = (site) => {
    setForm({
      libelle: site.libelle || '', adresse: site.adresse || '',
      description: site.description || '', id_cat_site: site.id_cat_site || '',
      latitude: site.latitude || '', longitude: site.longitude || '',
      ouverture: site.ouverture || '', fermeture: site.fermeture || '',
      id_region: site.id_region || '',
      points_forts: site.points_forts || [], inclus: site.inclus || [], non_inclus: site.non_inclus || [],
      duree_visite: site.duree_visite || '', difficulte: site.difficulte || '',
      infos_pratiques: site.infos_pratiques || '', recommandations: site.recommandations || '',
    })
    setModal(site)
  }
  ```

  `handleSubmit` actuel (identique dans les 2 fichiers, seul le nom d'API diffère — `prestatairesApi.createSite/updateSite` vs `responsablesApi.createSite/updateSite`, NE PAS toucher ces appels) :
  ```js
  const payload = {
    ...form,
    latitude: form.latitude ? parseFloat(form.latitude) : undefined,
    longitude: form.longitude ? parseFloat(form.longitude) : undefined,
    id_cat_site: form.id_cat_site ? parseInt(form.id_cat_site) : undefined,
    id_region: form.id_region ? parseInt(form.id_region) : undefined,
  }
  ```
  Remplacer par :
  ```js
  const payload = {
    ...form,
    latitude: form.latitude !== '' && form.latitude != null ? parseFloat(form.latitude) : undefined,
    longitude: form.longitude !== '' && form.longitude != null ? parseFloat(form.longitude) : undefined,
    id_cat_site: form.id_cat_site ? parseInt(form.id_cat_site) : undefined,
    id_region: form.id_region ? parseInt(form.id_region) : undefined,
    points_forts: form.points_forts.filter(v => v.trim()),
    inclus: form.inclus.filter(v => v.trim()),
    non_inclus: form.non_inclus.filter(v => v.trim()),
    duree_visite: form.duree_visite || undefined,
    difficulte: form.difficulte || undefined,
    infos_pratiques: form.infos_pratiques || undefined,
    recommandations: form.recommandations || undefined,
  }
  ```

  JSX — remplacer le bloc latitude/longitude brut :
  ```jsx
  <div className="admin-form__row">
    <div className="admin-form__field"><label>Latitude *</label>
      <input type="number" step="any" value={form.latitude} onChange={e => setForm(f => ({ ...f, latitude: e.target.value }))} required /></div>
    <div className="admin-form__field"><label>Longitude *</label>
      <input type="number" step="any" value={form.longitude} onChange={e => setForm(f => ({ ...f, longitude: e.target.value }))} required /></div>
  </div>
  ```
  par :
  ```jsx
  <LocationPicker
    latitude={form.latitude}
    longitude={form.longitude}
    onChange={({ latitude, longitude }) => setForm(f => ({ ...f, latitude, longitude }))}
  />
  ```

  Puis, juste après le bloc `admin-form__row` "Ouverture"/"Fermeture" et avant le champ `Description`, insérer dans cet ordre exact (copié verbatim depuis `AdminSites.jsx` lignes 192-222, mêmes labels "Ce que le billet inclut" à conserver ici, PAS le libellé générique des Global Constraints) :
  ```jsx
  <TagListInput
    label="Points forts"
    value={form.points_forts}
    onChange={v => setForm(f => ({ ...f, points_forts: v }))}
    placeholder="Ex: Vue imprenable sur la lagune"
  />
  <TagListInput
    label="Ce que le billet inclut"
    value={form.inclus}
    onChange={v => setForm(f => ({ ...f, inclus: v }))}
    placeholder="Ex: Accès au site, parking"
  />
  <TagListInput
    label="Non inclus"
    value={form.non_inclus}
    onChange={v => setForm(f => ({ ...f, non_inclus: v }))}
    placeholder="Ex: Guide privé, transport"
  />
  <div className="admin-form__row">
    <div className="admin-form__field"><label>Durée de visite</label>
      <input type="text" value={form.duree_visite} onChange={e => setForm(f => ({ ...f, duree_visite: e.target.value }))} placeholder="Ex: 2h, Demi-journée" /></div>
    <div className="admin-form__field"><label>Difficulté</label>
      <select value={form.difficulte} onChange={e => setForm(f => ({ ...f, difficulte: e.target.value }))}>
        <option value="">Non précisée</option>
        <option value="facile">Facile</option>
        <option value="moderee">Modérée</option>
        <option value="difficile">Difficile</option>
      </select></div>
  </div>
  <div className="admin-form__field"><label>Infos pratiques</label>
    <textarea rows={3} value={form.infos_pratiques} onChange={e => setForm(f => ({ ...f, infos_pratiques: e.target.value }))} placeholder="Ex: Prévoir de l'eau, chaussures fermées recommandées" /></div>
  <div className="admin-form__field"><label>Recommandations</label>
    <textarea rows={3} value={form.recommandations} onChange={e => setForm(f => ({ ...f, recommandations: e.target.value }))} placeholder="Ex: Meilleure période : novembre à février" /></div>
  ```

- [ ] **Step 5: Build**

  Run: `npm run build` — attendu : 0 erreur.

- [ ] **Step 6: Vérification Playwright réelle**

  Se connecter en Prestataire (créer un compte de test via le flux d'inscription public si aucun identifiant documenté n'existe dans `ROADMAP.md`/`CLAUDE.md`), naviguer vers "Mes Sites", ouvrir "Ajouter", vérifier : la carte s'affiche, un clic sur la carte remplit latitude/longitude, les 3 `TagListInput` acceptent l'ajout/suppression de tags, soumettre, rouvrir en édition et confirmer que toutes les valeurs (y compris `infos_pratiques`/`recommandations`) sont bien réaffichées. Capturer la console (aucune erreur JS).

- [ ] **Step 7: Commit**

  ```bash
  git add src/pages/prestataire/PrestataireSites.jsx src/pages/responsable/ResponsableSites.jsx
  git commit -m "feat(contenu-enrichi): câble LocationPicker/TagListInput dans Site (Prestataire/Responsable)"
  ```

---

### Task 2: Evenement — Admin/Prestataire/Responsable

**Files:**
- Modify: `src/pages/admin/AdminEvenements.jsx`
- Modify: `src/pages/prestataire/PrestataireEvenements.jsx`
- Modify: `src/pages/responsable/ResponsableEvenements.jsx`

**Interfaces:**
- Consumes: `LocationPicker`, `TagListInput`, `ItineraryEditor`.
- Produces: rien.

Les 3 fichiers partagent la même forme (`emptyForm = { libelle, adresse, description, id_cat_evenmt, date_debut, date_fin, latitude, longitude, id_region }`, `+ status: 'en_attente'` pour Admin seulement). Aucun des 3 n'a encore de champ de contenu enrichi.

**Champs backend disponibles pour Evenement** (validation `EvenementController::store`) : `points_forts`/`inclus`/`non_inclus` (arrays), `infos_pratiques`/`recommandations` (text), `itineraire` (array de `{titre, description}`), `groupe_min` (`nullable|integer|min:1|max:255`), `groupe_max` (`nullable|integer|min:1|max:255|gte:groupe_min`), `langue` (`nullable|string|max:100`), `difficulte` (`nullable|in:facile,moderee,difficile` — **champ séparé et distinct de celui de Site**, même valeurs possibles).

- [ ] **Step 1: Imports** (dans les 3 fichiers, après `import toast from 'react-hot-toast'`)

  ```js
  import LocationPicker from '../../components/map/LocationPicker'
  import TagListInput from '../../components/forms/TagListInput'
  import ItineraryEditor from '../../components/forms/ItineraryEditor'
  ```

- [ ] **Step 2: `emptyForm`**

  Ajouter à la fin de l'objet existant (avant la accolade fermante, après `id_region: ''` — garder `status: 'en_attente'` pour Admin, absent pour les 2 autres) :
  ```js
  points_forts: [], inclus: [], non_inclus: [],
  infos_pratiques: '', recommandations: '', itineraire: [],
  groupe_min: '', groupe_max: '', langue: '', difficulte: '',
  ```

- [ ] **Step 3: `openEdit`**

  Ajouter au mapping existant (garder intact tout ce qui existe déjà — `date_debut`/`date_fin` gardent leur `.split('T')[0]`) :
  ```js
  points_forts: evt.points_forts || [], inclus: evt.inclus || [], non_inclus: evt.non_inclus || [],
  infos_pratiques: evt.infos_pratiques || '', recommandations: evt.recommandations || '',
  itineraire: evt.itineraire || [],
  groupe_min: evt.groupe_min ?? '', groupe_max: evt.groupe_max ?? '',
  langue: evt.langue || '', difficulte: evt.difficulte || '',
  ```
  (`??` et non `||` pour `groupe_min`/`groupe_max` : `0` n'est pas une valeur métier valide ici — `min:1` — donc `||` serait sans risque réel, mais `??` est plus correct sémantiquement et cohérent avec le reste du plan.)

- [ ] **Step 4: `handleSubmit`**

  Remplacer les 2 lignes lat/lng existantes (`form.latitude ? parseFloat(...) : undefined`) par le pattern zéro-safe (Global Constraints), et ajouter :
  ```js
  points_forts: form.points_forts.filter(v => v.trim()),
  inclus: form.inclus.filter(v => v.trim()),
  non_inclus: form.non_inclus.filter(v => v.trim()),
  infos_pratiques: form.infos_pratiques || undefined,
  recommandations: form.recommandations || undefined,
  itineraire: form.itineraire.filter(s => s.titre?.trim() && s.description?.trim()),
  groupe_min: form.groupe_min !== '' ? parseInt(form.groupe_min) : undefined,
  groupe_max: form.groupe_max !== '' ? parseInt(form.groupe_max) : undefined,
  langue: form.langue || undefined,
  difficulte: form.difficulte || undefined,
  ```

- [ ] **Step 5: JSX**

  Remplacer le bloc `admin-form__row` latitude/longitude par `<LocationPicker latitude={form.latitude} longitude={form.longitude} onChange={({ latitude, longitude }) => setForm(f => ({ ...f, latitude, longitude }))} />` (position inchangée : juste après la ligne Date début/Date fin).

  Juste après le champ `status`/disclaimer (Admin a un `<select>` Statut ; Prestataire/Responsable ont un `<p>` disclaimer — laisser cette différence intacte, insérer APRÈS elle dans les 2 cas) et avant `Description`, insérer dans cet ordre :
  ```jsx
  <TagListInput
    label="Points forts"
    value={form.points_forts}
    onChange={v => setForm(f => ({ ...f, points_forts: v }))}
    placeholder="Ex: Ambiance festive, artisanat local"
  />
  <TagListInput
    label="Ce qui est inclus"
    value={form.inclus}
    onChange={v => setForm(f => ({ ...f, inclus: v }))}
    placeholder="Ex: Accès à l'événement, animation"
  />
  <TagListInput
    label="Non inclus"
    value={form.non_inclus}
    onChange={v => setForm(f => ({ ...f, non_inclus: v }))}
    placeholder="Ex: Transport, hébergement"
  />
  <ItineraryEditor
    value={form.itineraire}
    onChange={v => setForm(f => ({ ...f, itineraire: v }))}
  />
  <div className="admin-form__row">
    <div className="admin-form__field"><label>Groupe min.</label>
      <input type="number" min="1" value={form.groupe_min} onChange={e => setForm(f => ({ ...f, groupe_min: e.target.value }))} /></div>
    <div className="admin-form__field"><label>Groupe max.</label>
      <input type="number" min="1" value={form.groupe_max} onChange={e => setForm(f => ({ ...f, groupe_max: e.target.value }))} /></div>
  </div>
  <div className="admin-form__row">
    <div className="admin-form__field"><label>Langue</label>
      <input type="text" value={form.langue} onChange={e => setForm(f => ({ ...f, langue: e.target.value }))} placeholder="Ex: Français, Fon" /></div>
    <div className="admin-form__field"><label>Difficulté</label>
      <select value={form.difficulte} onChange={e => setForm(f => ({ ...f, difficulte: e.target.value }))}>
        <option value="">Non précisée</option>
        <option value="facile">Facile</option>
        <option value="moderee">Modérée</option>
        <option value="difficile">Difficile</option>
      </select></div>
  </div>
  <div className="admin-form__field"><label>Infos pratiques</label>
    <textarea rows={3} value={form.infos_pratiques} onChange={e => setForm(f => ({ ...f, infos_pratiques: e.target.value }))} placeholder="Ex: Prévoir de l'eau, chaussures fermées recommandées" /></div>
  <div className="admin-form__field"><label>Recommandations</label>
    <textarea rows={3} value={form.recommandations} onChange={e => setForm(f => ({ ...f, recommandations: e.target.value }))} placeholder="Ex: Meilleure période : novembre à février" /></div>
  ```

  Note pour `AdminEvenements.jsx` uniquement : ce fichier a actuellement les inputs latitude/longitude SANS `required` (contrairement aux 13 autres fichiers) — bug préexistant, alors que le backend exige `required|numeric`. En remplaçant par `LocationPicker` (qui rend toujours `required`), ce bug est corrigé automatiquement — ne pas ajouter de logique supplémentaire, `LocationPicker` suffit.

- [ ] **Step 6: Build**

  Run: `npm run build` — attendu : 0 erreur.

- [ ] **Step 7: Vérification Playwright réelle**

  Sur le portail Admin (identifiants déjà utilisés dans les sessions précédentes de ce projet) : créer un événement avec au moins 2 étapes d'itinéraire (une complète, une incomplète pour vérifier le filtrage silencieux au submit), des tags dans les 3 `TagListInput`, `groupe_min`/`groupe_max` cohérents, `difficulte`. Soumettre, rouvrir en édition, confirmer que l'étape incomplète a bien été supprimée du payload envoyé (elle ne doit pas réapparaître avec un contenu vide en base) et que toutes les autres valeurs persistent. Console sans erreur JS.

- [ ] **Step 8: Commit**

  ```bash
  git add src/pages/admin/AdminEvenements.jsx src/pages/prestataire/PrestataireEvenements.jsx src/pages/responsable/ResponsableEvenements.jsx
  git commit -m "feat(contenu-enrichi): câble LocationPicker/TagListInput/ItineraryEditor dans Evenement (Admin/Prestataire/Responsable)"
  ```

---

### Task 3: Hotel — Admin/Prestataire/Responsable

**Files:**
- Modify: `src/pages/admin/AdminHotels.jsx`
- Modify: `src/pages/prestataire/PrestataireHotels.jsx`
- Modify: `src/pages/responsable/ResponsableHotels.jsx`

**Interfaces:**
- Consumes: `LocationPicker`, `TagListInput` (pas d'`ItineraryEditor` — Hotel n'a pas d'équivalent itinéraire).
- Produces: rien.

`nombre_etoiles` (select 1-5) existe déjà et fonctionne — ne pas y toucher. **Champs backend disponibles** (`HotelController::store`) : `points_forts`/`inclus`/`non_inclus`, `infos_pratiques`/`recommandations`, `heure_arrivee`/`heure_depart` (`nullable|date_format:H:i`).

- [ ] **Step 1: Imports** (identique Task 2, sans `ItineraryEditor`)

  ```js
  import LocationPicker from '../../components/map/LocationPicker'
  import TagListInput from '../../components/forms/TagListInput'
  ```

- [ ] **Step 2: `emptyForm`** — ajouter :
  ```js
  points_forts: [], inclus: [], non_inclus: [],
  infos_pratiques: '', recommandations: '', heure_arrivee: '', heure_depart: '',
  ```

- [ ] **Step 3: `openEdit`** — ajouter :
  ```js
  points_forts: hotel.points_forts || [], inclus: hotel.inclus || [], non_inclus: hotel.non_inclus || [],
  infos_pratiques: hotel.infos_pratiques || '', recommandations: hotel.recommandations || '',
  heure_arrivee: hotel.heure_arrivee || '', heure_depart: hotel.heure_depart || '',
  ```

- [ ] **Step 4: `handleSubmit`** — zéro-safe lat/lng (Global Constraints) + ajouter :
  ```js
  points_forts: form.points_forts.filter(v => v.trim()),
  inclus: form.inclus.filter(v => v.trim()),
  non_inclus: form.non_inclus.filter(v => v.trim()),
  infos_pratiques: form.infos_pratiques || undefined,
  recommandations: form.recommandations || undefined,
  heure_arrivee: form.heure_arrivee || undefined,
  heure_depart: form.heure_depart || undefined,
  ```

- [ ] **Step 5: JSX**

  Remplacer le bloc lat/lng par `<LocationPicker .../>` (même pattern). Juste après le champ `status`/disclaimer et avant `Description`, insérer :
  ```jsx
  <TagListInput
    label="Points forts"
    value={form.points_forts}
    onChange={v => setForm(f => ({ ...f, points_forts: v }))}
    placeholder="Ex: Piscine, vue sur mer"
  />
  <TagListInput
    label="Ce qui est inclus"
    value={form.inclus}
    onChange={v => setForm(f => ({ ...f, inclus: v }))}
    placeholder="Ex: Petit-déjeuner, Wi-Fi"
  />
  <TagListInput
    label="Non inclus"
    value={form.non_inclus}
    onChange={v => setForm(f => ({ ...f, non_inclus: v }))}
    placeholder="Ex: Navette aéroport, spa"
  />
  <div className="admin-form__row">
    <div className="admin-form__field"><label>Heure d'arrivée</label>
      <input type="time" value={form.heure_arrivee} onChange={e => setForm(f => ({ ...f, heure_arrivee: e.target.value }))} /></div>
    <div className="admin-form__field"><label>Heure de départ</label>
      <input type="time" value={form.heure_depart} onChange={e => setForm(f => ({ ...f, heure_depart: e.target.value }))} /></div>
  </div>
  <div className="admin-form__field"><label>Infos pratiques</label>
    <textarea rows={3} value={form.infos_pratiques} onChange={e => setForm(f => ({ ...f, infos_pratiques: e.target.value }))} placeholder="Ex: Parking gratuit sur place" /></div>
  <div className="admin-form__field"><label>Recommandations</label>
    <textarea rows={3} value={form.recommandations} onChange={e => setForm(f => ({ ...f, recommandations: e.target.value }))} placeholder="Ex: Réserver 48h à l'avance en haute saison" /></div>
  ```

- [ ] **Step 6: Build**

  Run: `npm run build` — attendu : 0 erreur.

- [ ] **Step 7: Vérification Playwright réelle**

  Portail Admin : créer un hôtel, remplir carte + 3 tags + heures d'arrivée/départ + infos pratiques/recommandations, soumettre, rouvrir en édition, confirmer persistance. Console sans erreur JS.

- [ ] **Step 8: Commit**

  ```bash
  git add src/pages/admin/AdminHotels.jsx src/pages/prestataire/PrestataireHotels.jsx src/pages/responsable/ResponsableHotels.jsx
  git commit -m "feat(contenu-enrichi): câble LocationPicker/TagListInput dans Hotel (Admin/Prestataire/Responsable)"
  ```

---

### Task 4: Restaurant — Admin/Prestataire/Responsable

**Files:**
- Modify: `src/pages/admin/AdminRestaurants.jsx`
- Modify: `src/pages/prestataire/PrestataireRestaurants.jsx`
- Modify: `src/pages/responsable/ResponsableRestaurants.jsx`

**Interfaces:**
- Consumes: `LocationPicker`, `TagListInput`.
- Produces: rien.

`type_cuisine`/`gamme_prix` existent déjà et fonctionnent — ne pas y toucher. **Champs backend disponibles** (`RestaurantController::store`) : `points_forts`/`inclus`/`non_inclus`, `infos_pratiques`/`recommandations`, `horaires` (`nullable|string|max:255` — texte libre, PAS un time picker : ex. "Lun-Ven 11h-22h").

- [ ] **Step 1: Imports**
  ```js
  import LocationPicker from '../../components/map/LocationPicker'
  import TagListInput from '../../components/forms/TagListInput'
  ```

- [ ] **Step 2: `emptyForm`** — ajouter :
  ```js
  points_forts: [], inclus: [], non_inclus: [],
  infos_pratiques: '', recommandations: '', horaires: '',
  ```

- [ ] **Step 3: `openEdit`** — ajouter :
  ```js
  points_forts: restaurant.points_forts || [], inclus: restaurant.inclus || [], non_inclus: restaurant.non_inclus || [],
  infos_pratiques: restaurant.infos_pratiques || '', recommandations: restaurant.recommandations || '',
  horaires: restaurant.horaires || '',
  ```

- [ ] **Step 4: `handleSubmit`** — zéro-safe lat/lng + ajouter :
  ```js
  points_forts: form.points_forts.filter(v => v.trim()),
  inclus: form.inclus.filter(v => v.trim()),
  non_inclus: form.non_inclus.filter(v => v.trim()),
  infos_pratiques: form.infos_pratiques || undefined,
  recommandations: form.recommandations || undefined,
  horaires: form.horaires || undefined,
  ```

- [ ] **Step 5: JSX**

  Remplacer le bloc lat/lng par `<LocationPicker .../>`. Juste après le champ `status`/disclaimer et avant `Description`, insérer :
  ```jsx
  <TagListInput
    label="Points forts"
    value={form.points_forts}
    onChange={v => setForm(f => ({ ...f, points_forts: v }))}
    placeholder="Ex: Terrasse ombragée, spécialités locales"
  />
  <TagListInput
    label="Ce qui est inclus"
    value={form.inclus}
    onChange={v => setForm(f => ({ ...f, inclus: v }))}
    placeholder="Ex: Boisson offerte, wifi"
  />
  <TagListInput
    label="Non inclus"
    value={form.non_inclus}
    onChange={v => setForm(f => ({ ...f, non_inclus: v }))}
    placeholder="Ex: Boissons alcoolisées"
  />
  <div className="admin-form__field"><label>Horaires</label>
    <input type="text" value={form.horaires} onChange={e => setForm(f => ({ ...f, horaires: e.target.value }))} placeholder="Ex: Lun-Ven 11h-22h" /></div>
  <div className="admin-form__field"><label>Infos pratiques</label>
    <textarea rows={3} value={form.infos_pratiques} onChange={e => setForm(f => ({ ...f, infos_pratiques: e.target.value }))} placeholder="Ex: Réservation recommandée le week-end" /></div>
  <div className="admin-form__field"><label>Recommandations</label>
    <textarea rows={3} value={form.recommandations} onChange={e => setForm(f => ({ ...f, recommandations: e.target.value }))} placeholder="Ex: Essayez le poisson braisé maison" /></div>
  ```

- [ ] **Step 6: Build**

  Run: `npm run build` — attendu : 0 erreur.

- [ ] **Step 7: Vérification Playwright réelle**

  Portail Admin : créer un restaurant, remplir carte + 3 tags + horaires + infos pratiques/recommandations, soumettre, rouvrir en édition, confirmer persistance. Console sans erreur JS.

- [ ] **Step 8: Commit**

  ```bash
  git add src/pages/admin/AdminRestaurants.jsx src/pages/prestataire/PrestataireRestaurants.jsx src/pages/responsable/ResponsableRestaurants.jsx
  git commit -m "feat(contenu-enrichi): câble LocationPicker/TagListInput dans Restaurant (Admin/Prestataire/Responsable)"
  ```

---

### Task 5: Transport — Admin/Prestataire/Responsable

**Files:**
- Modify: `src/pages/admin/AdminTransports.jsx`
- Modify: `src/pages/prestataire/PrestataireTransports.jsx`
- Modify: `src/pages/responsable/ResponsableTransports.jsx`

**Interfaces:**
- Consumes: `LocationPicker`, `TagListInput`.
- Produces: rien.

`type_transport`/`capacite` existent déjà et fonctionnent — ne pas y toucher. **Champs backend disponibles** (`TransportController::store`) : `points_forts`/`inclus`/`non_inclus`, `infos_pratiques`/`recommandations`, `duree_trajet_estimee` (`nullable|string|max:100` — texte libre, ex. "45 min").

- [ ] **Step 1: Imports**
  ```js
  import LocationPicker from '../../components/map/LocationPicker'
  import TagListInput from '../../components/forms/TagListInput'
  ```

- [ ] **Step 2: `emptyForm`** — ajouter :
  ```js
  points_forts: [], inclus: [], non_inclus: [],
  infos_pratiques: '', recommandations: '', duree_trajet_estimee: '',
  ```

- [ ] **Step 3: `openEdit`** — ajouter :
  ```js
  points_forts: transport.points_forts || [], inclus: transport.inclus || [], non_inclus: transport.non_inclus || [],
  infos_pratiques: transport.infos_pratiques || '', recommandations: transport.recommandations || '',
  duree_trajet_estimee: transport.duree_trajet_estimee || '',
  ```
  (Adapter le nom de la variable de boucle/paramètre exact déjà utilisé dans le fichier — vérifier le nom du paramètre d'`openEdit` en le lisant, il peut différer de `transport`.)

- [ ] **Step 4: `handleSubmit`** — zéro-safe lat/lng + ajouter :
  ```js
  points_forts: form.points_forts.filter(v => v.trim()),
  inclus: form.inclus.filter(v => v.trim()),
  non_inclus: form.non_inclus.filter(v => v.trim()),
  infos_pratiques: form.infos_pratiques || undefined,
  recommandations: form.recommandations || undefined,
  duree_trajet_estimee: form.duree_trajet_estimee || undefined,
  ```

- [ ] **Step 5: JSX**

  Remplacer le bloc lat/lng par `<LocationPicker .../>`. Juste après le champ `status`/disclaimer et avant `Description`, insérer :
  ```jsx
  <TagListInput
    label="Points forts"
    value={form.points_forts}
    onChange={v => setForm(f => ({ ...f, points_forts: v }))}
    placeholder="Ex: Climatisé, ponctuel"
  />
  <TagListInput
    label="Ce qui est inclus"
    value={form.inclus}
    onChange={v => setForm(f => ({ ...f, inclus: v }))}
    placeholder="Ex: Bagages inclus, wifi à bord"
  />
  <TagListInput
    label="Non inclus"
    value={form.non_inclus}
    onChange={v => setForm(f => ({ ...f, non_inclus: v }))}
    placeholder="Ex: Repas, bagage supplémentaire"
  />
  <div className="admin-form__field"><label>Durée de trajet estimée</label>
    <input type="text" value={form.duree_trajet_estimee} onChange={e => setForm(f => ({ ...f, duree_trajet_estimee: e.target.value }))} placeholder="Ex: 45 min" /></div>
  <div className="admin-form__field"><label>Infos pratiques</label>
    <textarea rows={3} value={form.infos_pratiques} onChange={e => setForm(f => ({ ...f, infos_pratiques: e.target.value }))} placeholder="Ex: Arriver 15 min avant le départ" /></div>
  <div className="admin-form__field"><label>Recommandations</label>
    <textarea rows={3} value={form.recommandations} onChange={e => setForm(f => ({ ...f, recommandations: e.target.value }))} placeholder="Ex: Prévoir une pièce d'identité" /></div>
  ```

- [ ] **Step 6: Build**

  Run: `npm run build` — attendu : 0 erreur.

- [ ] **Step 7: Vérification Playwright réelle**

  Portail Admin : créer un transport, remplir carte + 3 tags + durée trajet + infos pratiques/recommandations, soumettre, rouvrir en édition, confirmer persistance. Console sans erreur JS.

- [ ] **Step 8: Commit**

  ```bash
  git add src/pages/admin/AdminTransports.jsx src/pages/prestataire/PrestataireTransports.jsx src/pages/responsable/ResponsableTransports.jsx
  git commit -m "feat(contenu-enrichi): câble LocationPicker/TagListInput dans Transport (Admin/Prestataire/Responsable)"
  ```
