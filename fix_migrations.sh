#!/bin/bash
# À exécuter depuis la racine du projet Laravel
# bash fix_migrations.sh

MDIR="database/migrations"

# ─── admin ────────────────────────────────────────────────────
cat > $MDIR/2026_03_18_143052_create_admin_table.php << 'PHP'
<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('admin', function (Blueprint $table) {
            $table->id();
            $table->string('nom');
            $table->string('prenom');
            $table->string('tel')->unique();
            $table->string('password');
            $table->boolean('status')->default(1);
            $table->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('admin'); }
};
PHP

# ─── cat_evenmt ───────────────────────────────────────────────
cat > $MDIR/2026_03_18_143052_create_cat_evenmt_table.php << 'PHP'
<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('cat_evenmt', function (Blueprint $table) {
            $table->id();
            $table->string('libelle');
            $table->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('cat_evenmt'); }
};
PHP

# ─── cat_site ─────────────────────────────────────────────────
cat > $MDIR/2026_03_18_143052_create_cat_site_table.php << 'PHP'
<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('cat_site', function (Blueprint $table) {
            $table->id();
            $table->string('libelle');
            $table->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('cat_site'); }
};
PHP

# ─── fonctionnalite ───────────────────────────────────────────
cat > $MDIR/2026_03_18_143053_create_fonctionnalite_table.php << 'PHP'
<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('fonctionnalite', function (Blueprint $table) {
            $table->id();
            $table->string('libelle');
            $table->string('type');
            $table->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('fonctionnalite'); }
};
PHP

# ─── evenement ────────────────────────────────────────────────
cat > $MDIR/2026_03_18_143053_create_evenement_table.php << 'PHP'
<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('evenement', function (Blueprint $table) {
            $table->id();
            $table->string('libelle');
            $table->string('adresse');
            $table->decimal('longitude', 10, 7)->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->text('description')->nullable();
            $table->date('date_debut');
            $table->date('date_fin');
            $table->enum('status', ['en_attente', 'valide', 'rejete', 'suspendu'])->default('en_attente');
            $table->foreignId('id_cat_evenmt')->constrained('cat_evenmt');
            $table->foreignId('id_admin')->constrained('admin');
            $table->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('evenement'); }
};
PHP

# ─── site ─────────────────────────────────────────────────────
cat > $MDIR/2026_03_18_143053_create_site_table.php << 'PHP'
<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('site', function (Blueprint $table) {
            $table->id();
            $table->string('libelle');
            $table->string('adresse');
            $table->decimal('longitude', 10, 7)->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->text('description')->nullable();
            $table->time('ouverture')->nullable();
            $table->time('fermeture')->nullable();
            $table->boolean('status')->default(1);
            $table->foreignId('id_cat_site')->constrained('cat_site');
            $table->foreignId('id_admin')->constrained('admin');
            $table->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('site'); }
};
PHP

# ─── disposer ─────────────────────────────────────────────────
cat > $MDIR/2026_03_18_143058_create_disposer_table.php << 'PHP'
<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('disposer', function (Blueprint $table) {
            $table->foreignId('id_evnmt')->constrained('evenement')->cascadeOnDelete();
            $table->foreignId('id_site')->constrained('site')->cascadeOnDelete();
            $table->primary(['id_evnmt', 'id_site']);
            $table->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('disposer'); }
};
PHP

# ─── gallerie_evnmt ───────────────────────────────────────────
cat > $MDIR/2026_03_18_143059_create_gallerie_evnmt_table.php << 'PHP'
<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('gallerie_evnmt', function (Blueprint $table) {
            $table->id();
            $table->string('libelle');
            $table->string('type');
            $table->boolean('status')->default(1);
            $table->foreignId('id_evnmt')->constrained('evenement')->cascadeOnDelete();
            $table->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('gallerie_evnmt'); }
};
PHP

