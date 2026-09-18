<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('prestataire', function (Blueprint $table) {
            $table->string('email')->unique()->after('nom_entreprise');
            $table->string('tel')->nullable()->after('email');
            $table->string('password')->after('tel');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('prestataire', function (Blueprint $table) {
            $table->dropColumn(['email', 'tel', 'password']);
        });
    }
};
