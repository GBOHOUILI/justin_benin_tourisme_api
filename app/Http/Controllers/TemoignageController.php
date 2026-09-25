<?php

namespace App\Http\Controllers;

use App\Models\Temoignage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use OpenApi\Attributes as OA;

class TemoignageController extends Controller
{
    #[
        OA\Get(
            path: "/api/temoignages",
            tags: ["Témoignages"],
            summary: "Lister les témoignages actifs (public, page d'accueil)",
            responses: [new OA\Response(response: 200, description: "Liste des témoignages actifs")],
        ),
    ]
    public function index()
    {
        return response()->json(
            Temoignage::where('actif', true)->orderBy('created_at', 'desc')->get()
        );
    }

    #[
        OA\Get(
            path: "/api/admin/temoignages",
            tags: ["Témoignages"],
            summary: "Lister tous les témoignages, actifs et inactifs (admin)",
            security: [["bearerAuth" => []]],
            responses: [new OA\Response(response: 200, description: "Liste complète des témoignages")],
        ),
    ]
    public function adminIndex()
    {
        return response()->json(
            Temoignage::orderBy('created_at', 'desc')->get()
        );
    }

    #[
        OA\Post(
            path: "/api/admin/temoignages",
            tags: ["Témoignages"],
            summary: "Créer un témoignage (admin)",
            security: [["bearerAuth" => []]],
            requestBody: new OA\RequestBody(
                content: [
                    new OA\MediaType(
                        mediaType: "multipart/form-data",
                        schema: new OA\Schema(
                            required: ["nom", "role", "message"],
                            properties: [
                                new OA\Property(property: "nom", type: "string"),
                                new OA\Property(property: "role", type: "string", description: "Ex: Prestataire, Touriste, Responsable régional"),
                                new OA\Property(property: "message", type: "string"),
                                new OA\Property(property: "actif", type: "boolean"),
                                new OA\Property(property: "photo", type: "string", format: "binary"),
                            ],
                        ),
                    ),
                ],
            ),
            responses: [new OA\Response(response: 201, description: "Témoignage créé")],
        ),
    ]
    public function store(Request $request)
    {
        $validated = $request->validate([
            'nom' => 'required|string|max:255',
            'role' => 'required|string|max:255',
            'message' => 'required|string',
            'actif' => 'nullable|boolean',
            'photo' => 'nullable|file|mimes:jpg,jpeg,png,webp|max:5120',
        ]);

        if ($request->hasFile('photo')) {
            $validated['photo'] = $request->file('photo')->store('temoignages', 'public');
        }

        $temoignage = Temoignage::create($validated);

        return response()->json($temoignage, 201);
    }

    #[
        OA\Put(
            path: "/api/admin/temoignages/{id}",
            tags: ["Témoignages"],
            summary: "Mettre à jour un témoignage (admin)",
            security: [["bearerAuth" => []]],
            parameters: [new OA\Parameter(name: "id", in: "path", required: true, schema: new OA\Schema(type: "integer"))],
            responses: [new OA\Response(response: 200, description: "Témoignage mis à jour")],
        ),
    ]
    public function update(Request $request, Temoignage $temoignage)
    {
        $validated = $request->validate([
            'nom' => 'sometimes|string|max:255',
            'role' => 'sometimes|string|max:255',
            'message' => 'sometimes|string',
            'actif' => 'nullable|boolean',
            'photo' => 'nullable|file|mimes:jpg,jpeg,png,webp|max:5120',
        ]);

        if ($request->hasFile('photo')) {
            if ($temoignage->photo && Storage::disk('public')->exists($temoignage->photo)) {
                Storage::disk('public')->delete($temoignage->photo);
            }
            $validated['photo'] = $request->file('photo')->store('temoignages', 'public');
        }

        $temoignage->update($validated);

        return response()->json($temoignage);
    }

    #[
        OA\Delete(
            path: "/api/admin/temoignages/{id}",
            tags: ["Témoignages"],
            summary: "Supprimer un témoignage (admin)",
            security: [["bearerAuth" => []]],
            parameters: [new OA\Parameter(name: "id", in: "path", required: true, schema: new OA\Schema(type: "integer"))],
            responses: [new OA\Response(response: 200, description: "Témoignage supprimé")],
        ),
    ]
    public function destroy(Temoignage $temoignage)
    {
        if ($temoignage->photo && Storage::disk('public')->exists($temoignage->photo)) {
            Storage::disk('public')->delete($temoignage->photo);
        }

        $temoignage->delete();

        return response()->json(['message' => 'Témoignage supprimé'], 200);
    }
}
