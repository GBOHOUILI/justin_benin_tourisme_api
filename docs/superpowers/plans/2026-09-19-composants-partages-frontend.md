# Composants partagés frontend (LocationPicker, TagListInput, ItineraryEditor) Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Créer 3 composants React réutilisables (sélecteur de localisation cliquable, éditeur de liste de textes répétable, éditeur d'itinéraire) et prouver qu'ils fonctionnent en les câblant dans `AdminSites.jsx` (le premier des 15 formulaires qui les utiliseront à terme).

**Architecture:** `LocationPicker` réutilise `react-leaflet` déjà en dépendance (mirroir du pattern déjà en place dans `CircuitMap.jsx` — `DivIcon` SVG inline pour éviter le gotcha des icônes PNG par défaut cassées par les bundlers). `TagListInput`/`ItineraryEditor` sont des éditeurs de liste contrôlés (`value`/`onChange`), sans dépendance nouvelle. Ce repo n'a pas de framework de test JS (pas de Jest/Vitest) — la vérification suit la convention déjà établie du projet : `npm run build` pour la compilation, puis vérification en réel via Playwright (serveur de dev + navigateur).

**Tech Stack:** React 18, `react-leaflet@^4.2.1` / `leaflet@^1.9.4` (déjà en dépendance), `lucide-react` pour les icônes (déjà en dépendance).

**Spec:** `docs/superpowers/specs/2026-09-18-fiches-detail-enrichies-design.md` (section "Chantier 1bis — Localisation" et "Composants d'édition de listes"), dans le repo `justin_benin_tourisme_api` — ce plan s'exécute dans le repo `totche-front`.

## Global Constraints

- Aucune nouvelle dépendance npm — tout se construit avec `react-leaflet`/`leaflet`/`lucide-react` déjà présents.
- `LocationPicker` expose `value` à plat (`latitude`, `longitude` en props séparées, pas un objet imbriqué) et `onChange({ latitude, longitude })` — les formulaires existants (`AdminSites.jsx` et les 14 autres) gèrent un `useState` plat, pas d'objet `location` imbriqué.
- Les liens Google Maps raccourcis (`maps.app.goo.gl/...`) ne sont pas parsables côté client (pas de clé API Google payante) — message d'erreur clair dans ce cas, jamais un crash.
- Style visuel cohérent avec l'existant : réutiliser les variables CSS déjà définies (`var(--red)`, `var(--gray-300)`, `var(--gray-700)`, etc.) et le pattern `.admin-form__field` — ne pas inventer une nouvelle palette.
- Chaque tâche se termine par `npm run build` (0 erreur) avant commit ; la tâche 4 se termine en plus par une vérification Playwright réelle (créer un site avec les nouveaux champs, recharger, confirmer la persistance).
- Ce plan ne couvre PAS le câblage des 14 autres formulaires (Admin/Prestataire/Responsable × Evenement/Hotel/Restaurant/Transport) ni l'usage réel d'`ItineraryEditor` (qui ne s'applique qu'à Événement) — ces câblages font l'objet d'un plan séparé. `ItineraryEditor` est construit et revu dans ce plan mais sa vérification Playwright n'aura lieu que dans le plan suivant, une fois câblé dans un vrai formulaire Événement.

---

### Task 1: `LocationPicker` — carte cliquable + lien Google Maps

**Files:**
- Create: `src/components/map/LocationPicker.jsx`
- Modify: `src/index.css` (nouvelles classes `.location-picker*`)

**Interfaces:**
- Produces: `export default function LocationPicker({ latitude, longitude, onChange })` — `latitude`/`longitude` : string ou number (comme les champs de formulaire existants, non convertis). `onChange({ latitude, longitude })` renvoie soit des numbers (clic carte, lien Google Maps), soit la string brute tapée dans les inputs numériques manuels — le composant appelant fait le `parseFloat` final à la soumission, comme il le fait déjà pour ses propres champs.

- [ ] **Step 1: Créer le composant**

`src/components/map/LocationPicker.jsx` :

