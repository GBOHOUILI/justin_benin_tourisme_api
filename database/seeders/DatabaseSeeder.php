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

        $this->command->info('✅ Admin + catégories + régions créés.');
    }
}