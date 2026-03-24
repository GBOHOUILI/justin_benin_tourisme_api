<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('avis', function (Blueprint $table) {
            $table->id();
            $table->text('message');
            $table->enum('status', ['en_attente', 'approuve', 'rejete'])->default('en_attente');
            $table->foreignId('id_utilisation')->unique()->constrained('utilisation')->cascadeOnDelete();
            $table->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('avis'); }
};
