<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // ─── Admin par défaut ──────────────────────────────────
        \App\Models\Admin::firstOrCreate(
            ['tel' => '+22901000000'],
            [
                'nom'      => 'Super',
                'prenom'   => 'Admin',
                'password' => Hash::make('admin123'),
                'status'   => true,
            ]
        );

        // ─── Catégories de sites ───────────────────────────────
        foreach (['Patrimoine historique','Site naturel','Musée','Monument','Plage'] as $libelle) {
            \App\Models\CatSite::firstOrCreate(['libelle' => $libelle]);
        }

        // ─── Catégories d'événements ───────────────────────────
        foreach (['Festival culturel','Concert','Exposition','Cérémonie traditionnelle','Foire'] as $libelle) {
            \App\Models\CatEvenmt::firstOrCreate(['libelle' => $libelle]);
        }

        // ─── Régions (départements du Bénin) ────────────────────
        foreach ([
            'Alibori', 'Atacora', 'Atlantique', 'Borgou', 'Collines', 'Couffo',
            'Donga', 'Littoral', 'Mono', 'Ouémé', 'Plateau', 'Zou',
        ] as $nom) {
            \App\Models\Region::firstOrCreate(['nom' => $nom]);
        }

        // ─── Villes principales (pour les trajets de transport) ──
        foreach ([
            'Cotonou' => 'Littoral',
            'Porto-Novo' => 'Ouémé',
            'Parakou' => 'Borgou',
            'Abomey' => 'Zou',
            'Bohicon' => 'Zou',
            'Natitingou' => 'Atacora',
            'Ouidah' => 'Atlantique',
            'Abomey-Calavi' => 'Atlantique',
            'Lokossa' => 'Mono',
            'Djougou' => 'Donga',
            'Kandi' => 'Alibori',
            'Savalou' => 'Collines',
            'Aplahoué' => 'Couffo',
            'Pobè' => 'Plateau',
        ] as $nomVille => $nomRegion) {
            \App\Models\Ville::firstOrCreate(
                ['nom' => $nomVille],
                ['id_region' => \App\Models\Region::where('nom', $nomRegion)->first()?->id]
            );
        }

        $this->command->info('✅ Admin + catégories + régions + villes créés.');
    }
}