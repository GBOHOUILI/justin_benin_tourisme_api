<?php

namespace App\Http\Controllers;

use App\Models\Utilisation;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

class UtilisationController extends Controller
{
    #[
        OA\Get(
            path: "/api/utilisations",
            tags: ["Utilisations"],
            summary: "Liste des utilisations",
            parameters: [
                new OA\Parameter(
                    name: "id_ticket",
                    in: "query",
                    description: "Filtrer par ID de ticket",
                    schema: new OA\Schema(type: "integer"),
                ),
                new OA\Parameter(
                    name: "date_visite",
                    in: "query",
                    description: "Filtrer par date de visite",
                    schema: new OA\Schema(type: "string", format: "date"),
                ),
            ],
            responses: [
                new OA\Response(
                    response: 200,
                    description: "Liste des utilisations",
                ),
            ],
        ),
    ]
    public function index(Request $request)
    {
        $query = Utilisation::with(["ticket.reservation", "avis"]);
        if ($request->filled("id_ticket")) {
            $query->where("id_ticket", $request->id_ticket);
        }
        if ($request->filled("date_visite")) {
            $query->whereDate("date_visite", $request->date_visite);
        }
        return response()->json($query->latest("date_visite")->get());
    }

    #[
        OA\Post(
            path: "/api/utilisations",
            tags: ["Utilisations"],
            summary: "Créer une utilisation",
            requestBody: new OA\RequestBody(
                required: true,
                content: new OA\JsonContent(
                    required: ["date_visite", "heure", "id_ticket"],
                    properties: [
                        new OA\Property(
                            property: "date_visite",
                            type: "string",
                            format: "date",
                        ),
                        new OA\Property(
                            property: "heure",
                            type: "string",
                            example: "14:30",
                        ),
                        new OA\Property(property: "id_ticket", type: "integer"),
                    ],
                ),
            ),
            responses: [
                new OA\Response(
                    response: 201,
                    description: "Utilisation créée",
                ),
                new OA\Response(
                    response: 422,
                    description: "Erreur de validation ou ticket déjà utilisé",
                ),
            ],
        ),
    ]
    public function store(Request $request)
    {
        $validated = $request->validate([
            "date_visite" => "required|date",
            "heure" => "required|date_format:H:i",
            "id_ticket" => "required|exists:tickets,id",
        ]);

        if (
            Utilisation::where("id_ticket", $validated["id_ticket"])->exists()
        ) {
            return response()->json(
                ["message" => "Ce ticket a déjà été utilisé"],
                422,
            );
        }

        $utilisation = Utilisation::create($validated);
        return response()->json(
            $utilisation->load(["ticket.reservation", "avis"]),
            201,
        );
    }

    #[
        OA\Get(
            path: "/api/utilisations/{id}",
            tags: ["Utilisations"],
            summary: "Afficher une utilisation",
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
                    description: "Utilisation affichée",
                ),
            ],
        ),
    ]
    public function show(Utilisation $utilisation)
    {
        return response()->json(
            $utilisation->load(["ticket.reservation.user", "avis"]),
        );
    }

    #[
        OA\Put(
            path: "/api/utilisations/{id}",
            tags: ["Utilisations"],
            summary: "Mettre à jour une utilisation",
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
                        new OA\Property(
                            property: "date_visite",
                            type: "string",
                            format: "date",
                        ),
                        new OA\Property(
                            property: "heure",
                            type: "string",
                            example: "14:30",
                        ),
                    ],
                ),
            ),
            responses: [
                new OA\Response(
                    response: 200,
                    description: "Utilisation mise à jour",
                ),
            ],
        ),
    ]
    public function update(Request $request, Utilisation $utilisation)
    {
        $validated = $request->validate([
            "date_visite" => "sometimes|date",
            "heure" => "sometimes|date_format:H:i",
        ]);

        $utilisation->update($validated);
        return response()->json($utilisation);
    }

    #[
        OA\Delete(
            path: "/api/utilisations/{id}",
            tags: ["Utilisations"],
            summary: "Supprimer une utilisation",
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
                    description: "Utilisation supprimée",
                ),
            ],
        ),
    ]
    public function destroy(Utilisation $utilisation)
    {
        $utilisation->delete();
        return response()->json(["message" => "Utilisation supprimée"], 200);
    }
}
