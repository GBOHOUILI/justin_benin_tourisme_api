<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('favori', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_user')->constrained('users')->cascadeOnDelete();
            $table->foreignId('id_site')->nullable()->constrained('site')->cascadeOnDelete();
            $table->foreignId('id_evnmt')->nullable()->constrained('evenement')->cascadeOnDelete();
            $table->foreignId('id_hotel')->nullable()->constrained('hotel')->cascadeOnDelete();
            $table->foreignId('id_restaurant')->nullable()->constrained('restaurant')->cascadeOnDelete();
            $table->foreignId('id_transport')->nullable()->constrained('transport')->cascadeOnDelete();
            $table->timestamps();

            // Un même utilisateur ne peut pas favoriter deux fois la même fiche
            // (une seule des 5 colonnes id_* est non-nulle par ligne, cf.
            // FavoriController::store - MySQL traite chaque combinaison de NULL
            // comme distincte, donc cet index composite suffit).
            $table->unique(
                ['id_user', 'id_site', 'id_evnmt', 'id_hotel', 'id_restaurant', 'id_transport'],
                'favori_unique_par_utilisateur'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('favori');
    }
};
