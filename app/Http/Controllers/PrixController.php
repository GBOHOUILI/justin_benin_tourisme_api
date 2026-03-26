<?php

namespace App\Http\Controllers;

use App\Models\Prix;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

class PrixController extends Controller
{
    #[
        OA\Get(
            path: "/api/prix",
            tags: ["Prix"],
            summary: "Lister les prix",
            parameters: [
                new OA\Parameter(
                    name: "id_site",
                    in: "query",
                    schema: new OA\Schema(type: "integer"),
                ),
                new OA\Parameter(
                    name: "id_evnmt",
                    in: "query",
                    schema: new OA\Schema(type: "integer"),
                ),
            ],
            responses: [
                new OA\Response(response: 200, description: "Liste des prix"),
            ],
        ),
    ]
    public function index(Request $request)
    {
        $query = Prix::query();
        if ($request->filled("id_site")) {
            $query->where("id_site", $request->id_site);
        }
        if ($request->filled("id_evnmt")) {
            $query->where("id_evnmt", $request->id_evnmt);
        }
        return response()->json($query->get());
    }

    #[
        OA\Post(
            path: "/api/prix",
            tags: ["Prix"],
            summary: "Créer un prix",
            requestBody: new OA\RequestBody(
                content: new OA\JsonContent(
                    required: ["libelle", "montant"],
                    properties: [
                        new OA\Property(property: "libelle", type: "string"),
                        new OA\Property(property: "montant", type: "number"),
                        new OA\Property(property: "id_site", type: "integer"),
                        new OA\Property(property: "id_evnmt", type: "integer"),
                    ],
                ),
            ),
            responses: [
                new OA\Response(response: 201, description: "Prix créé"),
            ],
        ),
    ]
    public function store(Request $request)
    {
        $validated = $request->validate([
            "libelle" => "required|string|max:200",
            "montant" => "required|numeric|min:0",
            "id_site" => "nullable|exists:sites,id",
            "id_evnmt" => "nullable|exists:evenements,id",
        ]);

        if (empty($validated["id_site"]) && empty($validated["id_evnmt"])) {
            return response()->json(
                ["message" => "Un site ou un événement est requis"],
                422,
            );
        }

        $prix = Prix::create($validated);
        return response()->json($prix->load(["site", "evenement"]), 201);
    }

    #[
        OA\Get(
            path: "/api/prix/{id}",
            tags: ["Prix"],
            summary: "Afficher un prix",
            parameters: [
                new OA\Parameter(
                    name: "id",
                    in: "path",
                    required: true,
                    schema: new OA\Schema(type: "integer"),
                ),
            ],
            responses: [
                new OA\Response(response: 200, description: "Détails du prix"),
            ],
        ),
    ]
    public function show(Prix $prix)
    {
        return response()->json($prix->load(["site", "evenement"]));
    }

    #[
        OA\Put(
            path: "/api/prix/{id}",
            tags: ["Prix"],
            summary: "Mettre à jour un prix",
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
                        new OA\Property(property: "libelle", type: "string"),
                        new OA\Property(property: "montant", type: "number"),
                        new OA\Property(property: "id_site", type: "integer"),
                        new OA\Property(property: "id_evnmt", type: "integer"),
                    ],
                ),
            ),
            responses: [
                new OA\Response(response: 200, description: "Prix mis à jour"),
            ],
        ),
    ]
    public function update(Request $request, Prix $prix)
    {
        $validated = $request->validate([
            "libelle" => "sometimes|string|max:200",
            "montant" => "sometimes|numeric|min:0",
            "id_site" => "nullable|exists:sites,id",
            "id_evnmt" => "nullable|exists:evenements,id",
        ]);

        $prix->update($validated);
        return response()->json($prix->load(["site", "evenement"]));
    }

    #[
        OA\Delete(
            path: "/api/prix/{id}",
            tags: ["Prix"],
            summary: "Supprimer un prix",
            parameters: [
                new OA\Parameter(
                    name: "id",
                    in: "path",
                    required: true,
                    schema: new OA\Schema(type: "integer"),
                ),
            ],
            responses: [
                new OA\Response(response: 200, description: "Prix supprimé"),
            ],
        ),
    ]
    public function destroy(Prix $prix)
    {
        $prix->delete();
        return response()->json(["message" => "Prix supprimé"], 200);
    }
}
