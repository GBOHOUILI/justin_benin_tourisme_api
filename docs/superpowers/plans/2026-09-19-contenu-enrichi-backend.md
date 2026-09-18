# Contenu enrichi — Backend (5 entités) Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Ajouter les champs de contenu enrichi (points forts, inclus/non-inclus, infos pratiques, recommandations, champs contextuels par type) sur les tables `site`, `evenement`, `hotel`, `restaurant`, `transport`, et les exposer/valider dans les 5 controllers correspondants.

**Architecture:** Une migration touche les 5 tables à la fois pour les colonnes communes (pattern déjà utilisé dans ce projet pour `id_prestataire`/`id_region`). Chaque entité reçoit ensuite sa propre migration pour ses champs spécifiques. Les colonnes liste (`points_forts`, `inclus`, `non_inclus`, `itineraire`) sont des colonnes `json` castées `array` côté Eloquent — seul pattern JSON déjà en place dans le projet (`Plan::$casts['fonctionnalites']`), pas de nouvelle table enfant. Aucune colonne n'est requise (`nullable`) : les fiches existantes ne cassent pas.

**Tech Stack:** Laravel 13 / PHP 8.3, MySQL (dev via Docker), PHPUnit + SQLite en mémoire pour les tests (`docker-compose exec app php artisan test`).

**Spec:** `docs/superpowers/specs/2026-09-18-fiches-detail-enrichies-design.md` (sections "Chantier 1 — Contenu enrichi" et "Champs spécifiques par entité")

## Global Constraints

- Toutes les nouvelles colonnes sont `nullable` — aucune migration ne doit casser une fiche existante.
- `doctrine/dbal` n'est pas installé dans ce projet : toute conversion de type de colonne existante (ex. `date` → `datetime`) passe par `DB::statement()` en SQL brut, jamais par `Schema::table(...)->change()`.
- Éditables par Admin/Prestataire/Responsable au même titre que `description` : aucune restriction d'ownership supplémentaire sur ces champs de contenu (contrairement à `status`/`id_region`, déjà protégés).
- Chaque tâche se termine par `docker exec benin_tourisme_app php artisan test` vert avant de commit.
- Ce plan ne touche à aucun fichier frontend (`totche-front`) — c'est un plan backend uniquement. Le câblage frontend fait l'objet d'un plan séparé.
- Toutes les commandes `artisan`/`composer`/`php` de ce plan s'exécutent via `docker exec benin_tourisme_app ...` (ou `docker-compose exec app ...`), jamais en local — `bootstrap/cache/*.php` est généré avec les chemins `/var/www` du container, `php artisan` en local échoue sur des erreurs de permissions sans rapport avec le code (déjà documenté dans `ROADMAP.md`).
- Après toute modification d'un fichier PHP déjà chargé par Apache (controllers, `bootstrap/app.php`), un `docker restart benin_tourisme_app` est nécessaire avant de vérifier via une vraie requête HTTP (`opcache.validate_timestamps=0`) — les tests `artisan test` via `docker exec` n'ont pas besoin de ce restart (nouveau process PHP à chaque appel), mais toute vérification `curl` en tâche 7 si.

---

### Task 1: Migration commune + modèle `Site`

**Files:**
- Create: `database/migrations/2026_09_19_100000_add_contenu_enrichi_to_site_evenement_hotel_restaurant_transport_tables.php`
- Modify: `app/Models/Site.php`
- Test: `tests/Feature/ContenuEnrichiEntitesTest.php` (nouveau fichier)

**Interfaces:**
- Consumes: `Site::factory()` (existe déjà, `database/factories/SiteFactory.php`)
- Produces: colonnes `points_forts`, `inclus`, `non_inclus` (array, castées `array`), `infos_pratiques`, `recommandations` (string) sur les 5 tables `site`/`evenement`/`hotel`/`restaurant`/`transport`. Seul `Site::$fillable`/`$casts` est mis à jour dans cette tâche — les 4 autres modèles sont mis à jour dans leurs tâches respectives (2, 4, 5, 6).

- [ ] **Step 1: Écrire le test qui échoue**

Créer `tests/Feature/ContenuEnrichiEntitesTest.php` :

