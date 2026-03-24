<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('evenement', function (Blueprint $table) {
            $table->id();
            $table->string('libelle');
            $table->string('adresse');
            $table->decimal('longitude', 10, 7)->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->text('description')->nullable();
            $table->date('date_debut');
            $table->date('date_fin');
            $table->enum('status', ['en_attente', 'valide', 'rejete', 'suspendu'])->default('en_attente');
            $table->foreignId('id_cat_evenmt')->constrained('cat_evenmt');
            $table->foreignId('id_admin')->constrained('admin');
            $table->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('evenement'); }
};
