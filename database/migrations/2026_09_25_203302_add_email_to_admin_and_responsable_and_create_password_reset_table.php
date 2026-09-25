<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * "Mot de passe oublié" pour les 4 types de comptes (touriste, admin,
 * prestataire, responsable régional) - décision produit : le recouvrement
 * se fait par email pour tous, la connexion elle-même ne change pas
 * (toujours tel pour admin/responsable, email pour touriste/prestataire).
 * Admin et ResponsableRegional n'ont aujourd'hui aucune colonne email
 * (seulement tel) - ajoutée ici, nullable (comptes existants sans email
 * tant qu'un email n'est pas renseigné, recouvrement alors impossible en
 * self-service pour eux - message clair côté API plutôt qu'un crash).
 *
 * Une seule table `password_reset` pour les 4 types plutôt que 4 tables
 * séparées ou le password broker natif de Laravel (pensé pour un seul
 * modèle "users") - `type` distingue le compte concerné, cohérent avec le
 * reste du projet (discriminants explicites plutôt que du polymorphisme
 * Eloquent, jamais utilisé ailleurs dans ce projet).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('admin', function (Blueprint $table) {
            $table->string('email')->nullable()->unique()->after('prenom');
        });

        Schema::table('responsable_regional', function (Blueprint $table) {
            $table->string('email')->nullable()->unique()->after('prenom');
        });

        Schema::create('password_reset', function (Blueprint $table) {
            $table->id();
            $table->enum('type', ['user', 'admin', 'prestataire', 'responsable']);
            $table->string('email');
            $table->string('token');
            $table->timestamp('created_at')->nullable();
            $table->index(['type', 'email']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('password_reset');

        Schema::table('responsable_regional', function (Blueprint $table) {
            $table->dropColumn('email');
        });

        Schema::table('admin', function (Blueprint $table) {
            $table->dropColumn('email');
        });
    }
};
