<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * date_debut/date_fin étaient des colonnes DATE (l'heure n'était jamais
 * stockée, seulement castée côté Eloquent en 'datetime' avec heure à
 * 00:00:00). Nécessaire pour afficher "Départ le 2 janvier 2027 à 12:00"
 * comme sur la référence eventravel.fr. doctrine/dbal n'est pas installé
 * dans ce projet : ->change() est indisponible, ALTER en SQL brut comme
 * pour convert_status_to_enum_on_site_table.php (même pattern déjà en
 * place).
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::statement('ALTER TABLE evenement MODIFY date_debut DATETIME NOT NULL');
        DB::statement('ALTER TABLE evenement MODIFY date_fin DATETIME NOT NULL');
    }

    public function down(): void
    {
        // ATTENTION : cette conversion DATETIME -> DATE tronque silencieusement
        // l'heure de toutes les lignes existantes (perte de données, inhérente
        // au rollback de cette conversion de type).
        DB::statement('ALTER TABLE evenement MODIFY date_debut DATE NOT NULL');
        DB::statement('ALTER TABLE evenement MODIFY date_fin DATE NOT NULL');
    }
};
