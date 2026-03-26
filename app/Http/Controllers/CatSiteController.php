<?php

namespace App\Http\Controllers;

use App\Models\CatSite;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

class CatSiteController extends Controller
{
    #[
        OA\Get(
            path: "/api/cat-sites",
            tags: ["Categories Sites"],
            summary: "Lister les catégories de sites",
            responses: [
                new OA\Response(
                    response: 200,
                    description: "Liste des catégories",
                ),
            ],
        ),
    ]
    public function index()
    {
        return response()->json(CatSite::withCount("sites")->get());
    }

    #[
        OA\Post(
            path: "/api/cat-sites",
            tags: ["Categories Sites"],
            summary: "Créer une catégorie de site",
            requestBody: new OA\RequestBody(
                content: new OA\JsonContent(
                    required: ["libelle"],
                    properties: [
                        new OA\Property(property: "libelle", type: "string"),
                    ],
                ),
            ),
            responses: [
                new OA\Response(response: 201, description: "Catégorie créée"),
            ],
        ),
    ]
    public function store(Request $request)
    {
        $validated = $request->validate([
            "libelle" => "required|string|max:150|unique:cat_sites",
        ]);
        $cat = CatSite::create($validated);
        return response()->json($cat, 201);
    }

    #[
        OA\Get(
            path: "/api/cat-sites/{id}",
            tags: ["Categories Sites"],
            summary: "Afficher une catégorie de site",
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
                    description: "Détails de la catégorie",
                ),
            ],
        ),
    ]
    public function show(CatSite $catSite)
    {
        return response()->json($catSite->load("sites"));
    }

    #[
        OA\Put(
            path: "/api/cat-sites/{id}",
            tags: ["Categories Sites"],
            summary: "Mettre à jour une catégorie de site",
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
                    ],
                ),
            ),
            responses: [
                new OA\Response(
                    response: 200,
                    description: "Catégorie mise à jour",
                ),
            ],
        ),
    ]
    public function update(Request $request, CatSite $catSite)
    {
        $validated = $request->validate([
            "libelle" =>
                "required|string|max:150|unique:cat_sites,libelle," .
                $catSite->id,
        ]);
        $catSite->update($validated);
        return response()->json($catSite);
    }

    #[
        OA\Delete(
            path: "/api/cat-sites/{id}",
            tags: ["Categories Sites"],
            summary: "Supprimer une catégorie de site",
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
                    description: "Catégorie supprimée",
                ),
            ],
        ),
    ]
    public function destroy(CatSite $catSite)
    {
        $catSite->delete();
        return response()->json(["message" => "Catégorie supprimée"], 200);
    }
}
