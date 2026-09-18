<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Ville (commune) — distincte de Region (département) : granularité fine
 * nécessaire pour un Trajet ("Cotonou → Parakou"), contrairement au
 * Responsable régional qui supervise au niveau département. Liste ouverte
 * (contrairement aux 12 régions fixes), gérée par les admins au fil de l'eau.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ville', function (Blueprint $table) {
            $table->id();
            $table->string('nom');
            $table->foreignId('id_region')->nullable()->constrained('region')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ville');
    }
};
