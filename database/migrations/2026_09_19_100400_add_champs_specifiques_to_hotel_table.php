<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('hotel', function (Blueprint $table) {
            $table->time('heure_arrivee')->nullable();
            $table->time('heure_depart')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('hotel', function (Blueprint $table) {
            $table->dropColumn(['heure_arrivee', 'heure_depart']);
        });
    }
};
