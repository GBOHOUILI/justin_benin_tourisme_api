<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Un Responsable régional connaît son territoire - il peut créer un Site/
 * Evenement pour sa région, mais ne peut jamais le valider lui-même : seul
 * un Admin valide une fiche créée par un responsable (cf. id_prestataire :
 * même logique de créateur "en_attente" jamais auto-validé).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('site', function (Blueprint $table) {
            $table->foreignId('id_responsable')->nullable()->after('id_region')
                ->constrained('responsable_regional')->nullOnDelete();
        });

        Schema::table('evenement', function (Blueprint $table) {
            $table->foreignId('id_responsable')->nullable()->after('id_region')
                ->constrained('responsable_regional')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('site', function (Blueprint $table) {
            $table->dropConstrainedForeignId('id_responsable');
        });

        Schema::table('evenement', function (Blueprint $table) {
            $table->dropConstrainedForeignId('id_responsable');
        });
    }
};
