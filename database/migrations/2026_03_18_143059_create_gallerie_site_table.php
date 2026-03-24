<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('galerie_site', function (Blueprint $table) {
            $table->id();
            $table->string('libelle');
            $table->string('type');
            $table->boolean('status')->default(1);
            $table->foreignId('id_site')->constrained('site')->cascadeOnDelete();
            $table->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('galerie_site'); }
};
