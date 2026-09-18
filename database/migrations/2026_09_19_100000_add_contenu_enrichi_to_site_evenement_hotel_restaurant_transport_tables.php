<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Socle de contenu enrichi partagé par les 5 entités touristiques - cf.
 * docs/superpowers/specs/2026-09-18-fiches-detail-enrichies-design.md.
 * points_forts/inclus/non_inclus en JSON (liste de textes), même pattern
 * que Plan::fonctionnalites (seul champ JSON existant avant celui-ci).
 * Tout nullable : aucune fiche existante ne casse.
 */
return new class extends Migration
{
    private array $tables = ['site', 'evenement', 'hotel', 'restaurant', 'transport'];

    public function up(): void
    {
        foreach ($this->tables as $tableName) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->json('points_forts')->nullable();
                $table->json('inclus')->nullable();
                $table->json('non_inclus')->nullable();
                $table->text('infos_pratiques')->nullable();
                $table->text('recommandations')->nullable();
            });
        }
    }

    public function down(): void
    {
        foreach ($this->tables as $tableName) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->dropColumn(['points_forts', 'inclus', 'non_inclus', 'infos_pratiques', 'recommandations']);
            });
        }
    }
};
