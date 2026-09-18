<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Site.status passe de booléen (actif/inactif) au même enum qu'Evenement
 * (en_attente/valide/rejete/suspendu) - nécessaire pour que le Responsable
 * régional puisse valider/rejeter un Site comme il le fait déjà pour un
 * Evenement (un booléen ne distingue pas "en attente" de "rejeté").
 * Site actif (1) -> valide ; inactif (0) -> en_attente (c'est déjà le bucket
 * "pas encore approuvé" utilisé pour les fiches créées par un Prestataire).
 * Colonne temporaire + copie des valeurs plutôt qu'un MODIFY direct : un
 * MODIFY bool -> enum ferait un cast implicite peu fiable côté MySQL.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE site ADD COLUMN status_new ENUM('en_attente','valide','rejete','suspendu') NOT NULL DEFAULT 'en_attente' AFTER status");
        DB::statement("UPDATE site SET status_new = IF(status = 1, 'valide', 'en_attente')");
        DB::statement("ALTER TABLE site DROP COLUMN status");
        DB::statement("ALTER TABLE site CHANGE status_new status ENUM('en_attente','valide','rejete','suspendu') NOT NULL DEFAULT 'en_attente'");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE site ADD COLUMN status_bool BOOLEAN NOT NULL DEFAULT 1 AFTER status");
        DB::statement("UPDATE site SET status_bool = IF(status = 'valide', 1, 0)");
        DB::statement("ALTER TABLE site DROP COLUMN status");
        DB::statement("ALTER TABLE site CHANGE status_bool status BOOLEAN NOT NULL DEFAULT 1");
    }
};