```php
<?php

namespace Tests\Feature;

use App\Models\Site;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ContenuEnrichiEntitesTest extends TestCase
{
    use RefreshDatabase;

    public function test_site_round_trips_points_forts_inclus_non_inclus_as_arrays(): void
    {
        $site = Site::factory()->create([
            'points_forts' => ['Vue imprenable', 'Guide inclus'],
            'inclus' => ['Accès au site', 'Parking'],
            'non_inclus' => ['Transport', 'Repas'],
            'infos_pratiques' => 'Prévoir de bonnes chaussures.',
            'recommandations' => 'Venir tôt le matin pour éviter la foule.',
        ]);

        $fresh = $site->fresh();

        $this->assertSame(['Vue imprenable', 'Guide inclus'], $fresh->points_forts);
        $this->assertSame(['Accès au site', 'Parking'], $fresh->inclus);
        $this->assertSame(['Transport', 'Repas'], $fresh->non_inclus);
        $this->assertSame('Prévoir de bonnes chaussures.', $fresh->infos_pratiques);
        $this->assertSame('Venir tôt le matin pour éviter la foule.', $fresh->recommandations);
    }
}
```

- [ ] **Step 2: Lancer le test, vérifier qu'il échoue**

Run: `docker exec benin_tourisme_app php artisan test --filter=test_site_round_trips_points_forts_inclus_non_inclus_as_arrays`
Expected: FAIL — `SQLSTATE[HY000]: General error: 1 table site has no column named points_forts` (SQLite) ou équivalent MySQL selon la connexion de test.

- [ ] **Step 3: Créer la migration commune**

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Socle de contenu enrichi partagé par les 5 entités touristiques - cf.
 * docs/superpowers/specs/2026-09-18-fiches-detail-enrichies-design.md.
 * points_forts/inclus/non_inclus en JSON (liste de textes), même pattern
 * que Plan::fonctionnalites (seul champ JSON existant avant celui-ci).
 * Tout nullable : aucune fiche existante ne casse.
 */
return new class extends Migration
{
    private array $tables = ['site', 'evenement', 'hotel', 'restaurant', 'transport'];

    public function up(): void
    {
        foreach ($this->tables as $tableName) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->json('points_forts')->nullable();
                $table->json('inclus')->nullable();
                $table->json('non_inclus')->nullable();
                $table->text('infos_pratiques')->nullable();
                $table->text('recommandations')->nullable();
            });
        }
    }

    public function down(): void
    {
        foreach ($this->tables as $tableName) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->dropColumn(['points_forts', 'inclus', 'non_inclus', 'infos_pratiques', 'recommandations']);
            });
        }
    }
};
```

- [ ] **Step 4: Mettre à jour `app/Models/Site.php`**

Dans le `$fillable` existant, ajouter après `'description',` :

```php
        'points_forts',
        'inclus',
        'non_inclus',
        'infos_pratiques',
        'recommandations',
```

Dans le `$casts` existant (qui contient déjà `'ouverture' => 'datetime', 'fermeture' => 'datetime'`), ajouter :

```php
        'points_forts' => 'array',
        'inclus' => 'array',
        'non_inclus' => 'array',
```

- [ ] **Step 5: Lancer le test, vérifier qu'il passe**

Run: `docker exec benin_tourisme_app php artisan test --filter=test_site_round_trips_points_forts_inclus_non_inclus_as_arrays`
Expected: PASS

- [ ] **Step 6: Lancer toute la suite pour vérifier l'absence de régression**

Run: `docker exec benin_tourisme_app php artisan test`
Expected: tous les tests existants (31 avant cette tâche) + le nouveau, tous PASS.

- [ ] **Step 7: Commit**

```bash
git add database/migrations/2026_09_19_100000_add_contenu_enrichi_to_site_evenement_hotel_restaurant_transport_tables.php app/Models/Site.php tests/Feature/ContenuEnrichiEntitesTest.php
git commit -m "feat(contenu-enrichi): migration commune + modèle Site (points forts, inclus, non-inclus, infos pratiques)"
```

---

### Task 2: `Evenement` — champs communs, champs spécifiques (itinéraire, groupe, langue, difficulté) et conversion date_debut/date_fin en datetime

**Files:**
- Create: `database/migrations/2026_09_19_100100_add_champs_specifiques_to_evenement_table.php`
- Create: `database/migrations/2026_09_19_100200_convert_date_debut_fin_to_datetime_on_evenement_table.php`
- Create: `database/factories/CatEvenmtFactory.php`
- Create: `database/factories/EvenementFactory.php`
- Modify: `app/Models/CatEvenmt.php` (ajout `HasFactory`)
- Modify: `app/Models/Evenement.php` (ajout `HasFactory`, `$fillable`, `$casts`)
- Modify: `app/Http/Controllers/EvenementController.php:244-255` (store) et `:406-417` (update)
- Test: `tests/Feature/ContenuEnrichiEntitesTest.php` (ajout de méthodes)

**Interfaces:**
- Produces: `Evenement::factory()`, `CatEvenmt::factory()`. Colonnes `evenement.itineraire` (array de `{titre, description}`), `groupe_min`/`groupe_max` (int nullable), `langue` (string nullable), `difficulte` (enum `facile|moderee|difficile` nullable). `date_debut`/`date_fin` deviennent des vraies colonnes `datetime` (l'heure est désormais persistée, plus seulement castée côté Eloquent).

- [ ] **Step 1: Écrire les tests qui échouent**

Ajouter à `tests/Feature/ContenuEnrichiEntitesTest.php` :

```php
    public function test_evenement_round_trips_itineraire_groupe_langue_difficulte(): void
    {
        $evenement = \App\Models\Evenement::factory()->create([
            'itineraire' => [
                ['titre' => 'Jour 1 - Arrivée', 'description' => 'Accueil à Ouidah.'],
                ['titre' => 'Jour 2 - Cérémonies', 'description' => 'Immersion Vodun.'],
            ],
            'groupe_min' => 2,
            'groupe_max' => 10,
            'langue' => 'Français, Anglais',
            'difficulte' => 'moderee',
        ]);

        $fresh = $evenement->fresh();

        $this->assertSame('Jour 1 - Arrivée', $fresh->itineraire[0]['titre']);
        $this->assertSame(2, $fresh->groupe_min);
        $this->assertSame(10, $fresh->groupe_max);
        $this->assertSame('Français, Anglais', $fresh->langue);
        $this->assertSame('moderee', $fresh->difficulte);
    }

    public function test_evenement_date_debut_stores_time_of_day(): void
    {
        $evenement = \App\Models\Evenement::factory()->create([
            'date_debut' => '2027-01-02 12:00:00',
        ]);

        $this->assertSame('12:00:00', $evenement->fresh()->date_debut->format('H:i:s'));
    }
