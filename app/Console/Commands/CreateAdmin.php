<?php

namespace App\Console\Commands;

use App\Models\Admin;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;

class CreateAdmin extends Command
{
    /**
     * Commande : php artisan admin:create
     *
     * Permet de créer un administrateur en interactif depuis le terminal.
     * Utile pour créer le premier admin sur un serveur vierge où le seeder
     * n'a pas encore été exécuté.
     */
    protected $signature = "admin:create";
    protected $description = "Créer un nouvel administrateur de manière interactive";

    public function handle(): int
    {
        $this->info("═══════════════════════════════════════");
        $this->info('  Création d\'un administrateur');
        $this->info("═══════════════════════════════════════");

        $nom = $this->ask("Nom");
        $prenom = $this->ask("Prénom");
        $tel = $this->ask("Téléphone (ex: +22901000000)");

        // Vérifier que le numéro n'existe pas déjà
        if (Admin::where("tel", $tel)->exists()) {
            $this->error("Un admin avec le numéro {$tel} existe déjà.");
            return self::FAILURE;
        }

        $password = $this->secret("Mot de passe (min. 8 caractères)");
        $passwordConfirm = $this->secret("Confirmer le mot de passe");

        if ($password !== $passwordConfirm) {
            $this->error("Les mots de passe ne correspondent pas.");
            return self::FAILURE;
        }

        if (strlen($password) < 8) {
            $this->error(
                "Le mot de passe doit contenir au moins 8 caractères.",
            );
            return self::FAILURE;
        }

        $admin = Admin::create([
            "nom" => $nom,
            "prenom" => $prenom,
            "tel" => $tel,
            "password" => Hash::make($password),
            "status" => true,
        ]);

        $this->info("");
        $this->info("✅ Administrateur créé avec succès !");
        $this->table(
            ["ID", "Nom", "Prénom", "Téléphone", "Status"],
            [[$admin->id, $admin->nom, $admin->prenom, $admin->tel, "Actif"]],
        );
        $this->info("");
        $this->info("Connexion : POST /api/admin/login");
        $this->info("  { \"tel\": \"{$tel}\", \"password\": \"***\" }");

        return self::SUCCESS;
    }
}
