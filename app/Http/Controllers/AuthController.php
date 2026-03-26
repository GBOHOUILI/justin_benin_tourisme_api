<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Admin;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use OpenApi\Attributes as OA;

class AuthController extends Controller
{
    // ─── REGISTER ─────────────────────────────────────────────
    #[
        OA\Post(
            path: "/api/register",
            tags: ["Auth"],
            summary: "Inscription d'un nouvel utilisateur",
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
                    ],
                ),
            ),
            responses: [
                new OA\Response(
                    response: 201,
                    description: "Inscription réussie",
                ),
                new OA\Response(
                    response: 422,
                    description: "Erreur de validation",
                ),
            ],
        ),
    ]
    public function register(Request $request)
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
        $token = $user->createToken("auth_token")->plainTextToken;

        return response()->json(
            [
                "message" => "Inscription réussie",
                "user" => $user,
                "token" => $token,
                "type" => "Bearer",
            ],
            201,
        );
    }

    // ─── LOGIN USER ────────────────────────────────────────────
    #[
        OA\Post(
            path: "/api/login",
            tags: ["Auth"],
            summary: "Connexion utilisateur",
            requestBody: new OA\RequestBody(
                required: true,
                content: new OA\JsonContent(
                    required: ["email", "password"],
                    properties: [
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
                    ],
                ),
            ),
            responses: [
                new OA\Response(
                    response: 200,
                    description: "Connexion réussie",
                ),
                new OA\Response(
                    response: 422,
                    description: "Identifiants incorrects",
                ),
            ],
        ),
    ]
    public function login(Request $request)
    {
        $request->validate([
            "email" => "required|email",
            "password" => "required|string",
        ]);

        $user = User::where("email", $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                "email" => ["Les identifiants sont incorrects."],
            ]);
        }

        $token = $user->createToken("auth_token")->plainTextToken;

        return response()->json([
            "message" => "Connexion réussie",
            "user" => $user,
            "token" => $token,
            "type" => "Bearer",
        ]);
    }

    // ─── LOGIN ADMIN ───────────────────────────────────────────
    #[
        OA\Post(
            path: "/api/admin/login",
            tags: ["Auth"],
            summary: "Connexion administrateur",
            requestBody: new OA\RequestBody(
                required: true,
                content: new OA\JsonContent(
                    required: ["tel", "password"],
                    properties: [
                        new OA\Property(
                            property: "tel",
                            type: "string",
                            example: "+22901000000",
                        ),
                        new OA\Property(
                            property: "password",
                            type: "string",
                            example: "admin123",
                        ),
                    ],
                ),
            ),
            responses: [
                new OA\Response(
                    response: 200,
                    description: "Connexion admin réussie",
                ),
                new OA\Response(
                    response: 422,
                    description: "Identifiants incorrects",
                ),
            ],
        ),
    ]
    public function loginAdmin(Request $request)
    {
        $request->validate([
            "tel" => "required|string",
            "password" => "required|string",
        ]);

        $admin = Admin::where("tel", $request->tel)->first();

        if (!$admin || !Hash::check($request->password, $admin->password)) {
            throw ValidationException::withMessages([
                "tel" => ["Identifiants incorrects."],
            ]);
        }

        if (!$admin->status) {
            throw ValidationException::withMessages([
                "tel" => ["Ce compte admin est désactivé."],
            ]);
        }

        // Révoquer les anciens tokens pour éviter la prolifération de sessions
        $admin->tokens()->delete();

        // On crée le token via le guard 'admin' pour qu'il soit lié au provider admins
        $token = $admin->createToken("admin_token")->plainTextToken;

        return response()->json([
            "message" => "Connexion admin réussie",
            "admin" => $admin,
            "token" => $token,
            "type" => "Bearer",
        ]);
    }

    // ─── LOGOUT ────────────────────────────────────────────────
    #[
        OA\Post(
            path: "/api/logout",
            tags: ["Auth"],
            summary: "Déconnexion (user ou admin)",
            security: [["bearerAuth" => []]],
            responses: [
                new OA\Response(
                    response: 200,
                    description: "Déconnexion réussie",
                ),
            ],
        ),
    ]
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(["message" => "Déconnexion réussie"]);
    }

    // ─── ME ────────────────────────────────────────────────────
    #[
        OA\Get(
            path: "/api/me",
            tags: ["Auth"],
            summary: "Profil de l'utilisateur ou admin connecté",
            security: [["bearerAuth" => []]],
            responses: [
                new OA\Response(response: 200, description: "Profil retourné"),
                new OA\Response(response: 401, description: "Non authentifié"),
            ],
        ),
    ]
    public function me(Request $request)
    {
        $user = $request->user();

        if ($user instanceof Admin) {
            return response()->json(
                $user->load(["sites", "evenements", "fonctionnalites"]),
            );
        }

        return response()->json(
            $user->load(["reservations", "fonctionnalites"]),
        );
    }

    // ─── UPDATE PASSWORD ───────────────────────────────────────
    #[
        OA\Post(
            path: "/api/update-password",
            tags: ["Auth"],
            summary: "Mise à jour du mot de passe",
            security: [["bearerAuth" => []]],
            requestBody: new OA\RequestBody(
                required: true,
                content: new OA\JsonContent(
                    required: [
                        "current_password",
                        "password",
                        "password_confirmation",
                    ],
                    properties: [
                        new OA\Property(
                            property: "current_password",
                            type: "string",
                        ),
                        new OA\Property(property: "password", type: "string"),
                        new OA\Property(
                            property: "password_confirmation",
                            type: "string",
                        ),
                    ],
                ),
            ),
            responses: [
                new OA\Response(
                    response: 200,
                    description: "Mot de passe mis à jour",
                ),
                new OA\Response(
                    response: 422,
                    description: "Mot de passe actuel incorrect",
                ),
            ],
        ),
    ]
    public function updatePassword(Request $request)
    {
        $request->validate([
            "current_password" => "required|string",
            "password" => "required|string|min:8|confirmed",
        ]);

        $user = $request->user();

        if (!Hash::check($request->current_password, $user->password)) {
            throw ValidationException::withMessages([
                "current_password" => ["Le mot de passe actuel est incorrect."],
            ]);
        }

        $user->update(["password" => Hash::make($request->password)]);
        $user->tokens()->delete();

        return response()->json([
            "message" => "Mot de passe mis à jour. Veuillez vous reconnecter.",
        ]);
    }
}