```jsx
import { useEffect, useState } from 'react'
import { MapContainer, TileLayer, Marker, useMap, useMapEvents } from 'react-leaflet'
import L from 'leaflet'
import 'leaflet/dist/leaflet.css'
import toast from 'react-hot-toast'

// Pin simple (DivIcon SVG inline, comme CircuitMap.jsx - évite le gotcha des
// icônes PNG par défaut de Leaflet cassées par les bundlers, pas de nouvel
// asset à gérer).
const pinIcon = L.divIcon({
  className: 'location-picker__marker',
  html: '<span></span>',
  iconSize: [22, 22],
  iconAnchor: [11, 22],
})

// Bénin, vue d'ensemble - centre par défaut si aucune coordonnée existante.
const BENIN_CENTER = [9.3, 2.3]
const BENIN_ZOOM = 7

function ClickHandler({ onPick }) {
  useMapEvents({
    click(e) {
      onPick(e.latlng.lat, e.latlng.lng)
    },
  })
  return null
}

// Recentre la carte quand latitude/longitude changent depuis l'extérieur
// (lien Google Maps collé, ou modification manuelle des champs) - un clic
// sur la carte se recentre déjà visuellement de lui-même, ce composant
// couvre les deux autres sources de changement.
function RecenterOnChange({ lat, lng }) {
  const map = useMap()
  useEffect(() => {
    if (Number.isFinite(lat) && Number.isFinite(lng)) {
      map.setView([lat, lng], map.getZoom())
    }
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [lat, lng])
  return null
}

// Motifs de liens Google Maps supportés : .../@lat,lng,zoom, ?q=lat,lng,
// ?ll=lat,lng. Les liens raccourcis (maps.app.goo.gl/...) ne sont pas
// résolubles côté client sans clé API Google payante - message d'erreur
// clair dans ce cas plutôt qu'un crash silencieux.
function parseGoogleMapsLink(url) {
  const patterns = [
    /@(-?\d+\.\d+),(-?\d+\.\d+)/,
    /[?&]q=(-?\d+\.\d+),(-?\d+\.\d+)/,
    /[?&]ll=(-?\d+\.\d+),(-?\d+\.\d+)/,
  ]
  for (const re of patterns) {
    const m = url.match(re)
    if (m) return { lat: parseFloat(m[1]), lng: parseFloat(m[2]) }
  }
  return null
}

export default function LocationPicker({ latitude, longitude, onChange }) {
  const [linkValue, setLinkValue] = useState('')

  const lat = parseFloat(latitude)
  const lng = parseFloat(longitude)
  const hasPosition = Number.isFinite(lat) && Number.isFinite(lng)
  const center = hasPosition ? [lat, lng] : BENIN_CENTER
  const zoom = hasPosition ? 14 : BENIN_ZOOM

  const handlePick = (newLat, newLng) => {
    onChange({ latitude: newLat, longitude: newLng })
  }

  const handleLinkSubmit = (e) => {
    e.preventDefault()
    if (!linkValue.trim()) return
    const coords = parseGoogleMapsLink(linkValue)
    if (!coords) {
      toast.error("Lien non reconnu (les liens raccourcis type maps.app.goo.gl ne sont pas supportés) - utilisez la carte ci-dessous.")
      return
    }
    handlePick(coords.lat, coords.lng)
    setLinkValue('')
    toast.success('Position récupérée depuis le lien.')
  }

  return (
    <div className="location-picker">
      <form className="location-picker__link" onSubmit={handleLinkSubmit}>
        <input
          type="text"
          value={linkValue}
          onChange={e => setLinkValue(e.target.value)}
          placeholder="Coller un lien Google Maps (facultatif)"
        />
        <button type="submit" className="btn btn--ghost btn--sm">Utiliser</button>
      </form>

      <div className="location-picker__map">
        <MapContainer center={center} zoom={zoom} scrollWheelZoom={false} style={{ height: '100%', width: '100%' }}>
          <TileLayer
            attribution='&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>'
            url="https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png"
          />
          {hasPosition && <Marker position={[lat, lng]} icon={pinIcon} />}
          <ClickHandler onPick={handlePick} />
          <RecenterOnChange lat={lat} lng={lng} />
        </MapContainer>
      </div>

      <div className="location-picker__coords">
        <div className="admin-form__field">
          <label>Latitude *</label>
          <input
            type="number" step="any" required
            value={latitude}
            onChange={e => onChange({ latitude: e.target.value, longitude })}
          />
        </div>
        <div className="admin-form__field">
          <label>Longitude *</label>
          <input
            type="number" step="any" required
            value={longitude}
            onChange={e => onChange({ latitude, longitude: e.target.value })}
          />
        </div>
      </div>
    </div>
  )
}
```

- [ ] **Step 2: Ajouter les classes CSS**

Dans `src/index.css`, à la suite du bloc `.circuit-map*` existant :

