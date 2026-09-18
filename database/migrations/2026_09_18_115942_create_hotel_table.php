<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Même forme que `site` (dans son état final, pas sa forme d'origine) :
 * propriétaire (admin OU prestataire OU responsable, jamais deux à la fois),
 * statut à 4 états, région optionnelle. Pas de catégorie (id_cat_x) — le
 * document ne prévoit pas de taxonomie pour hôtel/restaurant/transport,
 * contrairement à site/événement.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hotel', function (Blueprint $table) {
            $table->id();
            $table->string('libelle');
            $table->string('adresse');
            $table->decimal('longitude', 10, 7)->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->text('description')->nullable();
            $table->unsignedTinyInteger('nombre_etoiles')->nullable();
            $table->enum('status', ['en_attente', 'valide', 'rejete', 'suspendu'])->default('en_attente');
            $table->foreignId('id_admin')->nullable()->constrained('admin')->nullOnDelete();
            $table->foreignId('id_prestataire')->nullable()->constrained('prestataire')->nullOnDelete();
            $table->foreignId('id_responsable')->nullable()->constrained('responsable_regional')->nullOnDelete();
            $table->foreignId('id_region')->nullable()->constrained('region')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hotel');
    }
};