```

- [ ] **Step 2: Lancer les tests, vérifier qu'ils échouent**

Run: `docker exec benin_tourisme_app php artisan test --filter=ContenuEnrichiEntitesTest`
Expected: FAIL — `Class "App\Models\Evenement" ... no factory defined` (pas de `HasFactory`/factory), puis après ajout de la factory, échec sur colonnes manquantes.

- [ ] **Step 3: Créer `CatEvenmtFactory` et activer `HasFactory` sur `CatEvenmt`**

`database/factories/CatEvenmtFactory.php` :

```php
<?php

namespace Database\Factories;

use App\Models\CatEvenmt;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CatEvenmt>
 */
class CatEvenmtFactory extends Factory
{
    protected $model = CatEvenmt::class;

    public function definition(): array
    {
        return [
            'libelle' => fake()->unique()->word(),
        ];
    }
}
```

Dans `app/Models/CatEvenmt.php`, ajouter l'import et le trait comme déjà fait sur `CatSite` :

```php
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CatEvenmt extends Model
{
    use HasFactory;

    protected $table = 'cat_evenmt';
```

- [ ] **Step 4: Créer `EvenementFactory` et activer `HasFactory` sur `Evenement`**

`database/factories/EvenementFactory.php` :

```php
<?php

namespace Database\Factories;

use App\Models\Admin;
use App\Models\CatEvenmt;
use App\Models\Evenement;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Evenement>
 */
class EvenementFactory extends Factory
{
    protected $model = Evenement::class;

    public function definition(): array
    {
        return [
            'libelle' => fake()->unique()->sentence(3),
            'adresse' => fake()->address(),
            'longitude' => fake()->longitude(1, 3),
            'latitude' => fake()->latitude(6, 12),
            'description' => fake()->sentence(),
            'date_debut' => now()->addMonth(),
            'date_fin' => now()->addMonth()->addDays(3),
            'status' => 'valide',
            'id_cat_evenmt' => CatEvenmt::factory(),
            'id_admin' => Admin::factory(),
        ];
    }
}
```

Dans `app/Models/Evenement.php`, ajouter l'import et le trait :

```php
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Evenement extends Model
{
    use HasFactory;

    protected $table = 'evenement';
```

- [ ] **Step 5: Migration des champs spécifiques Événement**

`database/migrations/2026_09_19_100100_add_champs_specifiques_to_evenement_table.php` :

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('evenement', function (Blueprint $table) {
            $table->json('itineraire')->nullable();
            $table->unsignedTinyInteger('groupe_min')->nullable();
            $table->unsignedTinyInteger('groupe_max')->nullable();
            $table->string('langue')->nullable();
            $table->enum('difficulte', ['facile', 'moderee', 'difficile'])->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('evenement', function (Blueprint $table) {
            $table->dropColumn(['itineraire', 'groupe_min', 'groupe_max', 'langue', 'difficulte']);
        });
    }
};
```

- [ ] **Step 6: Migration de conversion `date_debut`/`date_fin` en `datetime`**

`database/migrations/2026_09_19_100200_convert_date_debut_fin_to_datetime_on_evenement_table.php` :

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * date_debut/date_fin étaient des colonnes DATE (l'heure n'était jamais
 * stockée, seulement castée côté Eloquent en 'datetime' avec heure à
 * 00:00:00). Nécessaire pour afficher "Départ le 2 janvier 2027 à 12:00"
 * comme sur la référence eventravel.fr. doctrine/dbal n'est pas installé
 * dans ce projet : ->change() est indisponible, ALTER en SQL brut comme
 * pour convert_status_to_enum_on_site_table.php (même pattern déjà en
 * place).
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::statement('ALTER TABLE evenement MODIFY date_debut DATETIME NOT NULL');
        DB::statement('ALTER TABLE evenement MODIFY date_fin DATETIME NOT NULL');
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE evenement MODIFY date_debut DATE NOT NULL');
        DB::statement('ALTER TABLE evenement MODIFY date_fin DATE NOT NULL');
    }
};
```

- [ ] **Step 7: Mettre à jour `$fillable`/`$casts` sur `app/Models/Evenement.php`**

Dans `$fillable`, ajouter après `'description',` :

```php
        'points_forts',
        'inclus',
        'non_inclus',
        'infos_pratiques',
        'recommandations',
        'itineraire',
        'groupe_min',
        'groupe_max',
        'langue',
        'difficulte',