# ─── galerie_site ─────────────────────────────────────────────
cat > $MDIR/2026_03_18_143059_create_gallerie_site_table.php << 'PHP'
<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('galerie_site', function (Blueprint $table) {
            $table->id();
            $table->string('libelle');
            $table->string('type');
            $table->boolean('status')->default(1);
            $table->foreignId('id_site')->constrained('site')->cascadeOnDelete();
            $table->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('galerie_site'); }
};
PHP

# ─── reservation ──────────────────────────────────────────────
cat > $MDIR/2026_03_18_143060_create_reservation_table.php << 'PHP'
<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('reservation', function (Blueprint $table) {
            $table->id();
            $table->string('type');
            $table->decimal('prix', 10, 2);
            $table->integer('nombre');
            $table->decimal('total', 12, 2);
            $table->text('description')->nullable();
            $table->foreignId('id_site')->nullable()->constrained('site')->nullOnDelete();
            $table->foreignId('id_evnmt')->nullable()->constrained('evenement')->nullOnDelete();
            $table->foreignId('id_user')->constrained('users')->cascadeOnDelete();
            $table->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('reservation'); }
};
PHP

# ─── ticket ───────────────────────────────────────────────────
cat > $MDIR/2026_03_18_143061_create_ticket_table.php << 'PHP'
<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('ticket', function (Blueprint $table) {
            $table->id();
            $table->string('numero')->unique();
            $table->foreignId('id_reservation')->constrained('reservation')->cascadeOnDelete();
            $table->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('ticket'); }
};
PHP

# ─── utilisation ──────────────────────────────────────────────
cat > $MDIR/2026_03_18_143062_create_utilisation_table.php << 'PHP'
<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('utilisation', function (Blueprint $table) {
            $table->id();
            $table->date('date_visite');
            $table->time('heure');
            $table->foreignId('id_ticket')->constrained('ticket')->cascadeOnDelete();
            $table->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('utilisation'); }
};
PHP

# ─── avis ─────────────────────────────────────────────────────
cat > $MDIR/2026_03_18_143063_create_avis_table.php << 'PHP'
<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('avis', function (Blueprint $table) {
            $table->id();
            $table->text('message');
            $table->enum('status', ['en_attente', 'approuve', 'rejete'])->default('en_attente');
            $table->foreignId('id_utilisation')->unique()->constrained('utilisation')->cascadeOnDelete();
            $table->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('avis'); }
};
PHP

# ─── acceder ──────────────────────────────────────────────────
cat > $MDIR/2026_03_18_143064_create_acceder_table.php << 'PHP'
<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('acceder', function (Blueprint $table) {
            $table->foreignId('id_admin')->constrained('admin')->cascadeOnDelete();
            $table->foreignId('id_fonc')->constrained('fonctionnalite')->cascadeOnDelete();
            $table->boolean('status')->default(1);
            $table->primary(['id_admin', 'id_fonc']);
        });
    }
    public function down(): void { Schema::dropIfExists('acceder'); }
};
PHP

# ─── voir ─────────────────────────────────────────────────────
cat > $MDIR/2026_03_18_143065_create_voir_table.php << 'PHP'
<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('voir', function (Blueprint $table) {
            $table->foreignId('id_user')->constrained('users')->cascadeOnDelete();
            $table->foreignId('id_fonc')->constrained('fonctionnalite')->cascadeOnDelete();
            $table->boolean('status')->default(1);
            $table->primary(['id_user', 'id_fonc']);
        });
    }
    public function down(): void { Schema::dropIfExists('voir'); }
};
PHP

# ─── prix ─────────────────────────────────────────────────────
cat > $MDIR/2026_03_24_050520_create_prix_table.php << 'PHP'
<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('prix', function (Blueprint $table) {
            $table->id();
            $table->string('libelle');
            $table->decimal('montant', 10, 2);
            $table->foreignId('id_site')->nullable()->constrained('site')->nullOnDelete();
            $table->foreignId('id_evnmt')->nullable()->constrained('evenement')->nullOnDelete();
            $table->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('prix'); }
};
PHP

echo "✅ Toutes les migrations ont été corrigées !"
echo "Lance maintenant : docker exec -it benin_tourisme_app php artisan migrate:refresh --seed"