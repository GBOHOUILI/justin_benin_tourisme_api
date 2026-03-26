<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use OpenApi\Attributes as OA;

class UserController extends Controller
{
    #[
        OA\Get(
            path: "/api/users",
            tags: ["Users"],
            summary: "Liste des utilisateurs",
            responses: [
                new OA\Response(
                    response: 200,
                    description: "Liste des utilisateurs paginée",
                ),
            ],
        ),
    ]
    public function index()
    {
        return response()->json(User::paginate(20));
    }

    #[
        OA\Post(
            path: "/api/users",
            tags: ["Users"],
            summary: "Créer un utilisateur",
            requestBody: new OA\RequestBody(
                required: true,
                content: new OA\JsonContent(
                    required: [
                        "nom",
                        "prenom",
                        "tel",
                        "email",
                        "password",
                        "password_confirmation",
                    ],
                    properties: [
                        new OA\Property(
                            property: "nom",
                            type: "string",
                            example: "Doe",
                        ),
                        new OA\Property(
                            property: "prenom",
                            type: "string",
                            example: "John",
                        ),
                        new OA\Property(
                            property: "tel",
                            type: "string",
                            example: "+22901000000",
                        ),
                        new OA\Property(
                            property: "email",
                            type: "string",
                            example: "john@example.com",
                        ),
                        new OA\Property(
                            property: "password",
                            type: "string",
                            example: "password123",
                        ),
                        new OA\Property(
                            property: "password_confirmation",
                            type: "string",
                            example: "password123",
                        ),
                        new OA\Property(
                            property: "nationalite",
                            type: "string",
                            example: "Béninoise",
                        ),
                        new OA\Property(property: "longitude", type: "number"),
                        new OA\Property(property: "latitude", type: "number"),
                    ],
                ),
            ),
            responses: [
                new OA\Response(response: 201, description: "Utilisateur créé"),
                new OA\Response(
                    response: 422,
                    description: "Erreur de validation",
                ),
            ],
        ),
    ]
    public function store(Request $request)
    {
        $validated = $request->validate([
            "nom" => "required|string|max:100",
            "prenom" => "required|string|max:100",
            "tel" => "required|string|max:20",
            "email" => "required|email|unique:users,email",
            "password" => "required|string|min:8|confirmed",
            "nationalite" => "nullable|string|max:100",
            "longitude" => "nullable|numeric",
            "latitude" => "nullable|numeric",
        ]);

        $validated["password"] = Hash::make($validated["password"]);
        $user = User::create($validated);
        return response()->json($user, 201);
    }

    #[
        OA\Get(
            path: "/api/users/{id}",
            tags: ["Users"],
            summary: "Afficher un utilisateur",
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
                    description: "Utilisateur affiché",
                ),
            ],
        ),
    ]
    public function show(User $user)
    {
        return response()->json(
            $user->load(["reservations.tickets", "fonctionnalites"]),
        );
    }

    #[
        OA\Put(
            path: "/api/users/{id}",
            tags: ["Users"],
            summary: "Mettre à jour un utilisateur",
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
                        new OA\Property(property: "nom", type: "string"),
                        new OA\Property(property: "prenom", type: "string"),
                        new OA\Property(property: "tel", type: "string"),
                        new OA\Property(property: "email", type: "string"),
                        new OA\Property(property: "password", type: "string"),
                        new OA\Property(
                            property: "password_confirmation",
                            type: "string",
                        ),
                        new OA\Property(
                            property: "nationalite",
                            type: "string",
                        ),
                        new OA\Property(property: "longitude", type: "number"),
                        new OA\Property(property: "latitude", type: "number"),
                    ],
                ),
            ),
            responses: [
                new OA\Response(
                    response: 200,
                    description: "Utilisateur mis à jour",
                ),
            ],
        ),
    ]
    public function update(Request $request, User $user)
    {
        $validated = $request->validate([
            "nom" => "sometimes|string|max:100",
            "prenom" => "sometimes|string|max:100",
            "tel" => "sometimes|string|max:20",
            "email" => "sometimes|email|unique:users,email," . $user->id,
            "password" => "sometimes|string|min:8|confirmed",
            "nationalite" => "nullable|string|max:100",
            "longitude" => "nullable|numeric",
            "latitude" => "nullable|numeric",
        ]);

        if (isset($validated["password"])) {
            $validated["password"] = Hash::make($validated["password"]);
        }

        $user->update($validated);
        return response()->json($user);
    }

    #[
        OA\Delete(
            path: "/api/users/{id}",
            tags: ["Users"],
            summary: "Supprimer un utilisateur",
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
                    description: "Utilisateur supprimé",
                ),
            ],
        ),
    ]
    public function destroy(User $user)
    {
        $user->delete();
        return response()->json(["message" => "Utilisateur supprimé"], 200);
    }
}
