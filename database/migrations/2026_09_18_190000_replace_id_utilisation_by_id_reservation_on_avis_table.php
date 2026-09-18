<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Décision produit (2026-09-18, validée avec l'utilisateur) : un avis
 * exigeait jusqu'ici une Utilisation (visite scannée par un admin/staff sur
 * place) - un goulot d'étranglement opérationnel qui bloquait la quasi-
 * totalité des avis en pratique. Remplacé par une exigence de Reservation
 * confirmee (déjà vérifiable sans action manuelle du staff).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('avis', function (Blueprint $table) {
            $table->dropForeign(['id_utilisation']);
            $table->dropColumn('id_utilisation');
            $table->foreignId('id_reservation')->unique()->after('id')
                ->constrained('reservation')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('avis', function (Blueprint $table) {
            $table->dropForeign(['id_reservation']);
            $table->dropColumn('id_reservation');
            $table->foreignId('id_utilisation')->unique()->after('id')
                ->constrained('utilisation')->cascadeOnDelete();
        });
    }
};
