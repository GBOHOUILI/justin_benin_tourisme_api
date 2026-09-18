<?php

namespace App\Http\Controllers;

use App\Models\Plan;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

class PlanController extends Controller
{
    #[
        OA\Get(
            path: "/api/plans",
            tags: ["Plans"],
            summary: "Lister les plans d'abonnement disponibles",
            responses: [new OA\Response(response: 200, description: "Liste des plans")],
        ),
    ]
    public function index()
    {
        return response()->json(Plan::orderBy('prix_mensuel')->get());
    }

    public function show(Plan $plan)
    {
        return response()->json($plan);
    }

    #[
        OA\Post(
            path: "/api/admin/plans",
            tags: ["Plans"],
            summary: "Créer un plan d'abonnement (admin)",
            security: [["bearerAuth" => []]],
            requestBody: new OA\RequestBody(
                required: true,
                content: new OA\JsonContent(
                    required: ["nom", "prix_mensuel"],
                    properties: [
                        new OA\Property(property: "nom", type: "string"),
                        new OA\Property(property: "prix_mensuel", type: "number"),
                        new OA\Property(property: "nombre_fiches_max", type: "integer", description: "Null = illimité"),
                        new OA\Property(property: "fonctionnalites", type: "array", items: new OA\Items(type: "string")),
                    ],
                ),
            ),
            responses: [new OA\Response(response: 201, description: "Plan créé")],
        ),
    ]
    public function store(Request $request)
    {
        $validated = $request->validate([
            'nom' => 'required|string|max:100|unique:plan,nom',
            'prix_mensuel' => 'required|numeric|min:0',
            'nombre_fiches_max' => 'nullable|integer|min:1',
            'fonctionnalites' => 'nullable|array',
            'fonctionnalites.*' => 'string',
        ]);

        $plan = Plan::create($validated);
        return response()->json($plan, 201);
    }

    #[
        OA\Put(
            path: "/api/admin/plans/{id}",
            tags: ["Plans"],
            summary: "Mettre à jour un plan d'abonnement (admin)",
            security: [["bearerAuth" => []]],
            parameters: [new OA\Parameter(name: "id", in: "path", required: true, schema: new OA\Schema(type: "integer"))],
            responses: [new OA\Response(response: 200, description: "Plan mis à jour")],
        ),
    ]
    public function update(Request $request, Plan $plan)
    {
        $validated = $request->validate([
            'nom' => 'sometimes|string|max:100|unique:plan,nom,' . $plan->id,
            'prix_mensuel' => 'sometimes|numeric|min:0',
            'nombre_fiches_max' => 'nullable|integer|min:1',
            'fonctionnalites' => 'nullable|array',
            'fonctionnalites.*' => 'string',
        ]);

        $plan->update($validated);
        return response()->json($plan);
    }

    #[
        OA\Delete(
            path: "/api/admin/plans/{id}",
            tags: ["Plans"],
            summary: "Supprimer un plan d'abonnement (admin)",
            security: [["bearerAuth" => []]],
            parameters: [new OA\Parameter(name: "id", in: "path", required: true, schema: new OA\Schema(type: "integer"))],
            responses: [
                new OA\Response(response: 200, description: "Plan supprimé"),
                new OA\Response(response: 422, description: "Des abonnements référencent encore ce plan"),
            ],
        ),
    ]
    public function destroy(Plan $plan)
    {
        if ($plan->abonnements()->exists()) {
            return response()->json([
                'message' => "Impossible de supprimer ce plan : des abonnements y sont rattachés.",
            ], 422);
        }

        $plan->delete();
        return response()->json(['message' => 'Plan supprimé'], 200);
    }
}