```

Dans `$casts` (déjà `date_debut`/`date_fin` en `datetime`), ajouter :

```php
        'points_forts' => 'array',
        'inclus' => 'array',
        'non_inclus' => 'array',
        'itineraire' => 'array',
```

- [ ] **Step 8: Mettre à jour la validation dans `EvenementController::store` (ligne ~244)**

Ajouter dans le tableau de règles, après `"id_region" => "nullable|exists:region,id",` :

```php
            "points_forts" => "nullable|array",
            "points_forts.*" => "string|max:200",
            "inclus" => "nullable|array",
            "inclus.*" => "string|max:200",
            "non_inclus" => "nullable|array",
            "non_inclus.*" => "string|max:200",
            "infos_pratiques" => "nullable|string",
            "recommandations" => "nullable|string",
            "itineraire" => "nullable|array",
            "itineraire.*.titre" => "required_with:itineraire|string|max:150",
            "itineraire.*.description" => "required_with:itineraire|string",
            "groupe_min" => "nullable|integer|min:1",
            "groupe_max" => "nullable|integer|min:1|gte:groupe_min",
            "langue" => "nullable|string|max:100",
            "difficulte" => "nullable|in:facile,moderee,difficile",
```

- [ ] **Step 9: Même ajout dans `EvenementController::update` (ligne ~406)**

Coller exactement les mêmes lignes de validation (avec `sometimes` déjà implicite car tout est `nullable` — pas de `required` dans ce bloc, donc identique au `store` sauf que `date_debut`/`date_fin` restent `sometimes` comme déjà en place, non touchés par cette tâche).

- [ ] **Step 10: Lancer les tests, vérifier qu'ils passent**

Run: `docker exec benin_tourisme_app php artisan test --filter=ContenuEnrichiEntitesTest`
Expected: PASS (les 3 tests du fichier)

- [ ] **Step 11: Suite complète**

Run: `docker exec benin_tourisme_app php artisan test`
Expected: tous PASS.

- [ ] **Step 12: Commit**

```bash
git add database/migrations/2026_09_19_100100_add_champs_specifiques_to_evenement_table.php \
        database/migrations/2026_09_19_100200_convert_date_debut_fin_to_datetime_on_evenement_table.php \
        database/factories/CatEvenmtFactory.php database/factories/EvenementFactory.php \
        app/Models/CatEvenmt.php app/Models/Evenement.php \
        app/Http/Controllers/EvenementController.php \
        tests/Feature/ContenuEnrichiEntitesTest.php
git commit -m "feat(contenu-enrichi): Evenement - itinéraire, groupe, langue, difficulté + date_debut/fin en datetime"
```

---

### Task 3: `Site` — champs spécifiques (durée de visite, difficulté)

**Files:**
- Create: `database/migrations/2026_09_19_100300_add_champs_specifiques_to_site_table.php`
- Modify: `app/Models/Site.php` (`$fillable`)
- Modify: `app/Http/Controllers/SiteController.php:226-237` (store) et `:377-388` (update)
- Test: `tests/Feature/ContenuEnrichiEntitesTest.php`

**Interfaces:**
- Consumes: `Site::factory()` (Task 1)
- Produces: colonnes `site.duree_visite` (string nullable), `site.difficulte` (enum nullable, mêmes valeurs que `evenement.difficulte`)

- [ ] **Step 1: Écrire le test qui échoue**

```php
    public function test_site_round_trips_duree_visite_et_difficulte(): void
    {
        $site = Site::factory()->create([
            'duree_visite' => '2h',
            'difficulte' => 'facile',
        ]);

        $fresh = $site->fresh();

        $this->assertSame('2h', $fresh->duree_visite);
        $this->assertSame('facile', $fresh->difficulte);
    }
