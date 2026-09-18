<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * id_admin était NOT NULL - impossible pour un Site/Evenement créé par un
 * Prestataire (pas d'admin associé). Symétrique à id_prestataire, déjà
 * nullable depuis la migration 2026_09_15_151308. Raw SQL (pas ->change())
 * pour éviter la dépendance doctrine/dbal, absente de ce projet.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::statement('ALTER TABLE site MODIFY id_admin BIGINT UNSIGNED NULL');
        DB::statement('ALTER TABLE evenement MODIFY id_admin BIGINT UNSIGNED NULL');
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE site MODIFY id_admin BIGINT UNSIGNED NOT NULL');
        DB::statement('ALTER TABLE evenement MODIFY id_admin BIGINT UNSIGNED NOT NULL');
    }
};
