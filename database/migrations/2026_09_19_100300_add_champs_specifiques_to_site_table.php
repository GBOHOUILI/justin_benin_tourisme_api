<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('site', function (Blueprint $table) {
            $table->string('duree_visite')->nullable();
            $table->enum('difficulte', ['facile', 'moderee', 'difficile'])->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('site', function (Blueprint $table) {
            $table->dropColumn(['duree_visite', 'difficulte']);
        });
    }
};
