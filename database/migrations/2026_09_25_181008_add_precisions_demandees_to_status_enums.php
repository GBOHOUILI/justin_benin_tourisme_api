<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Ajoute la valeur d'enum "precisions_demandees" (entre en_attente et rejete
 * dans le workflow : le responsable régional ne rejette pas d'emblée, il
 * peut demander un complément d'information au prestataire) et une colonne
 * commentaire_responsable (le message expliquant ce qui manque) sur les 5
 * tables qui partagent ce workflow de validation.
 * MODIFY plutôt qu'un ADD COLUMN + copie : on ajoute une valeur d'enum sans
 * toucher aux valeurs existantes, pas de conversion de type ici (contrairement
 * à 2026_09_18_064229_convert_status_to_enum_on_site_table.php).
 */
return new class extends Migration
{
    private array $tables = ['site', 'evenement', 'hotel', 'restaurant', 'transport'];

    public function up(): void
    {
        foreach ($this->tables as $table) {
            DB::statement("ALTER TABLE {$table} MODIFY status ENUM('en_attente','valide','rejete','suspendu','precisions_demandees') NOT NULL DEFAULT 'en_attente'");

            if (! Schema::hasColumn($table, 'commentaire_responsable')) {
                Schema::table($table, function (Blueprint $t) {
                    $t->text('commentaire_responsable')->nullable()->after('status');
                });
            }
        }
    }

    public function down(): void
    {
        foreach ($this->tables as $table) {
            if (Schema::hasColumn($table, 'commentaire_responsable')) {
                Schema::table($table, function (Blueprint $t) {
                    $t->dropColumn('commentaire_responsable');
                });
            }

            DB::statement("ALTER TABLE {$table} MODIFY status ENUM('en_attente','valide','rejete','suspendu') NOT NULL DEFAULT 'en_attente'");
        }
    }
};
