<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('voir', function (Blueprint $table) {
            $table->foreignId('id_user')->constrained('users')->cascadeOnDelete();
            $table->foreignId('id_fonc')->constrained('fonctionnalite')->cascadeOnDelete();
            $table->boolean('status')->default(1);
            $table->primary(['id_user', 'id_fonc']);
        });
    }
    public function down(): void { Schema::dropIfExists('voir'); }
};
