<?php

namespace App\Http\Controllers;

use App\Mail\ReinitialisationMotDePasse;
use App\Models\Admin;
use App\Models\Prestataire;
use App\Models\ResponsableRegional;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use OpenApi\Attributes as OA;

/**
 * "Mot de passe oublié" pour les 4 types de comptes (touriste, admin,
 * prestataire, responsable régional) - la connexion elle-même ne change
 * pas (toujours tel pour admin/responsable), seul le recouvrement se fait
 * par email pour tous. Admin/ResponsableRegional n'ont pas toujours un
 * email renseigné (colonne ajoutée après coup, nullable) - le message
 * générique de demander() ne révèle jamais si un compte/email existe
 * (anti-énumération), donc ce cas se comporte exactement comme un email
 * inconnu du point de vue de l'appelant.
 */
class PasswordResetController extends Controller
{
    private const DUREE_VALIDITE_MINUTES = 60;

    private function config(string $type): array
    {
        return match ($type) {
            'user' => ['model' => User::class, 'champ_nom' => 'prenom'],
            'admin' => ['model' => Admin::class, 'champ_nom' => 'prenom'],
            'prestataire' => ['model' => Prestataire::class, 'champ_nom' => 'nom_entreprise'],
            'responsable' => ['model' => ResponsableRegional::class, 'champ_nom' => 'prenom'],
        };
    }

    #[
        OA\Post(
            path: "/api/mot-de-passe/oublie",
            tags: ["Authentification"],
            summary: "Demander un lien de réinitialisation de mot de passe (envoyé par email si le compte existe)",
            requestBody: new OA\RequestBody(
                required: true,
                content: new OA\JsonContent(required: ["type", "email"], properties: [
                    new OA\Property(property: "type", type: "string", enum: ["user", "admin", "prestataire", "responsable"]),
                    new OA\Property(property: "email", type: "string", format: "email"),
                ]),
            ),
            responses: [
                new OA\Response(response: 200, description: "Message générique (compte trouvé ou non, jamais révélé)"),
            ],
        ),
    ]
    public function demander(Request $request)
    {
        $validated = $request->validate([
            "type" => "required|in:user,admin,prestataire,responsable",
            "email" => "required|email",
        ]);

        $conf = $this->config($validated["type"]);
        $compte = $conf["model"]::where("email", $validated["email"])->first();

        if ($compte) {
            $token = Str::random(64);

            DB::table("password_reset")
                ->where("type", $validated["type"])
                ->where("email", $validated["email"])
                ->delete();

            DB::table("password_reset")->insert([
                "type" => $validated["type"],
                "email" => $validated["email"],
                "token" => Hash::make($token),
                "created_at" => now(),
            ]);

            $lien = rtrim(config("services.frontend.url"), "/")
                . "/mot-de-passe/reinitialiser/{$validated['type']}"
                . "?email=" . urlencode($validated["email"])
                . "&token=" . $token;

            try {
                Mail::to($validated["email"])->send(
                    new ReinitialisationMotDePasse($compte->{$conf["champ_nom"]}, $lien),
                );
            } catch (\Throwable $e) {
                Log::warning("Mot de passe oublié : envoi de l'email échoué", ["exception" => $e->getMessage()]);
            }
        }

        // Toujours la même réponse, compte trouvé ou non - ne jamais
        // laisser deviner quels emails existent en base.
        return response()->json([
            "message" => "Si un compte existe avec cet email, un lien de réinitialisation vient de lui être envoyé.",
        ]);
    }

    #[
        OA\Post(
            path: "/api/mot-de-passe/reinitialiser",
            tags: ["Authentification"],
            summary: "Réinitialiser le mot de passe à partir du lien reçu par email",
            requestBody: new OA\RequestBody(
                required: true,
                content: new OA\JsonContent(required: ["type", "email", "token", "password", "password_confirmation"], properties: [
                    new OA\Property(property: "type", type: "string", enum: ["user", "admin", "prestataire", "responsable"]),
                    new OA\Property(property: "email", type: "string", format: "email"),
                    new OA\Property(property: "token", type: "string"),
                    new OA\Property(property: "password", type: "string"),
                    new OA\Property(property: "password_confirmation", type: "string"),
                ]),
            ),
            responses: [
                new OA\Response(response: 200, description: "Mot de passe réinitialisé"),
                new OA\Response(response: 422, description: "Lien invalide ou expiré"),
            ],
        ),
    ]
    public function reinitialiser(Request $request)
    {
        $validated = $request->validate([
            "type" => "required|in:user,admin,prestataire,responsable",
            "email" => "required|email",
            "token" => "required|string",
            "password" => "required|string|min:8|confirmed",
        ]);

        $ligne = DB::table("password_reset")
            ->where("type", $validated["type"])
            ->where("email", $validated["email"])
            ->first();

        $tokenValide = $ligne
            && Hash::check($validated["token"], $ligne->token)
            && abs(now()->diffInMinutes($ligne->created_at)) <= self::DUREE_VALIDITE_MINUTES;

        if (! $tokenValide) {
            return response()->json(["message" => "Ce lien de réinitialisation est invalide ou a expiré."], 422);
        }

        $conf = $this->config($validated["type"]);
        $compte = $conf["model"]::where("email", $validated["email"])->first();

        if (! $compte) {
            return response()->json(["message" => "Ce lien de réinitialisation est invalide ou a expiré."], 422);
        }

        $compte->update(["password" => $validated["password"]]);
        // Le mot de passe vient de changer - toute session ouverte ailleurs
        // (token Sanctum volé/partagé) doit être invalidée.
        $compte->tokens()->delete();

        DB::table("password_reset")
            ->where("type", $validated["type"])
            ->where("email", $validated["email"])
            ->delete();

        return response()->json(["message" => "Mot de passe réinitialisé avec succès."]);
    }
}
