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
                new OA\Parameter(
                    name: "status",
                    in: "query",
                    schema: new OA\Schema(type: "string"),
                ),
                new OA\Parameter(
                    name: "id_site",
                    in: "query",
                    description: "Avis laissés sur ce site (via la réservation)",
                    schema: new OA\Schema(type: "integer"),
                ),
                new OA\Parameter(
                    name: "id_evnmt",
                    in: "query",
                    description: "Avis laissés sur cet événement (via la réservation)",
                    schema: new OA\Schema(type: "integer"),
                ),
            ],
            responses: [
                new OA\Response(response: 200, description: "Liste des avis"),
            ],
        ),
    ]
    public function index(Request $request)
    {
        $query = Avis::with(["reservation.user", "reservation.site", "reservation.evenement"]);
        if ($request->filled("status")) {
            $query->where("status", $request->status);
        }
        if ($request->filled("id_site")) {
            $query->whereHas("reservation", fn($q) => $q->where("id_site", $request->id_site));
        }
        if ($request->filled("id_evnmt")) {
            $query->whereHas("reservation", fn($q) => $q->where("id_evnmt", $request->id_evnmt));
        }
        return response()->json($query->latest()->paginate(20));
    }

    #[
        OA\Post(
            path: "/api/avis",
            tags: ["Avis"],
            summary: "Créer un avis",
            requestBody: new OA\RequestBody(
                content: new OA\JsonContent(
                    required: ["id_reservation", "message"],
                    properties: [
                        new OA\Property(
                            property: "id_reservation",
                            type: "integer",
                            description: "Réservation confirmée du touriste sur laquelle porte l'avis",
                        ),
                        new OA\Property(property: "message", type: "string"),
                    ],
                ),
            ),
            responses: [
                new OA\Response(response: 201, description: "Avis créé"),
                new OA\Response(response: 422, description: "Réservation non confirmée, ou déjà notée"),
            ],
        ),
    ]
    public function store(Request $request)
    {
        $validated = $request->validate([
            "id_reservation" =>
                "required|exists:reservation,id|unique:avis,id_reservation",
            "message" => "required|string|max:1000",
        ]);

        $reservation = Reservation::findOrFail($validated["id_reservation"]);
        $this->authorizeOwner($reservation->id_user, $request);

        if ($reservation->statut !== "confirmee") {
            return response()->json(
                ["message" => "Cette réservation n'est pas confirmée, impossible de laisser un avis."],
                422,
            );
        }

        $validated["status"] = "en_attente";
        $avis = Avis::create($validated);
        return response()->json($avis->load("reservation"), 201);
    }

    #[
        OA\Get(
            path: "/api/avis/{id}",
            tags: ["Avis"],
            summary: "Afficher un avis",
            parameters: [
                new OA\Parameter(
                    name: "id",
                    in: "path",
                    required: true,
                    schema: new OA\Schema(type: "integer"),
                ),
            ],
            responses: [
                new OA\Response(
                    response: 200,
                    description: "Détails de l'avis",
                ),
            ],
        ),
    ]
    public function show(Avis $avi)
    {
        return response()->json(
            $avi->load(["reservation.user", "reservation.site", "reservation.evenement"]),
        );
    }

    #[
        OA\Put(
            path: "/api/avis/{id}",
            tags: ["Avis"],
            summary: "Mettre à jour un avis",
            parameters: [
                new OA\Parameter(
                    name: "id",
                    in: "path",
                    required: true,
                    schema: new OA\Schema(type: "integer"),
                ),
            ],
            requestBody: new OA\RequestBody(
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "message", type: "string"),
                    ],
                ),
            ),
            responses: [
                new OA\Response(response: 200, description: "Avis mis à jour"),
            ],
        ),
    ]
    public function update(Request $request, Avis $avi)
    {
        $avi->load("reservation");
        $this->authorizeOwner($avi->reservation->id_user, $request);

        $validated = $request->validate([
            "message" => "sometimes|string|max:1000",
        ]);
        $avi->update($validated);
        return response()->json($avi);
    }

    #[
        OA\Patch(
            path: "/api/avis/{id}/approuver",
            tags: ["Avis"],
            summary: "Approuver un avis",
            parameters: [
                new OA\Parameter(
                    name: "id",
                    in: "path",
                    required: true,
                    schema: new OA\Schema(type: "integer"),
                ),
            ],
            responses: [
                new OA\Response(response: 200, description: "Avis approuvé"),
            ],
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
            parameters: [
                new OA\Parameter(
                    name: "id",
                    in: "path",
                    required: true,
                    schema: new OA\Schema(type: "integer"),
                ),
            ],
            responses: [
                new OA\Response(response: 200, description: "Avis rejeté"),
            ],
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
            parameters: [
                new OA\Parameter(
                    name: "id",
                    in: "path",
                    required: true,
                    schema: new OA\Schema(type: "integer"),
                ),
            ],
            responses: [
                new OA\Response(response: 200, description: "Avis supprimé"),
            ],
        ),
    ]
    public function destroy(Request $request, Avis $avi)
    {
        $avi->load("reservation");
        $this->authorizeOwner($avi->reservation->id_user, $request);

        $avi->delete();
        return response()->json(["message" => "Avis supprimé"], 200);
    }
}
