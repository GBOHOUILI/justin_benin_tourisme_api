<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('chambre', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_hotel')->constrained('hotel')->cascadeOnDelete();
            $table->string('type_chambre');
            $table->decimal('prix_nuit', 10, 2);
            $table->unsignedSmallInteger('capacite')->default(1);
            $table->boolean('disponibilite')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('chambre');
    }
};
