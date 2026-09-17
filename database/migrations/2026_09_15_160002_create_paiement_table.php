<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('paiement', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_commande')->constrained('commande')->cascadeOnDelete();
            $table->string('reference_transaction')->nullable()->unique();
            $table->decimal('montant', 12, 2);
            $table->string('moyen')->nullable();
            $table->enum('statut', ['en_attente', 'reussi', 'echoue'])->default('en_attente');
            $table->unsignedTinyInteger('numero_echeance')->nullable();
            $table->json('payload_webhook')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('paiement');
    }
};
