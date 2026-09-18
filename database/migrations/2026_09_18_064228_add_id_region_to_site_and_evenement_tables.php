<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('site', function (Blueprint $table) {
            $table->foreignId('id_region')->nullable()->after('id_prestataire')
                ->constrained('region')->nullOnDelete();
        });

        Schema::table('evenement', function (Blueprint $table) {
            $table->foreignId('id_region')->nullable()->after('id_prestataire')
                ->constrained('region')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('site', function (Blueprint $table) {
            $table->dropConstrainedForeignId('id_region');
        });

        Schema::table('evenement', function (Blueprint $table) {
            $table->dropConstrainedForeignId('id_region');
        });
    }
};