```css
/* LocationPicker */
.location-picker__link { display: flex; gap: 0.5rem; margin-bottom: 0.75rem; }
.location-picker__link input { flex: 1; border: 1px solid var(--gray-300); padding: 0.65rem 0.875rem; font-size: 0.875rem; }
.location-picker__map { height: 240px; border: 1px solid var(--gray-300); overflow: hidden; margin-bottom: 0.75rem; }
.location-picker__map .leaflet-container { background: var(--gray-100); font-family: var(--font-body); }
.location-picker__marker.leaflet-div-icon { background: transparent; border: none; }
.location-picker__marker span { display: block; width: 20px; height: 20px; border-radius: 50% 50% 50% 0; transform: rotate(-45deg); background: var(--red); border: 2px solid var(--white); box-shadow: 0 2px 6px rgba(0,0,0,.35); }
.location-picker__coords { display: grid; grid-template-columns: 1fr 1fr; gap: 0.75rem; }
```

- [ ] **Step 3: Vérifier la compilation**

Run: `npm run build`
Expected: `✓ built` sans erreur, aucun warning sur `LocationPicker.jsx`.

- [ ] **Step 4: Commit**

```bash
git add src/components/map/LocationPicker.jsx src/index.css
git commit -m "feat(contenu-enrichi): composant LocationPicker (carte cliquable + lien Google Maps)"
```

---

### Task 2: `TagListInput` — éditeur de liste de textes répétable

**Files:**
- Create: `src/components/forms/TagListInput.jsx`
- Modify: `src/index.css` (nouvelles classes `.tag-list-input*`)

**Interfaces:**
- Consumes: aucune dépendance des tâches précédentes.
- Produces: `export default function TagListInput({ label, values = [], onChange, placeholder })` — `values: string[]`, `onChange(string[])`. Réutilisé tel quel pour `points_forts`/`inclus`/`non_inclus` sur les 5 entités.

- [ ] **Step 1: Créer le dossier et le composant**

`src/components/forms/TagListInput.jsx` (le dossier `src/components/forms/` n'existe pas encore, le créer) :

```jsx
import { Plus, X } from 'lucide-react'

// values: string[] - onChange(string[]). Réutilisé pour points_forts,
// inclus, non_inclus sur les 5 entités (Site/Evenement/Hotel/Restaurant/
// Transport) - même composant partout, seul le label change par appelant.
export default function TagListInput({ label, values = [], onChange, placeholder }) {
  const update = (i, val) => {
    const next = [...values]
    next[i] = val
    onChange(next)
  }
  const remove = (i) => onChange(values.filter((_, idx) => idx !== i))
  const add = () => onChange([...values, ''])

  return (
    <div className="admin-form__field tag-list-input">
      <label>{label}</label>
      {values.map((v, i) => (
        <div key={i} className="tag-list-input__row">
          <input
            type="text"
            value={v}
            onChange={e => update(i, e.target.value)}
            placeholder={placeholder}
          />
          <button type="button" className="tag-list-input__remove" onClick={() => remove(i)}>
            <X size={14} />
          </button>
        </div>
      ))}
      <button type="button" className="tag-list-input__add" onClick={add}>
        <Plus size={14} /> Ajouter
      </button>
    </div>
  )
}
```

- [ ] **Step 2: Ajouter les classes CSS**

Dans `src/index.css`, à la suite du bloc `.location-picker*` :

```css
/* TagListInput */
.tag-list-input__row { display: flex; gap: 0.5rem; margin-bottom: 0.5rem; }
.tag-list-input__row input { flex: 1; border: 1px solid var(--gray-300); padding: 0.6rem 0.75rem; font-size: 0.85rem; }
.tag-list-input__remove { color: var(--gray-500); padding: 0.4rem; }
.tag-list-input__remove:hover { color: var(--red); }
.tag-list-input__add { display: inline-flex; align-items: center; gap: 0.35rem; color: var(--red); font-size: 0.8rem; font-weight: 600; padding: 0.4rem 0; background: none; border: none; cursor: pointer; }
```

- [ ] **Step 3: Vérifier la compilation**

Run: `npm run build`
Expected: `✓ built` sans erreur.

- [ ] **Step 4: Commit**

```bash
git add src/components/forms/TagListInput.jsx src/index.css
git commit -m "feat(contenu-enrichi): composant TagListInput (liste de textes répétable)"
```

---

### Task 3: `ItineraryEditor` — éditeur d'itinéraire répétable (Événement)

**Files:**
- Create: `src/components/forms/ItineraryEditor.jsx`
- Modify: `src/index.css` (nouvelles classes `.itinerary-editor*`)

**Interfaces:**
- Produces: `export default function ItineraryEditor({ value = [], onChange })` — `value: {titre, description}[]`, `onChange(entries)`. Utilisé uniquement dans les formulaires Événement (Admin/Prestataire/Responsable), câblés dans le plan suivant — ce composant n'est PAS vérifié en réel dans ce plan-ci (aucun formulaire Événement n'est touché ici), seulement construit et revu. Sa vérification Playwright aura lieu dans le plan de câblage des formulaires Événement.

