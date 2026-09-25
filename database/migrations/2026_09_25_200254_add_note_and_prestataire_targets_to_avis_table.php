<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * "Note chiffrée par service" (décision produit validée avec l'utilisateur,
 * option 2 : étendre - pas seulement ajouter une note à l'existant) :
 * - note (1-5) sur tout avis, Site/Evenement compris (rétroactif : nullable
 *   pour ne pas casser les avis déjà en base).
 * - Hotel/Restaurant/Transport n'ont aucune Reservation (contrairement à
 *   Site/Evenement - cf. `reservation` : seules `id_site`/`id_evnmt`
 *   existent) : impossible d'exiger une réservation confirmée comme preuve
 *   de visite pour ces 3-là sans construire tout un système de réservation
 *   au préalable, hors périmètre de cette fonctionnalité. id_reservation
 *   devient nullable et 3 nouvelles colonnes cible + id_user (poteur direct
 *   de l'avis, nécessaire puisqu'il n'y a plus de réservation pour retrouver
 *   l'auteur) accueillent des avis "ouverts" à tout touriste connecté sur
 *   ces 3 entités - moins strict que le contrôle "réservation confirmée"
 *   de Site/Evenement, seule option réaliste vu l'existant.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('avis', function (Blueprint $table) {
            $table->unsignedTinyInteger('note')->nullable()->after('message');
            $table->foreignId('id_user')->nullable()->after('id_reservation')->constrained('users')->cascadeOnDelete();
            $table->foreignId('id_hotel')->nullable()->after('id_user')->constrained('hotel')->cascadeOnDelete();
            $table->foreignId('id_restaurant')->nullable()->after('id_hotel')->constrained('restaurant')->cascadeOnDelete();
            $table->foreignId('id_transport')->nullable()->after('id_restaurant')->constrained('transport')->cascadeOnDelete();
            $table->unique(['id_user', 'id_hotel']);
            $table->unique(['id_user', 'id_restaurant']);
            $table->unique(['id_user', 'id_transport']);
        });

        // id_reservation était unique + NOT NULL - doit devenir nullable pour
        // les avis Hotel/Restaurant/Transport qui n'en ont pas.
        DB::statement('ALTER TABLE avis MODIFY id_reservation BIGINT UNSIGNED NULL');
    }

    public function down(): void
    {
        Schema::table('avis', function (Blueprint $table) {
            $table->dropUnique(['id_user', 'id_hotel']);
            $table->dropUnique(['id_user', 'id_restaurant']);
            $table->dropUnique(['id_user', 'id_transport']);
            $table->dropConstrainedForeignId('id_transport');
            $table->dropConstrainedForeignId('id_restaurant');
            $table->dropConstrainedForeignId('id_hotel');
            $table->dropConstrainedForeignId('id_user');
            $table->dropColumn('note');
        });

        DB::statement('ALTER TABLE avis MODIFY id_reservation BIGINT UNSIGNED NOT NULL');
    }
};
