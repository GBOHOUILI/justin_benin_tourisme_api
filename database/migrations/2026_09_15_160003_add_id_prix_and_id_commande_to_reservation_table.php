<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('reservation', function (Blueprint $table) {
            $table->foreignId('id_prix')->nullable()->after('id_evnmt')
                ->constrained('prix')->nullOnDelete();
            $table->foreignId('id_commande')->nullable()->after('id_prix')
                ->constrained('commande')->nullOnDelete();
            $table->enum('statut', ['confirmee', 'en_attente_paiement', 'annulee'])
                ->default('confirmee')->after('id_commande');
        });
    }

    public function down(): void
    {
        Schema::table('reservation', function (Blueprint $table) {
            $table->dropColumn('statut');
            $table->dropConstrainedForeignId('id_commande');
            $table->dropConstrainedForeignId('id_prix');
        });
    }
};
