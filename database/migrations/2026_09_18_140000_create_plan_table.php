<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('plan', function (Blueprint $table) {
            $table->id();
            $table->string('nom');
            $table->decimal('prix_mensuel', 10, 2);
            $table->unsignedInteger('nombre_fiches_max')->nullable();
            $table->json('fonctionnalites')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('plan');
    }
};
