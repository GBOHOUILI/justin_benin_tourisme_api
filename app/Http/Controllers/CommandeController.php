<?php

namespace App\Http\Controllers;

use App\Models\Commande;
use App\Models\Paiement;
use App\Models\Reservation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use OpenApi\Attributes as OA;

class CommandeController extends Controller
{
    #[
        OA\Get(
            path: "/api/commandes",
            tags: ["Commandes"],
            summary: "Lister mes commandes",
            security: [["sanctum" => []]],
            responses: [
                new OA\Response(response: 200, description: "Liste des commandes de l'utilisateur connecté"),
            ],
        ),
    ]
    public function index(Request $request)
    {
        $commandes = Commande::with("paiements")
            ->where("id_user", $request->user()->id)
            ->latest()
            ->paginate(15);

        return response()->json($commandes);
    }

    #[
        OA\Post(
            path: "/api/commandes",
            tags: ["Commandes"],
            summary: "Regrouper une ou plusieurs réservations payantes en une commande",
            security: [["sanctum" => []]],
            requestBody: new OA\RequestBody(
                required: true,
                content: new OA\JsonContent(
                    required: ["reservation_ids"],
                    properties: [
                        new OA\Property(
                            property: "reservation_ids",
                            type: "array",
                            items: new OA\Items(type: "integer"),
                        ),
                        new OA\Property(
                            property: "echelonner",
                            type: "boolean",
                            description: "Demande un paiement échelonné - refusé si le tarif ne le permet pas",
                        ),
                    ],
                ),
            ),
            responses: [
                new OA\Response(response: 201, description: "Commande créée avec son (ses) paiement(s) en attente"),
                new OA\Response(response: 422, description: "Réservations invalides ou échelonnement non éligible"),
            ],
        ),
    ]
    public function store(Request $request)
    {
        $validated = $request->validate([
            "reservation_ids" => "required|array|min:1",
            "reservation_ids.*" => "integer|exists:reservation,id",
            "echelonner" => "sometimes|boolean",
        ]);

        $user = $request->user();

        // Seules des réservations payantes (statut en_attente_paiement), à l'utilisateur
        // connecté et pas déjà rattachées à une commande peuvent être regroupées.
        $reservations = Reservation::whereIn("id", $validated["reservation_ids"])
            ->where("id_user", $user->id)
            ->where("statut", "en_attente_paiement")
            ->whereNull("id_commande")
            ->with("tarif")
            ->get();

        if ($reservations->count() !== count($validated["reservation_ids"])) {
            return response()->json([
                "message" => "Une ou plusieurs réservations sont invalides, déjà réglées, ou n'appartiennent pas à cet utilisateur.",
            ], 422);
        }

        $montantTotal = $reservations->sum("total");
        $echelonner = $validated["echelonner"] ?? false;

        $nombreEcheances = 1;
        if ($echelonner) {
            $tarifIds = $reservations->pluck("id_prix")->unique();
            $tarif = $reservations->first()->tarif;

            if ($tarifIds->count() !== 1 || !$tarif || !$tarif->echelonnable) {
                return response()->json([
                    "message" => "Cette commande n'est pas éligible à l'échelonnement (plusieurs tarifs mélangés ou tarif non échelonnable).",
                ], 422);
            }

            $nombreEcheances = max(1, $tarif->nombre_echeances ?? 1);
        }

        $commande = DB::transaction(function () use ($reservations, $user, $montantTotal, $nombreEcheances) {
            $commande = Commande::create([
                "reference" => "CMD-" . strtoupper(Str::random(10)),
                "id_user" => $user->id,
                "montant_total" => $montantTotal,
                "statut" => "en_attente",
            ]);

            Reservation::whereIn("id", $reservations->pluck("id"))
                ->update(["id_commande" => $commande->id]);

            if ($nombreEcheances > 1) {
                $montantEcheance = round($montantTotal / $nombreEcheances, 2);
                $montantCumule = 0;
                for ($i = 1; $i <= $nombreEcheances; $i++) {
                    // La dernière échéance absorbe l'écart d'arrondi.
                    $montant = $i === $nombreEcheances
                        ? round($montantTotal - $montantCumule, 2)
                        : $montantEcheance;
                    $montantCumule += $montant;

                    Paiement::create([
                        "id_commande" => $commande->id,
                        "montant" => $montant,
                        "statut" => "en_attente",
                        "numero_echeance" => $i,
                    ]);
                }
            } else {
                Paiement::create([
                    "id_commande" => $commande->id,
                    "montant" => $montantTotal,
                    "statut" => "en_attente",
                    "numero_echeance" => null,
                ]);
            }

            return $commande;
        });

        return response()->json(
            $commande->load(["reservations.site", "reservations.evenement", "paiements"]),
            201,
        );
    }

    #[
        OA\Get(
            path: "/api/commandes/{id}",
            tags: ["Commandes"],
            summary: "Afficher une commande",
            security: [["sanctum" => []]],
            parameters: [
                new OA\Parameter(name: "id", in: "path", required: true, schema: new OA\Schema(type: "integer")),
            ],
            responses: [
                new OA\Response(response: 200, description: "Détails de la commande"),
                new OA\Response(response: 403, description: "Accès refusé"),
            ],
        ),
    ]
    public function show(Request $request, Commande $commande)
    {
        if ($commande->id_user !== $request->user()->id) {
            return response()->json(["message" => "Accès refusé."], 403);
        }

        return response()->json(
            $commande->load(["reservations.site", "reservations.evenement", "paiements"]),
        );
    }
}
