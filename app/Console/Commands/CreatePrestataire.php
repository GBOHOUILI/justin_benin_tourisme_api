<?php

namespace App\Console\Commands;

use App\Enums\TypePrestataire;
use App\Models\Prestataire;
use Illuminate\Console\Command;

/**
 * Outil CLI temporaire pour peupler la table prestataire en attendant
 * le module Prestataire (inscription, compte, dashboard). Réservé à
 * qui a accès au serveur/à la CLI — aucune route HTTP correspondante.
 */
class CreatePrestataire extends Command
{
    protected $signature = 'prestataire:create
        {--nom= : Nom de l\'entreprise}
        {--type= : Type de prestataire (' . 'site, evenement, hotel, restaurant, transport' . ')}';

    protected $description = 'Crée une fiche Prestataire minimale (pas de compte, pas d\'inscription)';

    public function handle(): int
    {
        $valeurs = array_column(TypePrestataire::cases(), 'value');

        $nom = $this->option('nom') ?: $this->ask('Nom de l\'entreprise');
        $type = $this->option('type') ?: $this->ask(
            'Type de prestataire (' . implode(', ', $valeurs) . ') — laisser vide si inconnu',
            null,
        );

        if (! $nom) {
            $this->error('Le nom de l\'entreprise est requis.');
            return self::FAILURE;
        }

        if ($type && ! TypePrestataire::tryFrom($type)) {
            $this->error(
                "Type de prestataire invalide : \"{$type}\". Valeurs acceptées : " . implode(', ', $valeurs),
            );
            return self::FAILURE;
        }

        $prestataire = Prestataire::create([
            'nom_entreprise' => $nom,
            'type_prestataire' => $type ?: null,
            'status' => true,
        ]);

        $this->info("Prestataire #{$prestataire->id} créé : {$prestataire->nom_entreprise}");
        return self::SUCCESS;
    }
}
