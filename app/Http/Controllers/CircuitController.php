<?php

namespace App\Http\Controllers;

use App\Models\Circuit;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

class CircuitController extends Controller
{
    private function chargerRelations()
    {
        return ['etapes.site', 'etapes.evenement', 'etapes.reservation'];
    }

    #[
        OA\Get(
            path: "/api/circuits",
            tags: ["Circuits"],
            summary: "Lister mes circuits",
            security: [["sanctum" => []]],
            responses: [
                new OA\Response(response: 200, description: "Liste des circuits de l'utilisateur connecté"),
            ],
        ),
    ]
    public function index(Request $request)
    {
        $circuits = Circuit::with($this->chargerRelations())
            ->where('id_user', $request->user()->id)
            ->latest()
            ->paginate(15);

        return response()->json($circuits);
    }

    #[
        OA\Post(
            path: "/api/circuits",
            tags: ["Circuits"],
            summary: "Créer un circuit (itinéraire personnalisé, construit manuellement - pas d'IA pour l'instant)",
            security: [["sanctum" => []]],
            requestBody: new OA\RequestBody(
                required: true,
                content: new OA\JsonContent(
                    required: ["libelle"],
                    properties: [
                        new OA\Property(property: "libelle", type: "string"),
                        new OA\Property(property: "description", type: "string"),
                    ],
                ),
            ),
            responses: [
                new OA\Response(response: 201, description: "Circuit créé (genere_par_ia toujours false)"),
            ],
        ),
    ]
    public function store(Request $request)
    {
        $validated = $request->validate([
            'libelle'     => 'required|string|max:200',
            'description' => 'nullable|string',
        ]);

        // genere_par_ia n'est jamais accepté depuis le client : le module IA n'existe pas
        // encore, un circuit créé ici est toujours construit manuellement par le touriste.
        $validated['id_user']       = $request->user()->id;
        $validated['genere_par_ia'] = false;

        $circuit = Circuit::create($validated);

        return response()->json($circuit->load($this->chargerRelations()), 201);
    }

    #[
        OA\Get(
            path: "/api/circuits/{id}",
            tags: ["Circuits"],
            summary: "Afficher un circuit avec ses étapes ordonnées",
            security: [["sanctum" => []]],
            parameters: [
                new OA\Parameter(name: "id", in: "path", required: true, schema: new OA\Schema(type: "integer")),
            ],
            responses: [
                new OA\Response(response: 200, description: "Détails du circuit"),
                new OA\Response(response: 403, description: "Accès refusé"),
            ],
        ),
    ]
    public function show(Request $request, Circuit $circuit)
    {
        if ($circuit->id_user !== $request->user()->id) {
            return response()->json(['message' => 'Accès refusé.'], 403);
        }

        return response()->json($circuit->load($this->chargerRelations()));
    }

    #[
        OA\Put(
            path: "/api/circuits/{id}",
            tags: ["Circuits"],
            summary: "Mettre à jour un circuit",
            security: [["sanctum" => []]],
            parameters: [
                new OA\Parameter(name: "id", in: "path", required: true, schema: new OA\Schema(type: "integer")),
            ],
            requestBody: new OA\RequestBody(
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "libelle", type: "string"),
                        new OA\Property(property: "description", type: "string"),
                    ],
                ),
            ),
            responses: [
                new OA\Response(response: 200, description: "Circuit mis à jour"),
                new OA\Response(response: 403, description: "Accès refusé"),
            ],
        ),
    ]
    public function update(Request $request, Circuit $circuit)
    {
        if ($circuit->id_user !== $request->user()->id) {
            return response()->json(['message' => 'Accès refusé.'], 403);
        }

        $validated = $request->validate([
            'libelle'     => 'sometimes|string|max:200',
            'description' => 'nullable|string',
        ]);

        $circuit->update($validated);

        return response()->json($circuit->load($this->chargerRelations()));
    }

    #[
        OA\Delete(
            path: "/api/circuits/{id}",
            tags: ["Circuits"],
            summary: "Supprimer un circuit (les réservations liées à ses étapes ne sont jamais supprimées)",
            security: [["sanctum" => []]],
            parameters: [
                new OA\Parameter(name: "id", in: "path", required: true, schema: new OA\Schema(type: "integer")),
            ],
            responses: [
                new OA\Response(response: 200, description: "Circuit supprimé"),
                new OA\Response(response: 403, description: "Accès refusé"),
            ],
        ),
    ]
    public function destroy(Request $request, Circuit $circuit)
    {
        if ($circuit->id_user !== $request->user()->id) {
            return response()->json(['message' => 'Accès refusé.'], 403);
        }

        $circuit->delete();

        return response()->json(['message' => 'Circuit supprimé.'], 200);
    }
}
