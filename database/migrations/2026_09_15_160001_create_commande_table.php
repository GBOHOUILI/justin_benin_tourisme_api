<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('commande', function (Blueprint $table) {
            $table->id();
            $table->string('reference')->unique();
            $table->foreignId('id_user')->constrained('users')->cascadeOnDelete();
            $table->decimal('montant_total', 12, 2);
            $table->enum('statut', ['en_attente', 'payee', 'echouee', 'annulee'])->default('en_attente');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('commande');
    }
};
