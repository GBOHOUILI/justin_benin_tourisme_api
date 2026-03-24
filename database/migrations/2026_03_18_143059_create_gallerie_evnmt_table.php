<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('gallerie_evnmt', function (Blueprint $table) {
            $table->id();
            $table->string('libelle');
            $table->string('type');
            $table->boolean('status')->default(1);
            $table->foreignId('id_evnmt')->constrained('evenement')->cascadeOnDelete();
            $table->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('gallerie_evnmt'); }
};
