<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('ticket', function (Blueprint $table) {
            $table->id();
            $table->string('numero')->unique();
            $table->foreignId('id_reservation')->constrained('reservation')->cascadeOnDelete();
            $table->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('ticket'); }
};
