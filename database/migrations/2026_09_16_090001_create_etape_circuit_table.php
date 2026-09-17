<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('etape_circuit', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_circuit')->constrained('circuit')->cascadeOnDelete();
            $table->foreignId('id_site')->nullable()->constrained('site')->nullOnDelete();
            $table->foreignId('id_evnmt')->nullable()->constrained('evenement')->nullOnDelete();
            $table->unsignedInteger('ordre');
            $table->foreignId('id_reservation')->nullable()->constrained('reservation')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('etape_circuit');
    }
};
