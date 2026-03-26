<?php

namespace App\Http\Controllers;

use App\Models\Site;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

class SiteController extends Controller
{
    #[
        OA\Get(
            path: "/api/sites",
            tags: ["Sites"],
            summary: "Liste des sites",
            parameters: [
                new OA\Parameter(
                    name: "libelle",
                    in: "query",
                    schema: new OA\Schema(type: "string"),
                ),
                new OA\Parameter(
                    name: "id_cat_site",
                    in: "query",
                    schema: new OA\Schema(type: "integer"),
                ),
                new OA\Parameter(
                    name: "status",
                    in: "query",
                    schema: new OA\Schema(type: "boolean"),
                ),
            ],
            responses: [
                new OA\Response(
                    response: 200,
                    description: "Liste paginée des sites",
                ),
            ],
        ),
    ]
    public function index(Request $request)
    {
        $query = Site::with(["categorie", "galeries", "prix"]);

        if ($request->filled("libelle")) {
            $query->where("libelle", "like", "%" . $request->libelle . "%");
        }
        if ($request->filled("id_cat_site")) {
            $query->where("id_cat_site", $request->id_cat_site);
        }
        if ($request->filled("status")) {
            $query->where("status", $request->status);
        }

        return response()->json($query->paginate(12));
    }

    // id_admin est retiré du body — déduit du token admin connecté
    #[
        OA\Post(
            path: "/api/admin/sites",
            tags: ["Sites"],
            summary: "Créer un site (admin)",
            security: [["bearerAuth" => []]],
            requestBody: new OA\RequestBody(
                required: true,
                content: new OA\JsonContent(
                    required: [
                        "libelle",
                        "adresse",
                        "longitude",
                        "latitude",
                        "id_cat_site",
                    ],
                    properties: [
                        new OA\Property(property: "libelle", type: "string"),
                        new OA\Property(property: "adresse", type: "string"),
                        new OA\Property(property: "longitude", type: "number"),
                        new OA\Property(property: "latitude", type: "number"),
                        new OA\Property(
                            property: "description",
                            type: "string",
                        ),
                        new OA\Property(
                            property: "ouverture",
                            type: "string",
                            example: "08:00",
                        ),
                        new OA\Property(
                            property: "fermeture",
                            type: "string",
                            example: "18:00",
                        ),
                        new OA\Property(property: "status", type: "boolean"),
                        new OA\Property(
                            property: "id_cat_site",
                            type: "integer",
                        ),
                    ],
                ),
            ),
            responses: [
                new OA\Response(response: 201, description: "Site créé"),
            ],
        ),
    ]
    public function store(Request $request)
    {
        $validated = $request->validate([
            "libelle" => "required|string|max:200",
            "adresse" => "required|string|max:255",
            "longitude" => "required|numeric",
            "latitude" => "required|numeric",
            "description" => "nullable|string",
            "ouverture" => "nullable|date_format:H:i",
            "fermeture" => "nullable|date_format:H:i",
            "status" => "nullable|boolean",
            "id_cat_site" => "required|exists:cat_site,id",
        ]);

        // CORRECTION : l'id_admin est toujours celui de l'admin connecté
        $validated["id_admin"] = $request->user()->id;

        $site = Site::create($validated);

        return response()->json($site->load(["categorie", "admin"]), 201);
    }

    #[
        OA\Get(
            path: "/api/sites/{id}",
            tags: ["Sites"],
            summary: "Afficher un site",
            parameters: [
                new OA\Parameter(
                    name: "id",
                    in: "path",
                    required: true,
                    schema: new OA\Schema(type: "integer"),
                ),
            ],
            responses: [
                new OA\Response(response: 200, description: "Détails du site"),
            ],
        ),
    ]
    public function show(Site $site)
    {
        return response()->json(
            $site->load([
                "categorie",
                "admin",
                "galeries",
                "prix",
                "evenements",
            ]),
        );
    }

    #[
        OA\Put(
            path: "/api/admin/sites/{id}",
            tags: ["Sites"],
            summary: "Mettre à jour un site (admin)",
            security: [["bearerAuth" => []]],
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
                        new OA\Property(property: "adresse", type: "string"),
                        new OA\Property(property: "longitude", type: "number"),
                        new OA\Property(property: "latitude", type: "number"),
                        new OA\Property(
                            property: "description",
                            type: "string",
                        ),
                        new OA\Property(
                            property: "ouverture",
                            type: "string",
                            example: "08:00",
                        ),
                        new OA\Property(
                            property: "fermeture",
                            type: "string",
                            example: "18:00",
                        ),
                        new OA\Property(property: "status", type: "boolean"),
                        new OA\Property(
                            property: "id_cat_site",
                            type: "integer",
                        ),
                    ],
                ),
            ),
            responses: [
                new OA\Response(response: 200, description: "Site mis à jour"),
            ],
        ),
    ]
    public function update(Request $request, Site $site)
    {
        $validated = $request->validate([
            "libelle" => "sometimes|string|max:200",
            "adresse" => "sometimes|string|max:255",
            "longitude" => "sometimes|numeric",
            "latitude" => "sometimes|numeric",
            "description" => "nullable|string",
            "ouverture" => "nullable|date_format:H:i",
            "fermeture" => "nullable|date_format:H:i",
            "status" => "nullable|boolean",
            "id_cat_site" => "sometimes|exists:cat_site,id",
        ]);

        $site->update($validated);

        return response()->json($site->load(["categorie", "admin"]));
    }

    #[
        OA\Delete(
            path: "/api/admin/sites/{id}",
            tags: ["Sites"],
            summary: "Supprimer un site (admin)",
            security: [["bearerAuth" => []]],
            parameters: [
                new OA\Parameter(
                    name: "id",
                    in: "path",
                    required: true,
                    schema: new OA\Schema(type: "integer"),
                ),
            ],
            responses: [
                new OA\Response(response: 200, description: "Site supprimé"),
            ],
        ),
    ]
    public function destroy(Site $site)
    {
        $site->delete();

        return response()->json(
            ["message" => "Site supprimé avec succès"],
            200,
        );
    }
}
