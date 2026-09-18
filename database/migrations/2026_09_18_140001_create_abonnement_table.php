<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('abonnement', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_prestataire')->constrained('prestataire')->cascadeOnDelete();
            $table->foreignId('id_plan')->constrained('plan')->restrictOnDelete();
            $table->date('date_debut')->nullable();
            $table->date('date_fin')->nullable();
            $table->enum('statut', ['en_attente', 'actif', 'expire', 'annule'])->default('en_attente');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('abonnement');
    }
};
