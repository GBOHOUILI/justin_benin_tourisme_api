<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\AuthorizesOwnership;
use App\Models\Avis;
use App\Models\Utilisation;
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
                    name: "id_utilisation",
                    in: "query",
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
        $query = Avis::with([
            "utilisation.ticket.reservation.user",
            "utilisation.ticket.reservation.site",
            "utilisation.ticket.reservation.evenement",
        ]);
        if ($request->filled("status")) {
            $query->where("status", $request->status);
        }
        if ($request->filled("id_utilisation")) {
            $query->where("id_utilisation", $request->id_utilisation);
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
                    required: ["id_utilisation", "message"],
                    properties: [
                        new OA\Property(
                            property: "id_utilisation",
                            type: "integer",
                        ),
                        new OA\Property(property: "message", type: "string"),
                    ],
                ),
            ),
            responses: [
                new OA\Response(response: 201, description: "Avis créé"),
            ],
        ),
    ]
    public function store(Request $request)
    {
        $validated = $request->validate([
            "id_utilisation" =>
                "required|exists:utilisation,id|unique:avis,id_utilisation",
            "message" => "required|string|max:1000",
        ]);

        $utilisation = Utilisation::with("ticket.reservation")->findOrFail(
            $validated["id_utilisation"],
        );
        $this->authorizeOwner($utilisation->ticket->reservation->id_user, $request);

        $validated["status"] = "en_attente";
        $avis = Avis::create($validated);
        return response()->json($avis->load("utilisation"), 201);
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
            $avi->load([
                "utilisation.ticket.reservation.user",
                "utilisation.ticket.reservation.site",
                "utilisation.ticket.reservation.evenement",
            ]),
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
        $avi->load("utilisation.ticket.reservation");
        $this->authorizeOwner($avi->utilisation->ticket->reservation->id_user, $request);

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
        $avi->load("utilisation.ticket.reservation");
        $this->authorizeOwner($avi->utilisation->ticket->reservation->id_user, $request);

        $avi->delete();
        return response()->json(["message" => "Avis supprimé"], 200);
    }
}
