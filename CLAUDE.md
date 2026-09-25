# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project

Laravel 13 (PHP 8.3) REST API for "Benin Tourisme" — a French-language tourism platform covering touristic sites and cultural events in Benin (site management, reservations, tickets, reviews, categories, media galleries). No frontend is served from here; Vite/Tailwind are only wired up as unused Laravel boilerplate.

## Commands

Run everything through Docker Compose — there's no local PHP/MySQL setup documented.

```bash
docker-compose up -d --build       # build & start app (Apache, :8000) + MySQL (:3306)
docker-compose exec app php artisan <cmd>
docker-compose exec app composer <cmd>
```

Container entrypoint auto-runs on every start: `config:clear`, `migrate --force`, `storage:link`, `route:cache`, `view:cache` (see `Dockerfile` CMD) — deliberately **no** `config:cache` (see the warning above the CMD line in `Dockerfile`: caching config makes `env()` stop being re-read at runtime, which silently breaks PHPUnit's DB env overrides). Restart the `app` container after route/controller/config changes to pick them up, or run the equivalent `artisan` commands manually inside the container during active development.

Common artisan commands (run inside the container):
```bash
php artisan migrate                     # apply migrations
php artisan migrate:fresh --seed        # rebuild schema + seed
php artisan test                        # run PHPUnit (equivalent to `composer test`)
php artisan test --filter=TestName      # run a single test
php artisan l5-swagger:generate         # regenerate OpenAPI docs from PHP attributes
php artisan db:seed --class=DemoContentSeeder  # populate demo content (real Bénin sites/events/hotels/restaurants/transports + testimonials) — idempotent, not run by default, cf. ROADMAP.md "Constat environnement 2026-09-25"
```

There are 5 Feature test files (~40 tests as of this branch) covering Auth, Reservation ownership, Avis ownership, User ownership, and the "contenu enrichi" fields (round-trips + HTTP validation). Not yet covered: Site/Hotel/Restaurant/Transport workflow-validation and Prestataire/Responsable ownership paths. Tests run against a dedicated MySQL database `benin_tourisme_test` (see `phpunit.xml`), separate from the dev database `benin_tourisme` — several migrations use raw MySQL-specific SQL incompatible with SQLite.

## API documentation (Swagger)

Endpoints are documented inline via `zircote/swagger-php` (`OpenApi\Attributes` / `OA\...`) PHP attributes directly on controller methods — see `app/Http/Controllers/SwaggerController.php` for the global `#[OA\Info]`/`#[OA\Tag]` declarations and any controller (e.g. `GalerieSiteController`) for the per-route pattern. Only `app/Http/Controllers` is scanned (`config/l5-swagger.php`). Rendered UI is at `/docs` (or `/api/documentation`, controlled by `config/l5-swagger.php`); regenerate after route/DTO changes with `php artisan l5-swagger:generate`.

When adding or changing an endpoint, add/update its `#[OA\Get|Post|Put|Delete(...)]` attribute block on the controller method — this is the only source of API documentation in the project.

## Architecture

### Two separate auth guards — not one user model

`config/auth.php` defines **two independent Sanctum guards** with separate Eloquent providers:
- `sanctum` guard → `App\Models\User` (public/tourist accounts)
- `admin` guard → `App\Models\Admin` (staff accounts)

Both issue Sanctum personal access tokens, but a User token and an Admin token are not interchangeable — `AuthController` has separate `login`/`loginAdmin` actions, and admin login also revokes all previous admin tokens (`$admin->tokens()->delete()`) before issuing a new one, i.e. single active session per admin.

`app/Http/Middleware/EnsureIsAdmin.php` (aliased `admin`) must run **after** an auth guard middleware; it checks `$request->user() instanceof Admin` and that `$admin->status` is truthy (disabled admins are rejected with 403 even with a valid token). Admin-only routes always pair both: `Route::middleware(['auth:admin', 'admin'])`.

### Routing structure (`routes/api.php`)

Three tiers, in this order:
1. **Public** (no middleware) — read-only listing/show endpoints for sites, evenements, categories, prix, galeries, avis, plus register/login/ticket verification.
2. **`auth:sanctum`** — authenticated tourist actions: profile, reservations (full `apiResource`), posting/editing own avis.
3. **`auth:admin` + `admin`, prefixed `/admin`** — all write operations (create/update/delete) on domain resources, admin management, user management, event moderation (`valider`/`rejeter`), avis moderation (`approuver`/`rejeter`), and `Fonctionnalite` (permission) assignment to admins/users.

Follow this pattern for new resources: public GET/show unauthenticated, mutation routes go under the `/admin` group unless the resource is user-owned (like `avis`/`reservations`), in which case mutations sit in the `auth:sanctum` group instead.

### Controllers have no service/repository layer

Controllers talk directly to Eloquent models (`Model::create($validated)`, `$query->with(...)->get()`, etc.) — there is no service, repository, or DTO layer in this codebase, unlike the NestJS-style layering used elsewhere. Follow the existing flat controller pattern rather than introducing new layers unless explicitly asked to refactor.

### Domain model (French naming)

Table/model names and columns are in French; DB tables mostly use **singular** snake_case names that don't match Laravel's pluralization defaults, so every model declares `protected $table = '...'` explicitly (e.g. `Site::$table = 'site'`). Foreign keys follow `id_<related>` (e.g. `id_cat_site`, `id_admin`, `id_site`), not Laravel's default `<related>_id`, so relation methods always pass explicit foreign/pivot key names.

Core entities: `Site` / `CatSite` (categories), `Evenement` / `CatEvenmt`, `Prix` (pricing), `GalerieSite` / `GallerieEvnmt` (media galleries, file upload), `Reservation`, `Ticket`, `Utilisation`, `Avis` (reviews, with admin moderation via `approuver`/`rejeter`), `Fonctionnalite` (permission/feature flags assignable to admins or users), `Admin`, `User`. `Site` ↔ `Evenement` is many-to-many through the `disposer` pivot table.

### File uploads

Uploaded media (galerie images/videos) are stored via `Storage::disk('public')->store(...)` under `galeries/sites` or `galeries/evenements`, with the resulting relative path saved in the `url_fichier` column — not the human-readable `libelle`/title field. Controllers that delete a gallery entry must also delete the physical file (`Storage::disk('public')->exists(...)` / `->delete(...)`) — see `GalerieSiteController::destroy` as the reference pattern. Requires `php artisan storage:link` (already run automatically by the Docker entrypoint).

# Vérification frontend

Pour toute modification frontend (totche-front) : utiliser Playwright pour naviguer vers 
la page concernée, prendre un snapshot/screenshot avant et après, vérifier la console pour 
les erreurs JS, et confirmer le comportement réel (clic, formulaire) avant de considérer 
la tâche terminée. Ne jamais se contenter de "le code compile" comme critère de succès 
côté UI.