```

- [ ] **Step 2: Run, vérifier l'échec**

Run: `docker exec benin_tourisme_app php artisan test --filter=test_site_round_trips_duree_visite_et_difficulte`
Expected: FAIL (colonnes inexistantes)

- [ ] **Step 3: Migration**

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('site', function (Blueprint $table) {
            $table->string('duree_visite')->nullable();
            $table->enum('difficulte', ['facile', 'moderee', 'difficile'])->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('site', function (Blueprint $table) {
            $table->dropColumn(['duree_visite', 'difficulte']);
        });
    }
};
```

- [ ] **Step 4: `$fillable` sur `Site`**

Ajouter `'duree_visite',` et `'difficulte',` à la suite des champs ajoutés en Task 1.

- [ ] **Step 5: Validation `SiteController::store`**

Ajouter après `"id_region" => "nullable|exists:region,id",` :

```php
            "points_forts" => "nullable|array",
            "points_forts.*" => "string|max:200",
            "inclus" => "nullable|array",
            "inclus.*" => "string|max:200",
            "non_inclus" => "nullable|array",
            "non_inclus.*" => "string|max:200",
            "infos_pratiques" => "nullable|string",
            "recommandations" => "nullable|string",
            "duree_visite" => "nullable|string|max:100",
            "difficulte" => "nullable|in:facile,moderee,difficile",
```

- [ ] **Step 6: Même bloc dans `SiteController::update`**

Coller les mêmes lignes.

- [ ] **Step 7: Run tests**

Run: `docker exec benin_tourisme_app php artisan test`
Expected: PASS (tous)

- [ ] **Step 8: Commit**

```bash
git add database/migrations/2026_09_19_100300_add_champs_specifiques_to_site_table.php \
        app/Models/Site.php app/Http/Controllers/SiteController.php \
        tests/Feature/ContenuEnrichiEntitesTest.php
git commit -m "feat(contenu-enrichi): Site - durée de visite, difficulté, champs communs exposés en API"
```

---

### Task 4: `Hotel` — champs communs + spécifiques (check-in/check-out)

**Files:**
- Create: `database/migrations/2026_09_19_100400_add_champs_specifiques_to_hotel_table.php`
- Create: `database/factories/HotelFactory.php`
- Modify: `app/Models/Hotel.php` (`HasFactory`, `$fillable`, `$casts`)
- Modify: `app/Http/Controllers/HotelController.php:128-137` (store) et `:209-218` (update)
- Test: `tests/Feature/ContenuEnrichiEntitesTest.php`

