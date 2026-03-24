<?php

namespace App\Http\Controllers;

use App\Models\GallerieEvnmt;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use OpenApi\Attributes as OA;

class GallerieEvnmtController extends Controller
{
    #[OA\Get(
        path: "/galeries-evenements",
        tags: ["Galeries Evenements"],
        summary: "Lister les galeries d'événements",
        parameters: [
            new OA\Parameter(name: "id_evnmt", in: "query", schema: new OA\Schema(type: "integer")),
            new OA\Parameter(name: "type", in: "query", schema: new OA\Schema(type: "string")),
            new OA\Parameter(name: "status", in: "query", schema: new OA\Schema(type: "boolean"))
        ],
        responses: [new OA\Response(response: 200, description: "Liste des galeries d'événements")]
    )]
    public function index(Request $request)
    {
        $query = GallerieEvnmt::with('evenement');
        if ($request->filled('id_evnmt')) $query->where('id_evnmt', $request->id_evnmt);
        if ($request->filled('type')) $query->where('type', $request->type);
        if ($request->filled('status')) $query->where('status', $request->status);
        return response()->json($query->get());
    }

    #[OA\Post(
        path: "/galeries-evenements",
        tags: ["Galeries Evenements"],
        summary: "Créer une galerie d'événement",
        requestBody: new OA\RequestBody(
            content: [
                new OA\MediaType(
                    mediaType: "multipart/form-data",
                    schema: new OA\Schema(
                        required: ["libelle", "type", "id_evnmt"],
                        properties: [
                            new OA\Property(property: "libelle", type: "string"),
                            new OA\Property(property: "type", type: "string", enum: ["image", "video"]),
                            new OA\Property(property: "status", type: "boolean"),
                            new OA\Property(property: "id_evnmt", type: "integer"),
                            new OA\Property(property: "fichier", type: "string", format: "binary")
                        ]
                    )
                )
            ]
        ),
        responses: [new OA\Response(response: 201, description: "Galerie créée")]
    )]
    public function store(Request $request)
    {
        $validated = $request->validate([
            'libelle' => 'required|string|max:255',
            'type' => 'required|string|in:image,video',
            'status' => 'nullable|boolean',
            'id_evnmt' => 'required|exists:evenements,id',
            'fichier' => 'nullable|file|mimes:jpg,jpeg,png,gif,mp4,mov|max:51200',
        ]);

        if ($request->hasFile('fichier')) {
            $path = $request->file('fichier')->store('galeries/evenements', 'public');
            $validated['libelle'] = $path;
        }

        $galerie = GallerieEvnmt::create($validated);
        return response()->json($galerie->load('evenement'), 201);
    }

    #[OA\Get(
        path: "/galeries-evenements/{id}",
        tags: ["Galeries Evenements"],
        summary: "Afficher une galerie d'événement",
        parameters: [new OA\Parameter(name: "id", in: "path", required: true, schema: new OA\Schema(type: "integer"))],
        responses: [new OA\Response(response: 200, description: "Galerie d'événement")]
    )]
    public function show(GallerieEvnmt $gallerieEvnmt)
    {
        return response()->json($gallerieEvnmt->load('evenement'));
    }

    #[OA\Put(
        path: "/galeries-evenements/{id}",
        tags: ["Galeries Evenements"],
        summary: "Mettre à jour une galerie d'événement",
        parameters: [new OA\Parameter(name: "id", in: "path", required: true, schema: new OA\Schema(type: "integer"))],
        requestBody: new OA\RequestBody(
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: "libelle", type: "string"),
                    new OA\Property(property: "type", type: "string"),
                    new OA\Property(property: "status", type: "boolean"),
                    new OA\Property(property: "id_evnmt", type: "integer")
                ]
            )
        ),
        responses: [new OA\Response(response: 200, description: "Galerie mise à jour")]
    )]
    public function update(Request $request, GallerieEvnmt $gallerieEvnmt)
    {
        $validated = $request->validate([
            'libelle' => 'sometimes|string|max:255',
            'type' => 'sometimes|string|in:image,video',
            'status' => 'nullable|boolean',
            'id_evnmt' => 'sometimes|exists:evenements,id',
        ]);

        $gallerieEvnmt->update($validated);
        return response()->json($gallerieEvnmt);
    }

    #[OA\Delete(
        path: "/galeries-evenements/{id}",
        tags: ["Galeries Evenements"],
        summary: "Supprimer une galerie d'événement",
        parameters: [new OA\Parameter(name: "id", in: "path", required: true, schema: new OA\Schema(type: "integer"))],
        responses: [new OA\Response(response: 200, description: "Galerie supprimée")]
    )]
    public function destroy(GallerieEvnmt $gallerieEvnmt)
    {
        if (Storage::disk('public')->exists($gallerieEvnmt->libelle)) {
            Storage::disk('public')->delete($gallerieEvnmt->libelle);
        }
        $gallerieEvnmt->delete();
        return response()->json(['message' => 'Média supprimé'], 200);
    }
}