- [ ] **Step 1: Créer le composant**

`src/components/forms/ItineraryEditor.jsx` :

```jsx
import { Plus, X } from 'lucide-react'

// value: {titre, description}[] - onChange(entries). Utilisé uniquement
// pour Evenement.itineraire (programme jour par jour) - pas d'équivalent
// sur les 4 autres entités.
export default function ItineraryEditor({ value = [], onChange }) {
  const update = (i, field, val) => {
    const next = [...value]
    next[i] = { ...next[i], [field]: val }
    onChange(next)
  }
  const remove = (i) => onChange(value.filter((_, idx) => idx !== i))
  const add = () => onChange([...value, { titre: '', description: '' }])

  return (
    <div className="admin-form__field itinerary-editor">
      <label>Itinéraire (programme jour par jour)</label>
      {value.map((step, i) => (
        <div key={i} className="itinerary-editor__step">
          <div className="itinerary-editor__step-header">
            <span>Étape {i + 1}</span>
            <button type="button" className="tag-list-input__remove" onClick={() => remove(i)}>
              <X size={14} />
            </button>
          </div>
          <input
            type="text"
            value={step.titre}
            onChange={e => update(i, 'titre', e.target.value)}
            placeholder="Titre (ex: Jour 1 - Arrivée à Ouidah)"
          />
          <textarea
            rows={2}
            value={step.description}
            onChange={e => update(i, 'description', e.target.value)}
            placeholder="Description de l'étape"
          />
        </div>
      ))}
      <button type="button" className="tag-list-input__add" onClick={add}>
        <Plus size={14} /> Ajouter une étape
      </button>
    </div>
  )
}
```

- [ ] **Step 2: Ajouter les classes CSS**

Dans `src/index.css`, à la suite du bloc `.tag-list-input*` :

```css
/* ItineraryEditor */
.itinerary-editor__step { border: 1px solid var(--gray-100); padding: 0.75rem; margin-bottom: 0.6rem; display: flex; flex-direction: column; gap: 0.5rem; }
.itinerary-editor__step-header { display: flex; justify-content: space-between; align-items: center; font-size: 0.8rem; font-weight: 600; color: var(--gray-700); }
.itinerary-editor__step input, .itinerary-editor__step textarea { border: 1px solid var(--gray-300); padding: 0.6rem 0.75rem; font-size: 0.85rem; width: 100%; }
```

- [ ] **Step 3: Vérifier la compilation**

Run: `npm run build`
Expected: `✓ built` sans erreur.

- [ ] **Step 4: Commit**

```bash
git add src/components/forms/ItineraryEditor.jsx src/index.css
git commit -m "feat(contenu-enrichi): composant ItineraryEditor (programme jour par jour Evenement)"
```

---

### Task 4: Câblage de preuve dans `AdminSites.jsx` (LocationPicker + TagListInput)

**Files:**
- Modify: `src/pages/admin/AdminSites.jsx`

**Interfaces:**
- Consumes: `LocationPicker` (Task 1), `TagListInput` (Task 2).

Ce fichier fait actuellement 233 lignes (contenu actuel connu, cf. le formulaire de création/édition existant avec `emptyForm`, `openEdit`, `handleSubmit`, et le bloc JSX de la modale).

- [ ] **Step 1: Étendre `emptyForm`**

Remplacer :

```js
const emptyForm = {
  libelle: '', adresse: '', description: '',
  id_cat_site: '', latitude: '', longitude: '',
  ouverture: '', fermeture: '', status: 'en_attente', id_region: ''
}
```

par :

```js
const emptyForm = {
  libelle: '', adresse: '', description: '',
  id_cat_site: '', latitude: '', longitude: '',
  ouverture: '', fermeture: '', status: 'en_attente', id_region: '',
  points_forts: [], inclus: [], non_inclus: [], duree_visite: '', difficulte: '',
}
```

- [ ] **Step 2: Étendre `openEdit` pour peupler les nouveaux champs**

Dans `openEdit`, ajouter aux clés déjà peuplées :

```js
      points_forts: site.points_forts || [], inclus: site.inclus || [], non_inclus: site.non_inclus || [],
      duree_visite: site.duree_visite || '', difficulte: site.difficulte || '',
```

