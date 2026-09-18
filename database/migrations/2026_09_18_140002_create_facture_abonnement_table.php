<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('facture_abonnement', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_abonnement')->constrained('abonnement')->cascadeOnDelete();
            $table->decimal('montant', 10, 2);
            $table->timestamp('date_facturation')->nullable();
            $table->enum('statut_paiement', ['en_attente', 'payee', 'echouee'])->default('en_attente');
            $table->string('reference_transaction')->nullable()->unique();
            $table->json('payload_webhook')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('facture_abonnement');
    }
};
