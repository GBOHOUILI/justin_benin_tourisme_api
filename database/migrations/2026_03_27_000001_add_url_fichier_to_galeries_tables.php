<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Ajoute la colonne url_fichier aux tables de galerie.
 *
 * Contexte : la colonne 'libelle' stockait à la fois le titre du média
 * ET le chemin du fichier uploadé (écrasant le titre). Ce bug est corrigé :
 * - 'libelle'    = titre/description lisible du média
 * - 'url_fichier' = chemin du fichier sur le disque public (nullable)
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::table("galerie_site", function (Blueprint $table) {
            $table->string("url_fichier")->nullable()->after("libelle");
        });

        Schema::table("gallerie_evnmt", function (Blueprint $table) {
            $table->string("url_fichier")->nullable()->after("libelle");
        });
    }

    public function down(): void
    {
        Schema::table("galerie_site", function (Blueprint $table) {
            $table->dropColumn("url_fichier");
        });

        Schema::table("gallerie_evnmt", function (Blueprint $table) {
            $table->dropColumn("url_fichier");
        });
    }
};
