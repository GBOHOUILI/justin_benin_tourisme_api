<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('prix', function (Blueprint $table) {
            $table->id();
            $table->string('libelle');
            $table->decimal('montant', 10, 2);
            $table->foreignId('id_site')->nullable()->constrained('site')->nullOnDelete();
            $table->foreignId('id_evnmt')->nullable()->constrained('evenement')->nullOnDelete();
            $table->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('prix'); }
};
