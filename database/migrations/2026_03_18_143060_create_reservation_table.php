<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('reservation', function (Blueprint $table) {
            $table->id();
            $table->string('type');
            $table->decimal('prix', 10, 2);
            $table->integer('nombre');
            $table->decimal('total', 12, 2);
            $table->text('description')->nullable();
            $table->foreignId('id_site')->nullable()->constrained('site')->nullOnDelete();
            $table->foreignId('id_evnmt')->nullable()->constrained('evenement')->nullOnDelete();
            $table->foreignId('id_user')->constrained('users')->cascadeOnDelete();
            $table->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('reservation'); }
};
