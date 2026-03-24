<?php

namespace App\Http\Controllers;

use App\Models\CatEvenmt;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

class CatEvenmtController extends Controller
{
    #[OA\Get(
        path: "/cat-evenmts",
        tags: ["Categories Evenements"],
        summary: "Lister les catégories d'événements",
        responses: [new OA\Response(response: 200, description: "Liste des catégories")]
    )]
    public function index()
    {
        return response()->json(CatEvenmt::withCount('evenements')->get());
    }

    #[OA\Post(
        path: "/cat-evenmts",
        tags: ["Categories Evenements"],
        summary: "Créer une catégorie d'événement",
        requestBody: new OA\RequestBody(
            content: new OA\JsonContent(
                required: ["libelle"],
                properties: [
                    new OA\Property(property: "libelle", type: "string")
                ]
            )
        ),
        responses: [new OA\Response(response: 201, description: "Catégorie créée")]
    )]
    public function store(Request $request)
    {
        $validated = $request->validate(['libelle' => 'required|string|max:150|unique:cat_evenmts']);
        $cat = CatEvenmt::create($validated);
        return response()->json($cat, 201);
    }

    #[OA\Get(
        path: "/cat-evenmts/{id}",
        tags: ["Categories Evenements"],
        summary: "Afficher une catégorie d'événement",
        parameters: [new OA\Parameter(name: "id", in: "path", required: true, schema: new OA\Schema(type: "integer"))],
        responses: [new OA\Response(response: 200, description: "Détails de la catégorie")]
    )]
    public function show(CatEvenmt $catEvenmt)
    {
        return response()->json($catEvenmt->load('evenements'));
    }

    #[OA\Put(
        path: "/cat-evenmts/{id}",
        tags: ["Categories Evenements"],
        summary: "Mettre à jour une catégorie d'événement",
        parameters: [new OA\Parameter(name: "id", in: "path", required: true, schema: new OA\Schema(type: "integer"))],
        requestBody: new OA\RequestBody(
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: "libelle", type: "string")
                ]
            )
        ),
        responses: [new OA\Response(response: 200, description: "Catégorie mise à jour")]
    )]
    public function update(Request $request, CatEvenmt $catEvenmt)
    {
        $validated = $request->validate(['libelle' => 'required|string|max:150|unique:cat_evenmts,libelle,' . $catEvenmt->id]);
        $catEvenmt->update($validated);
        return response()->json($catEvenmt);
    }

    #[OA\Delete(
        path: "/cat-evenmts/{id}",
        tags: ["Categories Evenements"],
        summary: "Supprimer une catégorie d'événement",
        parameters: [new OA\Parameter(name: "id", in: "path", required: true, schema: new OA\Schema(type: "integer"))],
        responses: [new OA\Response(response: 200, description: "Catégorie supprimée")]
    )]
    public function destroy(CatEvenmt $catEvenmt)
    {
        $catEvenmt->delete();
        return response()->json(['message' => 'Catégorie événement supprimée'], 200);
    }
}