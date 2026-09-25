<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\AuthorizesOwnership;
use App\Models\Avis;
use App\Models\Reservation;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

class AvisController extends Controller
{
    use AuthorizesOwnership;

    #[
        OA\Get(
            path: "/api/avis",
            tags: ["Avis"],
            summary: "Lister les avis",
            parameters: [
                new OA\Parameter(name: "status", in: "query", schema: new OA\Schema(type: "string")),
                new OA\Parameter(name: "id_site", in: "query", description: "Avis laissés sur ce site (via la réservation)", schema: new OA\Schema(type: "integer")),
                new OA\Parameter(name: "id_evnmt", in: "query", description: "Avis laissés sur cet événement (via la réservation)", schema: new OA\Schema(type: "integer")),
                new OA\Parameter(name: "id_hotel", in: "query", description: "Avis laissés sur cet hôtel", schema: new OA\Schema(type: "integer")),
                new OA\Parameter(name: "id_restaurant", in: "query", description: "Avis laissés sur ce restaurant", schema: new OA\Schema(type: "integer")),
                new OA\Parameter(name: "id_transport", in: "query", description: "Avis laissés sur ce transport", schema: new OA\Schema(type: "integer")),
            ],
            responses: [
                new OA\Response(response: 200, description: "Liste des avis"),
            ],
        ),
    ]
    public function index(Request $request)
    {
        $query = Avis::with(["reservation.user", "reservation.site", "reservation.evenement", "user", "hotel", "restaurant", "transport"]);
        if ($request->filled("status")) {
            $query->where("status", $request->status);
        }
        if ($request->filled("id_site")) {
            $query->whereHas("reservation", fn($q) => $q->where("id_site", $request->id_site));
        }
        if ($request->filled("id_evnmt")) {
            $query->whereHas("reservation", fn($q) => $q->where("id_evnmt", $request->id_evnmt));
        }
        if ($request->filled("id_hotel")) {
            $query->where("id_hotel", $request->id_hotel);
        }
        if ($request->filled("id_restaurant")) {
            $query->where("id_restaurant", $request->id_restaurant);
        }
        if ($request->filled("id_transport")) {
            $query->where("id_transport", $request->id_transport);
        }
        return response()->json($query->latest()->paginate(20));
    }

    #[
        OA\Post(
            path: "/api/avis",
            tags: ["Avis"],
            summary: "Créer un avis (sur une réservation confirmée pour Site/Événement, directement sur la fiche pour Hôtel/Restaurant/Transport)",
            requestBody: new OA\RequestBody(
                content: new OA\JsonContent(
                    required: ["message", "note"],
                    properties: [
                        new OA\Property(property: "id_reservation", type: "integer", description: "Réservation confirmée du touriste (Site/Événement)"),
                        new OA\Property(property: "id_hotel", type: "integer"),
                        new OA\Property(property: "id_restaurant", type: "integer"),
                        new OA\Property(property: "id_transport", type: "integer"),
                        new OA\Property(property: "message", type: "string"),
                        new OA\Property(property: "note", type: "integer", minimum: 1, maximum: 5),
                    ],
                ),
            ),
            responses: [
                new OA\Response(response: 201, description: "Avis créé"),
                new OA\Response(response: 422, description: "Cible manquante/multiple, réservation non confirmée, ou avis déjà déposé"),
            ],
        ),
    ]
    public function store(Request $request)
    {
        $validated = $request->validate([
            "id_reservation" => "nullable|integer|exists:reservation,id|unique:avis,id_reservation",
            "id_hotel" => "nullable|integer|exists:hotel,id",
            "id_restaurant" => "nullable|integer|exists:restaurant,id",
            "id_transport" => "nullable|integer|exists:transport,id",
            "message" => "required|string|max:1000",
            "note" => "required|integer|min:1|max:5",
        ]);

        // Exactement une cible : jamais 0 (avis orphelin), jamais 2+
        // (ambiguïté sur ce qui est réellement noté).
        $cibles = array_filter([
            "id_reservation" => $validated["id_reservation"] ?? null,
            "id_hotel" => $validated["id_hotel"] ?? null,
            "id_restaurant" => $validated["id_restaurant"] ?? null,
            "id_transport" => $validated["id_transport"] ?? null,
        ]);

        if (count($cibles) !== 1) {
            return response()->json(
                ["message" => "Précisez exactement une cible pour cet avis (réservation, hôtel, restaurant ou transport)."],
                422,
            );
        }

        if (isset($validated["id_reservation"])) {
            $reservation = Reservation::findOrFail($validated["id_reservation"]);
            $this->authorizeOwner($reservation->id_user, $request);

            if ($reservation->statut !== "confirmee") {
                return response()->json(
                    ["message" => "Cette réservation n'est pas confirmée, impossible de laisser un avis."],
                    422,
                );
            }

            $avis = Avis::create([
                "id_reservation" => $reservation->id,
                "message" => $validated["message"],
                "note" => $validated["note"],
                "status" => "en_attente",
            ]);

            return response()->json($avis->load("reservation"), 201);
        }

        // Hôtel/Restaurant/Transport : pas de Reservation à ces 3 entités
        // (cf. migration) donc pas de preuve de visite possible - ouvert à
        // tout touriste connecté, un seul avis par utilisateur et par fiche.
        $colonne = array_key_first($cibles);
        $user = $request->user();

        if (Avis::where("id_user", $user->id)->where($colonne, $cibles[$colonne])->exists()) {
            return response()->json(["message" => "Vous avez déjà laissé un avis sur cette fiche."], 422);
        }

        $avis = Avis::create([
            $colonne => $cibles[$colonne],
            "id_user" => $user->id,
            "message" => $validated["message"],
            "note" => $validated["note"],
            "status" => "en_attente",
        ]);

        return response()->json($avis->load(["hotel", "restaurant", "transport", "user"]), 201);
    }

    #[
        OA\Get(
            path: "/api/avis/{id}",
            tags: ["Avis"],
            summary: "Afficher un avis",
            parameters: [new OA\Parameter(name: "id", in: "path", required: true, schema: new OA\Schema(type: "integer"))],
            responses: [new OA\Response(response: 200, description: "Détails de l'avis")],
        ),
    ]
    public function show(Avis $avi)
    {
        return response()->json(
            $avi->load(["reservation.user", "reservation.site", "reservation.evenement", "user", "hotel", "restaurant", "transport"]),
        );
    }

    #[
        OA\Put(
            path: "/api/avis/{id}",
            tags: ["Avis"],
            summary: "Mettre à jour un avis",
            parameters: [new OA\Parameter(name: "id", in: "path", required: true, schema: new OA\Schema(type: "integer"))],
            requestBody: new OA\RequestBody(
                content: new OA\JsonContent(properties: [
                    new OA\Property(property: "message", type: "string"),
                    new OA\Property(property: "note", type: "integer", minimum: 1, maximum: 5),
                ]),
            ),
            responses: [new OA\Response(response: 200, description: "Avis mis à jour")],
        ),
    ]
    public function update(Request $request, Avis $avi)
    {
        $this->authorizeAvisOwner($avi, $request);

        $validated = $request->validate([
            "message" => "sometimes|string|max:1000",
            "note" => "sometimes|integer|min:1|max:5",
        ]);
        $avi->update($validated);
        return response()->json($avi);
    }

    #[
        OA\Patch(
            path: "/api/avis/{id}/approuver",
            tags: ["Avis"],
            summary: "Approuver un avis",
            parameters: [new OA\Parameter(name: "id", in: "path", required: true, schema: new OA\Schema(type: "integer"))],
            responses: [new OA\Response(response: 200, description: "Avis approuvé")],
        ),
    ]
    public function approuver(Avis $avi)
    {
        $avi->update(["status" => "approuve"]);
        return response()->json(["message" => "Avis approuvé", "avis" => $avi]);
    }

    #[
        OA\Patch(
            path: "/api/avis/{id}/rejeter",
            tags: ["Avis"],
            summary: "Rejeter un avis",
            parameters: [new OA\Parameter(name: "id", in: "path", required: true, schema: new OA\Schema(type: "integer"))],
            responses: [new OA\Response(response: 200, description: "Avis rejeté")],
        ),
    ]
    public function rejeter(Avis $avi)
    {
        $avi->update(["status" => "rejete"]);
        return response()->json(["message" => "Avis rejeté", "avis" => $avi]);
    }

    #[
        OA\Delete(
            path: "/api/avis/{id}",
            tags: ["Avis"],
            summary: "Supprimer un avis",
            parameters: [new OA\Parameter(name: "id", in: "path", required: true, schema: new OA\Schema(type: "integer"))],
            responses: [new OA\Response(response: 200, description: "Avis supprimé")],
        ),
    ]
    public function destroy(Request $request, Avis $avi)
    {
        $this->authorizeAvisOwner($avi, $request);

        $avi->delete();
        return response()->json(["message" => "Avis supprimé"], 200);
    }

    /** L'auteur remonte via la réservation (Site/Événement) ou directement via id_user (Hôtel/Restaurant/Transport, cf. migration 2026_09_25_200254). */
    private function authorizeAvisOwner(Avis $avi, Request $request): void
    {
        if ($avi->id_reservation) {
            $avi->loadMissing("reservation");
            $this->authorizeOwner($avi->reservation->id_user, $request);
            return;
        }

        $this->authorizeOwner($avi->id_user, $request);
    }
}
