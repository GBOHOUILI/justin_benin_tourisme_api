<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('restaurant', function (Blueprint $table) {
            $table->id();
            $table->string('libelle');
            $table->string('adresse');
            $table->decimal('longitude', 10, 7)->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->text('description')->nullable();
            $table->string('type_cuisine')->nullable();
            $table->enum('gamme_prix', ['economique', 'moyen', 'eleve'])->nullable();
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
        Schema::dropIfExists('restaurant');
    }
};
