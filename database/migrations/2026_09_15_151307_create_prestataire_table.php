<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Fiche minimale, sans compte ni flux d'inscription (module Prestataire à venir).
 * Peuplée pour l'instant via la commande artisan prestataire:create.
 */
return new class extends Migration {
    public function up(): void {
        Schema::create('prestataire', function (Blueprint $table) {
            $table->id();
            $table->string('nom_entreprise');
            $table->string('type_prestataire')->nullable();
            $table->boolean('status')->default(1);
            $table->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('prestataire'); }
};
