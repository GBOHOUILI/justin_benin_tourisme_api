<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('evenement', function (Blueprint $table) {
            $table->json('itineraire')->nullable();
            $table->unsignedTinyInteger('groupe_min')->nullable();
            $table->unsignedTinyInteger('groupe_max')->nullable();
            $table->string('langue')->nullable();
            $table->enum('difficulte', ['facile', 'moderee', 'difficile'])->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('evenement', function (Blueprint $table) {
            $table->dropColumn(['itineraire', 'groupe_min', 'groupe_max', 'langue', 'difficulte']);
        });
    }
};
