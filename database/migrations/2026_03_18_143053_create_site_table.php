<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('site', function (Blueprint $table) {
            $table->id();
            $table->string('libelle');
            $table->string('adresse');
            $table->decimal('longitude', 10, 7)->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->text('description')->nullable();
            $table->time('ouverture')->nullable();
            $table->time('fermeture')->nullable();
            $table->boolean('status')->default(1);
            $table->foreignId('id_cat_site')->constrained('cat_site');
            $table->foreignId('id_admin')->constrained('admin');
            $table->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('site'); }
};
