<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Compte créé par un Admin (pas d'auto-inscription — c'est un poste officiel,
 * même logique que Admin). id_region NULL = responsable global, valide
 * n'importe où (couvre les régions sans titulaire pour l'instant).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('responsable_regional', function (Blueprint $table) {
            $table->id();
            $table->string('nom');
            $table->string('prenom');
            $table->string('tel')->unique();
            $table->string('password');
            $table->boolean('status')->default(1);
            $table->foreignId('id_region')->nullable()->constrained('region')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('responsable_regional');
    }
};
