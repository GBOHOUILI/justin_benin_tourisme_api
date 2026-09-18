<?php

namespace App\Http\Controllers;

use App\Models\Ville;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

/**
 * Contrairement à Region (12 départements fixes, seedés), la liste des
 * villes est ouverte — un admin en ajoute au fil de l'eau (nécessaires
 * pour définir un Trajet entre deux villes).
 */
class VilleController extends Controller
{
    #[
        OA\Get(
            path: "/api/villes",
            tags: ["Villes"],
            summary: "Lister les villes",
            responses: [new OA\Response(response: 200, description: "Liste des villes")],
        ),
    ]
    public function index()
    {
        return response()->json(Ville::with('region')->orderBy('nom')->get());
    }

    #[
        OA\Post(
            path: "/api/admin/villes",
            tags: ["Villes"],
            summary: "Créer une ville (admin)",
            security: [["bearerAuth" => []]],
            responses: [new OA\Response(response: 201, description: "Ville créée")],
        ),
    ]
    public function store(Request $request)
    {
        $validated = $request->validate([
            'nom' => 'required|string|max:150|unique:ville,nom',
            'id_region' => 'nullable|exists:region,id',
        ]);

        $ville = Ville::create($validated);
        return response()->json($ville->load('region'), 201);
    }

    public function show(Ville $ville)
    {
        return response()->json($ville->load('region'));
    }

    #[
        OA\Put(
            path: "/api/admin/villes/{id}",
            tags: ["Villes"],
            summary: "Mettre à jour une ville (admin)",
            security: [["bearerAuth" => []]],
            responses: [new OA\Response(response: 200, description: "Ville mise à jour")],
        ),
    ]
    public function update(Request $request, Ville $ville)
    {
        $validated = $request->validate([
            'nom' => 'sometimes|string|max:150|unique:ville,nom,' . $ville->id,
            'id_region' => 'nullable|exists:region,id',
        ]);

        $ville->update($validated);
        return response()->json($ville->load('region'));
    }

    #[
        OA\Delete(
            path: "/api/admin/villes/{id}",
            tags: ["Villes"],
            summary: "Supprimer une ville (admin)",
            security: [["bearerAuth" => []]],
            responses: [new OA\Response(response: 200, description: "Ville supprimée")],
        ),
    ]
    public function destroy(Ville $ville)
    {
        $ville->delete();
        return response()->json(['message' => 'Ville supprimée'], 200);
    }
}
