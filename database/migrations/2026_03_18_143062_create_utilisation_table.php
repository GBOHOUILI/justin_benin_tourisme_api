<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('utilisation', function (Blueprint $table) {
            $table->id();
            $table->date('date_visite');
            $table->time('heure');
            $table->foreignId('id_ticket')->constrained('ticket')->cascadeOnDelete();
            $table->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('utilisation'); }
};
