<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('galerie_hotel', function (Blueprint $table) {
            $table->id();
            $table->string('libelle');
            $table->string('type');
            $table->string('url_fichier')->nullable();
            $table->boolean('status')->default(1);
            $table->foreignId('id_hotel')->constrained('hotel')->cascadeOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('galerie_hotel');
    }
};
