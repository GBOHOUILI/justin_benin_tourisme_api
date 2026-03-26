<?php

namespace App\Http\Controllers;

use App\Models\Fonctionnalite;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

class FonctionnaliteController extends Controller
{
    #[
        OA\Get(
            path: "/api/fonctionnalites",
            tags: ["Fonctionnalites"],
            summary: "Lister les fonctionnalités",
            parameters: [
                new OA\Parameter(
                    name: "type",
                    in: "query",
                    schema: new OA\Schema(type: "string"),
                ),
            ],
            responses: [
                new OA\Response(
                    response: 200,
                    description: "Liste des fonctionnalités",
                ),
            ],
        ),
    ]
    public function index(Request $request)
    {
        $query = Fonctionnalite::query();
        if ($request->filled("type")) {
            $query->where("type", $request->type);
        }
        return response()->json($query->get());
    }

    #[
        OA\Post(
            path: "/api/fonctionnalites",
            tags: ["Fonctionnalites"],
            summary: "Créer une fonctionnalité",
            requestBody: new OA\RequestBody(
                content: new OA\JsonContent(
                    required: ["libelle", "type"],
                    properties: [
                        new OA\Property(property: "libelle", type: "string"),
                        new OA\Property(
                            property: "type",
                            type: "string",
                            enum: ["admin", "user", "both"],
                        ),
                    ],
                ),
            ),
            responses: [
                new OA\Response(
                    response: 201,
                    description: "Fonctionnalité créée",
                ),
            ],
        ),
    ]
    public function store(Request $request)
    {
        $validated = $request->validate([
            "libelle" => "required|string|max:200",
            "type" => "required|string|in:admin,user,both",
        ]);
        $fonc = Fonctionnalite::create($validated);
        return response()->json($fonc, 201);
    }

    #[
        OA\Get(
            path: "/api/fonctionnalites/{id}",
            tags: ["Fonctionnalites"],
            summary: "Afficher une fonctionnalité",
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
                    description: "Détails de la fonctionnalité",
                ),
            ],
        ),
    ]
    public function show(Fonctionnalite $fonctionnalite)
    {
        return response()->json($fonctionnalite->load(["admins", "users"]));
    }

    #[
        OA\Put(
            path: "/api/fonctionnalites/{id}",
            tags: ["Fonctionnalites"],
            summary: "Mettre à jour une fonctionnalité",
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
                        new OA\Property(
                            property: "type",
                            type: "string",
                            enum: ["admin", "user", "both"],
                        ),
                    ],
                ),
            ),
            responses: [
                new OA\Response(
                    response: 200,
                    description: "Fonctionnalité mise à jour",
                ),
            ],
        ),
    ]
    public function update(Request $request, Fonctionnalite $fonctionnalite)
    {
        $validated = $request->validate([
            "libelle" => "sometimes|string|max:200",
            "type" => "sometimes|string|in:admin,user,both",
        ]);
        $fonctionnalite->update($validated);
        return response()->json($fonctionnalite);
    }

    #[
        OA\Delete(
            path: "/api/fonctionnalites/{id}",
            tags: ["Fonctionnalites"],
            summary: "Supprimer une fonctionnalité",
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
                    description: "Fonctionnalité supprimée",
                ),
            ],
        ),
    ]
    public function destroy(Fonctionnalite $fonctionnalite)
    {
        $fonctionnalite->delete();
        return response()->json(["message" => "Fonctionnalité supprimée"], 200);
    }

    #[
        OA\Post(
            path: "/api/fonctionnalites/{id}/assigner-admin",
            tags: ["Fonctionnalites"],
            summary: "Assigner une fonctionnalité à un admin",
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
                    required: ["id_admin"],
                    properties: [
                        new OA\Property(property: "id_admin", type: "integer"),
                        new OA\Property(property: "status", type: "boolean"),
                    ],
                ),
            ),
            responses: [
                new OA\Response(
                    response: 200,
                    description: "Accès admin attribué",
                ),
            ],
        ),
    ]
    public function assignerAdmin(
        Request $request,
        Fonctionnalite $fonctionnalite,
    ) {
        $request->validate([
            "id_admin" => "required|exists:admins,id",
            "status" => "nullable|boolean",
        ]);
        $fonctionnalite->admins()->syncWithoutDetaching([
            $request->id_admin => ["status" => $request->status ?? true],
        ]);
        return response()->json(["message" => "Accès admin attribué"]);
    }

    #[
        OA\Post(
            path: "/api/fonctionnalites/{id}/assigner-user",
            tags: ["Fonctionnalites"],
            summary: "Assigner une fonctionnalité à un utilisateur",
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
                    required: ["id_user"],
                    properties: [
                        new OA\Property(property: "id_user", type: "integer"),
                        new OA\Property(property: "status", type: "boolean"),
                    ],
                ),
            ),
            responses: [
                new OA\Response(
                    response: 200,
                    description: "Accès utilisateur attribué",
                ),
            ],
        ),
    ]
    public function assignerUser(
        Request $request,
        Fonctionnalite $fonctionnalite,
    ) {
        $request->validate([
            "id_user" => "required|exists:users,id",
            "status" => "nullable|boolean",
        ]);
        $fonctionnalite->users()->syncWithoutDetaching([
            $request->id_user => ["status" => $request->status ?? true],
        ]);
        return response()->json(["message" => "Accès utilisateur attribué"]);
    }
}