- [ ] **Step 3: Importer les deux composants**

En haut du fichier, ajouter :

```js
import LocationPicker from '../../components/map/LocationPicker'
import TagListInput from '../../components/forms/TagListInput'
```

- [ ] **Step 4: Remplacer le bloc latitude/longitude par `LocationPicker`**

Remplacer ce bloc dans le JSX :

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

- [ ] **Step 5: Ajouter les nouveaux champs de contenu enrichi dans le JSX**

Juste avant le bloc `<div className="admin-form__field"><label>Description</label>` existant, insérer :

```jsx
              <TagListInput
                label="Points forts"
                values={form.points_forts}
                onChange={v => setForm(f => ({ ...f, points_forts: v }))}
                placeholder="Ex: Vue imprenable sur la lagune"
              />
              <TagListInput
                label="Ce que le billet inclut"
                values={form.inclus}
                onChange={v => setForm(f => ({ ...f, inclus: v }))}
                placeholder="Ex: Accès au site, parking"
              />
              <TagListInput
                label="Non inclus"
                values={form.non_inclus}
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
```

- [ ] **Step 6: Nettoyer les tableaux vides avant soumission**

Dans `handleSubmit`, le payload actuel est :

```js
    const payload = {
      ...form,
      latitude: form.latitude ? parseFloat(form.latitude) : undefined,
      longitude: form.longitude ? parseFloat(form.longitude) : undefined,
      id_cat_site: form.id_cat_site ? parseInt(form.id_cat_site) : undefined,
      id_region: form.id_region ? parseInt(form.id_region) : undefined,
    }
```

Ajouter le filtrage des entrées vides des listes (un utilisateur peut cliquer "Ajouter" puis laisser vide) :

```js
    const payload = {
      ...form,
      latitude: form.latitude ? parseFloat(form.latitude) : undefined,
      longitude: form.longitude ? parseFloat(form.longitude) : undefined,
      id_cat_site: form.id_cat_site ? parseInt(form.id_cat_site) : undefined,
      id_region: form.id_region ? parseInt(form.id_region) : undefined,
      points_forts: form.points_forts.filter(v => v.trim()),
      inclus: form.inclus.filter(v => v.trim()),
      non_inclus: form.non_inclus.filter(v => v.trim()),
      duree_visite: form.duree_visite || undefined,
      difficulte: form.difficulte || undefined,
    }
```

- [ ] **Step 7: Vérifier la compilation**

Run: `npm run build`
Expected: `✓ built` sans erreur.

- [ ] **Step 8: Vérification en réel (Playwright)**

Démarrer le serveur de dev si besoin (`npm run dev`, déjà lancé dans cette session normalement — vérifier via `curl -s -o /dev/null -w "%{http_code}" http://localhost:5173` avant de relancer). Backend Docker doit tourner (`docker ps` doit montrer `benin_tourisme_app`).

Se connecter en admin (`+22901000000` / `admin123` — reseed via `docker exec benin_tourisme_app php artisan db:seed --force` si le compte a disparu, comportement déjà documenté dans le ROADMAP du repo API). Naviguer sur `/admin/sites`, cliquer "Ajouter", remplir le formulaire :
- Nom, adresse, catégorie (requis, comme avant)
- Cliquer sur la carte pour placer un marqueur → vérifier que les champs Latitude/Longitude se remplissent automatiquement
- Ajouter 2 points forts, 1 élément inclus
- Choisir une durée de visite et une difficulté
- Soumettre

Vérifier : le site apparaît dans la liste, rouvrir en édition confirme que tous les nouveaux champs (points forts, inclus, durée de visite, difficulté, latitude/longitude du clic carte) sont bien pré-remplis avec les valeurs saisies. Vérifier 0 erreur console à chaque étape. Supprimer le site de test après vérification (bouton Supprimer de la liste), comme la convention déjà établie dans le ROADMAP du repo API.

- [ ] **Step 9: Commit**

```bash
git add src/pages/admin/AdminSites.jsx
git commit -m "feat(contenu-enrichi): câble LocationPicker et TagListInput dans AdminSites.jsx (preuve d'intégration)"
```

---

## Suite

Une fois ce plan exécuté, un plan séparé câble les mêmes composants (+ `ItineraryEditor` pour Événement) dans les 14 formulaires restants (Admin/Prestataire/Responsable × Evenement/Hotel/Restaurant/Transport, + Prestataire/Responsable pour Site). Un autre plan, indépendant, s'occupe des pages détail publiques (affichage) et un dernier des Témoignages plateforme.
