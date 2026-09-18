<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('transport', function (Blueprint $table) {
            $table->string('duree_trajet_estimee')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('transport', function (Blueprint $table) {
            $table->dropColumn('duree_trajet_estimee');
        });
    }
};
