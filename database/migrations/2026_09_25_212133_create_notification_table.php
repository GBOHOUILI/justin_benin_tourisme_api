<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Notifications in-app pour les 4 types de comptes. Une seule table avec
 * discriminant `type_destinataire` (comme `password_reset`) plutôt que le
 * système de notifications polymorphe natif de Laravel (notifiable_type/
 * notifiable_id) - jamais de polymorphisme Eloquent dans ce projet, cf.
 * Avis/migration "note chiffrée par service". Pas de contrainte FK sur
 * id_destinataire : elle pointerait vers l'une de 4 tables différentes
 * selon le type, impossible à exprimer en une seule colonne.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notification', function (Blueprint $table) {
            $table->id();
            $table->enum('type_destinataire', ['user', 'admin', 'prestataire', 'responsable']);
            $table->unsignedBigInteger('id_destinataire');
            $table->string('type_evenement');
            $table->string('titre');
            $table->text('message');
            $table->string('lien')->nullable();
            $table->boolean('lu')->default(false);
            $table->timestamps();
            $table->index(['type_destinataire', 'id_destinataire', 'lu']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notification');
    }
};