**Interfaces:**
- Produces: `Hotel::factory()`. Colonnes `hotel.heure_arrivee`/`heure_depart` (time nullable), + les 5 colonnes communes de Task 1 exposées côté modèle/API (la table les a déjà, seul `Hotel` n'était pas encore mis à jour).

- [ ] **Step 1: Écrire le test qui échoue**

```php
    public function test_hotel_round_trips_contenu_enrichi_et_heures_arrivee_depart(): void
    {
        $hotel = \App\Models\Hotel::factory()->create([
            'points_forts' => ['Piscine', 'Vue mer'],
            'heure_arrivee' => '14:00',
            'heure_depart' => '11:00',
        ]);

        $fresh = $hotel->fresh();

        $this->assertSame(['Piscine', 'Vue mer'], $fresh->points_forts);
        $this->assertSame('14:00:00', $fresh->heure_arrivee);
        $this->assertSame('11:00:00', $fresh->heure_depart);
    }
```

- [ ] **Step 2: Run, vérifier l'échec**

Run: `docker exec benin_tourisme_app php artisan test --filter=test_hotel_round_trips_contenu_enrichi_et_heures_arrivee_depart`
Expected: FAIL (pas de factory `Hotel`)

- [ ] **Step 3: `HotelFactory`**

```php
<?php

namespace Database\Factories;

use App\Models\Admin;
use App\Models\Hotel;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Hotel>
 */
class HotelFactory extends Factory
{
    protected $model = Hotel::class;

    public function definition(): array
    {
        return [
            'libelle' => fake()->unique()->company(),
            'adresse' => fake()->address(),
            'longitude' => fake()->longitude(1, 3),
            'latitude' => fake()->latitude(6, 12),
            'description' => fake()->sentence(),
            'nombre_etoiles' => fake()->numberBetween(1, 5),
            'status' => 'valide',
            'id_admin' => Admin::factory(),
        ];
    }
}
```

- [ ] **Step 4: Activer `HasFactory` sur `app/Models/Hotel.php`**

```php
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Hotel extends Model
{
    use HasFactory;

    protected $table = 'hotel';
```

- [ ] **Step 5: Migration champs spécifiques Hôtel**

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('hotel', function (Blueprint $table) {
            $table->time('heure_arrivee')->nullable();
            $table->time('heure_depart')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('hotel', function (Blueprint $table) {
            $table->dropColumn(['heure_arrivee', 'heure_depart']);
        });
    }
};
```

- [ ] **Step 6: `$fillable`/`$casts` sur `Hotel`**

Ajouter au `$fillable` (après `'description',`) :

```php
        'points_forts',
        'inclus',
        'non_inclus',
        'infos_pratiques',
        'recommandations',
        'heure_arrivee',
        'heure_depart',
```

`Hotel` n'a pas de `$casts` actuellement — en ajouter un :

```php
    protected $casts = [
        'points_forts' => 'array',
        'inclus' => 'array',
        'non_inclus' => 'array',
    ];
```

- [ ] **Step 7: Validation `HotelController::store`**

Ajouter après `"id_region" => "nullable|exists:region,id",` :

```php
            "points_forts" => "nullable|array",
            "points_forts.*" => "string|max:200",
            "inclus" => "nullable|array",
            "inclus.*" => "string|max:200",
            "non_inclus" => "nullable|array",
            "non_inclus.*" => "string|max:200",
            "infos_pratiques" => "nullable|string",
            "recommandations" => "nullable|string",
            "heure_arrivee" => "nullable|date_format:H:i",
            "heure_depart" => "nullable|date_format:H:i",
```

- [ ] **Step 8: Même bloc dans `HotelController::update`**

- [ ] **Step 9: Run tests, suite complète**

Run: `docker exec benin_tourisme_app php artisan test`
Expected: PASS (tous)

- [ ] **Step 10: Commit**

```bash
git add database/migrations/2026_09_19_100400_add_champs_specifiques_to_hotel_table.php \
        database/factories/HotelFactory.php app/Models/Hotel.php \
        app/Http/Controllers/HotelController.php tests/Feature/ContenuEnrichiEntitesTest.php
git commit -m "feat(contenu-enrichi): Hotel - contenu enrichi + heures d'arrivée/départ"
```

---

### Task 5: `Restaurant` — champs communs + spécifiques (horaires)

**Files:**
- Create: `database/migrations/2026_09_19_100500_add_champs_specifiques_to_restaurant_table.php`
- Create: `database/factories/RestaurantFactory.php`
- Modify: `app/Models/Restaurant.php` (`HasFactory`, `$fillable`, `$casts`)
- Modify: `app/Http/Controllers/RestaurantController.php:132-142` (store) et `:214-224` (update)
- Test: `tests/Feature/ContenuEnrichiEntitesTest.php`

**Interfaces:**
- Produces: `Restaurant::factory()`. Colonne `restaurant.horaires` (string nullable) + colonnes communes exposées.

- [ ] **Step 1: Écrire le test qui échoue**

```php
    public function test_restaurant_round_trips_contenu_enrichi_et_horaires(): void
    {
        $restaurant = \App\Models\Restaurant::factory()->create([
            'inclus' => ['Menu dégustation'],
            'horaires' => '12h-15h, 19h-23h',
        ]);

        $fresh = $restaurant->fresh();

        $this->assertSame(['Menu dégustation'], $fresh->inclus);
        $this->assertSame('12h-15h, 19h-23h', $fresh->horaires);
    }
```

- [ ] **Step 2: Run, vérifier l'échec**

Run: `docker exec benin_tourisme_app php artisan test --filter=test_restaurant_round_trips_contenu_enrichi_et_horaires`
Expected: FAIL

- [ ] **Step 3: `RestaurantFactory`**

```php
<?php

namespace Database\Factories;

use App\Models\Admin;
use App\Models\Restaurant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Restaurant>
 */
class RestaurantFactory extends Factory
{
    protected $model = Restaurant::class;

    public function definition(): array
    {
        return [
            'libelle' => fake()->unique()->company(),
            'adresse' => fake()->address(),
            'longitude' => fake()->longitude(1, 3),
            'latitude' => fake()->latitude(6, 12),
            'description' => fake()->sentence(),
            'type_cuisine' => 'Béninoise',
            'gamme_prix' => 'moyen',
            'status' => 'valide',
            'id_admin' => Admin::factory(),
        ];
    }
}
```

- [ ] **Step 4: Activer `HasFactory` sur `app/Models/Restaurant.php`**

Même pattern que Task 4, Step 4.

- [ ] **Step 5: Migration champs spécifiques Restaurant**

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('restaurant', function (Blueprint $table) {
            $table->string('horaires')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('restaurant', function (Blueprint $table) {
            $table->dropColumn('horaires');
        });
    }
};
```

- [ ] **Step 6: `$fillable`/`$casts` sur `Restaurant`**

Ajouter au `$fillable` : `'points_forts', 'inclus', 'non_inclus', 'infos_pratiques', 'recommandations', 'horaires',`. Ajouter `$casts` comme pour `Hotel` (Task 4, Step 6).

- [ ] **Step 7: Validation `RestaurantController::store` puis `update`**

Ajouter après `"id_region" => "nullable|exists:region,id",` dans les deux méthodes :

```php
            "points_forts" => "nullable|array",
            "points_forts.*" => "string|max:200",
            "inclus" => "nullable|array",
            "inclus.*" => "string|max:200",
            "non_inclus" => "nullable|array",
            "non_inclus.*" => "string|max:200",
            "infos_pratiques" => "nullable|string",
            "recommandations" => "nullable|string",
            "horaires" => "nullable|string|max:255",
```

- [ ] **Step 8: Run tests, suite complète**

Run: `docker exec benin_tourisme_app php artisan test`
Expected: PASS

- [ ] **Step 9: Commit**

```bash
git add database/migrations/2026_09_19_100500_add_champs_specifiques_to_restaurant_table.php \
        database/factories/RestaurantFactory.php app/Models/Restaurant.php \
        app/Http/Controllers/RestaurantController.php tests/Feature/ContenuEnrichiEntitesTest.php
git commit -m "feat(contenu-enrichi): Restaurant - contenu enrichi + horaires"
```

---

### Task 6: `Transport` — champs communs + spécifiques (durée de trajet estimée)

**Files:**
- Create: `database/migrations/2026_09_19_100600_add_champs_specifiques_to_transport_table.php`
- Create: `database/factories/TransportFactory.php`
- Modify: `app/Models/Transport.php` (`HasFactory`, `$fillable`, `$casts`)
- Modify: `app/Http/Controllers/TransportController.php:129-139` (store) et `:211-221` (update)
- Test: `tests/Feature/ContenuEnrichiEntitesTest.php`

**Interfaces:**
- Produces: `Transport::factory()`. Colonne `transport.duree_trajet_estimee` (string nullable). Ne duplique pas `capacite` (déjà existant sur `transport`) avec un champ "groupe".

- [ ] **Step 1: Écrire le test qui échoue**

```php
    public function test_transport_round_trips_contenu_enrichi_et_duree_trajet(): void
    {
        $transport = \App\Models\Transport::factory()->create([
            'non_inclus' => ['Bagages en soute'],
            'duree_trajet_estimee' => '45 min',
        ]);

        $fresh = $transport->fresh();

        $this->assertSame(['Bagages en soute'], $fresh->non_inclus);
        $this->assertSame('45 min', $fresh->duree_trajet_estimee);
    }
```

- [ ] **Step 2: Run, vérifier l'échec**

Run: `docker exec benin_tourisme_app php artisan test --filter=test_transport_round_trips_contenu_enrichi_et_duree_trajet`
Expected: FAIL

- [ ] **Step 3: `TransportFactory`**

```php
<?php

namespace Database\Factories;

use App\Models\Admin;
use App\Models\Transport;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Transport>
 */
class TransportFactory extends Factory
{
    protected $model = Transport::class;

    public function definition(): array
    {
        return [
            'libelle' => fake()->unique()->company(),
            'adresse' => fake()->address(),
            'longitude' => fake()->longitude(1, 3),
            'latitude' => fake()->latitude(6, 12),
            'description' => fake()->sentence(),
            'type_transport' => 'Bus',
            'capacite' => fake()->numberBetween(4, 50),
            'status' => 'valide',
            'id_admin' => Admin::factory(),
        ];
    }
}
```

- [ ] **Step 4: Activer `HasFactory` sur `app/Models/Transport.php`**

Même pattern que Task 4, Step 4.

- [ ] **Step 5: Migration champs spécifiques Transport**

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('transport', function (Blueprint $table) {
            $table->string('duree_trajet_estimee')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('transport', function (Blueprint $table) {
            $table->dropColumn('duree_trajet_estimee');
        });
    }
};
```

- [ ] **Step 6: `$fillable`/`$casts` sur `Transport`**

Ajouter au `$fillable` : `'points_forts', 'inclus', 'non_inclus', 'infos_pratiques', 'recommandations', 'duree_trajet_estimee',`. `$casts` comme Task 4 Step 6.

- [ ] **Step 7: Validation `TransportController::store` puis `update`**

Ajouter après `"id_region" => "nullable|exists:region,id",` dans les deux méthodes :

```php
            "points_forts" => "nullable|array",
            "points_forts.*" => "string|max:200",
            "inclus" => "nullable|array",
            "inclus.*" => "string|max:200",
            "non_inclus" => "nullable|array",
            "non_inclus.*" => "string|max:200",
            "infos_pratiques" => "nullable|string",
            "recommandations" => "nullable|string",
            "duree_trajet_estimee" => "nullable|string|max:100",
```

- [ ] **Step 8: Run tests, suite complète**

Run: `docker exec benin_tourisme_app php artisan test`
Expected: PASS

- [ ] **Step 9: Commit**

```bash
git add database/migrations/2026_09_19_100600_add_champs_specifiques_to_transport_table.php \
        database/factories/TransportFactory.php app/Models/Transport.php \
        app/Http/Controllers/TransportController.php tests/Feature/ContenuEnrichiEntitesTest.php
git commit -m "feat(contenu-enrichi): Transport - contenu enrichi + durée de trajet estimée"
```

---

### Task 7: Migration en dev, Swagger, vérification HTTP réelle, ROADMAP

**Files:**
- Modify: `storage/api-docs/api-docs.json` (régénéré, pas édité à la main)
- Modify: `ROADMAP.md`

**Interfaces:**
- Consumes: toutes les tâches précédentes (migrations 1 à 6, tous les controllers).

- [ ] **Step 1: Appliquer les migrations sur la base de dev**

Run: `docker exec benin_tourisme_app php artisan migrate --force`
Expected: les 6 nouvelles migrations listées `Ran`, aucune erreur (`ALTER TABLE evenement MODIFY ...` inclus).

- [ ] **Step 2: Régénérer Swagger**

Run: `docker exec benin_tourisme_app php artisan l5-swagger:generate`
Expected: `Regenerating docs default`, aucune erreur.

- [ ] **Step 3: Vérification HTTP réelle sur Site et Evenement (au minimum)**

Se connecter en admin (`+22901000000`/`admin123`, reseed via `docker exec benin_tourisme_app php artisan db:seed --force` si le compte a disparu — comportement déjà documenté dans le ROADMAP), créer un Site avec `points_forts`/`duree_visite`/`difficulte`, un Événement avec `itineraire`/`groupe_min`/`groupe_max`/`langue`/`difficulte` et un `date_debut` avec heure (`"2027-01-02 12:00:00"`), vérifier via `GET` que les champs reviennent identiques et que `date_debut` inclut bien `12:00:00`. Supprimer les données de test après vérification (`php artisan tinker`), comme documenté dans le ROADMAP.

- [ ] **Step 4: Mettre à jour `ROADMAP.md`**

Ajouter sous la section "Module Fiches détail enrichies + Témoignages plateforme (2026-09-18)" déjà présente (créée lors du brainstorming) :

```markdown
- [x] Chantier 1 (backend) : migrations + modèles + validation pour points_forts/inclus/non_inclus/infos_pratiques/recommandations sur les 5 entités, + champs spécifiques (Événement : itinéraire/groupe/langue/difficulté + date_debut/fin en datetime ; Site : durée de visite/difficulté ; Hôtel : heures arrivée/départ ; Restaurant : horaires ; Transport : durée de trajet estimée). Factories ajoutées pour Evenement/CatEvenmt/Hotel/Restaurant/Transport (n'existaient pas). Vérifié : X tests backend verts, migration appliquée en dev, vérification HTTP réelle sur Site et Evenement. Plan frontend (composants partagés + câblage formulaires + pages détail) à écrire séparément.
```

Remplacer `X` par le nombre réel de tests obtenu à l'exécution de `docker exec benin_tourisme_app php artisan test` (Step 1 de cette tâche) avant de commit.

- [ ] **Step 5: Commit**

```bash
git add ROADMAP.md storage/api-docs/api-docs.json
git commit -m "docs(roadmap): coche le chantier 1 backend (contenu enrichi 5 entités)"
```

---

## Suite

Une fois ce plan exécuté et vérifié, deux plans séparés restent à écrire avant l'implémentation frontend :
1. **Composants partagés + câblage formulaires** (`LocationPicker`, `TagListInput`, `ItineraryEditor`, intégration dans les 15 formulaires Admin/Prestataire/Responsable × 5 entités).
2. **Pages détail publiques** (hero enrichi + nouvelles sections sur `SiteDetail.jsx`/`EvenementDetail.jsx`/`HotelDetail.jsx`/`RestaurantDetail.jsx`/`TransportDetail.jsx`).

Le chantier 3 (Témoignages plateforme) est indépendant et peut être planifié à part, dans n'importe quel ordre.
