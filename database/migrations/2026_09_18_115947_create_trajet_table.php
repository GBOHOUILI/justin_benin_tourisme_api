<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('trajet', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_transport')->constrained('transport')->cascadeOnDelete();
            $table->foreignId('id_ville_depart')->constrained('ville')->restrictOnDelete();
            $table->foreignId('id_ville_arrivee')->constrained('ville')->restrictOnDelete();
            $table->time('horaire_depart');
            $table->decimal('prix', 10, 2);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('trajet');
    }
};
