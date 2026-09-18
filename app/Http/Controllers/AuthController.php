<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Admin;
use App\Models\Prestataire;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use OpenApi\Attributes as OA;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        $validated = $request->validate([
            'nom'           => 'required|string|max:100',
            'prenom'        => 'required|string|max:100',
            'tel'           => 'required|string|max:20',
            'email'         => 'required|email|unique:users,email',
            'password'      => 'required|string|min:8|confirmed',
            'nationalite'   => 'nullable|string|max:100',
            'longitude'     => 'nullable|numeric',
            'latitude'      => 'nullable|numeric',
        ]);

        $validated['password'] = Hash::make($validated['password']);
        $user = User::create($validated);
        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'message' => 'Inscription réussie',
            'user'    => $user,
            'token'   => $token,
            'type'    => 'Bearer',
        ], 201);
    }

    public function login(Request $request)
    {
        $request->validate([
            'email'    => 'required|email',
            'password' => 'required|string',
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['Les identifiants sont incorrects.'],
            ]);
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'message' => 'Connexion réussie',
            'user'    => $user,
            'token'   => $token,
            'type'    => 'Bearer',
        ]);
    }

    public function loginAdmin(Request $request)
    {
        $request->validate([
            'tel'      => 'required|string',
            'password' => 'required|string',
        ]);

        $admin = Admin::where('tel', $request->tel)->first();

        if (!$admin || !Hash::check($request->password, $admin->password)) {
            throw ValidationException::withMessages([
                'tel' => ['Identifiants incorrects.'],
            ]);
        }

        if (!$admin->status) {
            throw ValidationException::withMessages([
                'tel' => ['Ce compte admin est désactivé.'],
            ]);
        }

        $admin->tokens()->delete();
        $token = $admin->createToken('admin_token')->plainTextToken;

        return response()->json([
            'message' => 'Connexion admin réussie',
            'admin'   => $admin,
            'token'   => $token,
            'type'    => 'Bearer',
        ]);
    }

    #[
        OA\Post(
            path: "/api/prestataire/register",
            tags: ["Prestataires"],
            summary: "Créer un compte prestataire (hôtel, restaurant, transport, site, événement)",
            requestBody: new OA\RequestBody(
                required: true,
                content: new OA\JsonContent(
                    required: ["nom_entreprise", "type_prestataire", "email", "password", "password_confirmation"],
                    properties: [
                        new OA\Property(property: "nom_entreprise", type: "string"),
                        new OA\Property(property: "type_prestataire", type: "string", enum: ["site", "evenement", "hotel", "restaurant", "transport"]),
                        new OA\Property(property: "email", type: "string", format: "email"),
                        new OA\Property(property: "tel", type: "string"),
                        new OA\Property(property: "password", type: "string"),
                    ],
                ),
            ),
            responses: [
                new OA\Response(response: 201, description: "Compte prestataire créé, actif immédiatement (pas de workflow de validation pour l'instant)"),
            ],
        ),
    ]
    public function registerPrestataire(Request $request)
    {
        $validated = $request->validate([
            'nom_entreprise'   => 'required|string|max:200',
            'type_prestataire' => 'required|string|in:site,evenement,hotel,restaurant,transport',
            'email'            => 'required|email|unique:prestataire,email',
            'tel'              => 'nullable|string|max:20',
            'password'         => 'required|string|min:8|confirmed',
        ]);

        $validated['password'] = Hash::make($validated['password']);
        $prestataire = Prestataire::create($validated);
        $token = $prestataire->createToken('prestataire_token')->plainTextToken;

        return response()->json([
            'message'     => 'Compte prestataire créé',
            'prestataire' => $prestataire,
            'token'       => $token,
            'type'        => 'Bearer',
        ], 201);
    }

    #[
        OA\Post(
            path: "/api/prestataire/login",
            tags: ["Prestataires"],
            summary: "Connexion prestataire",
            requestBody: new OA\RequestBody(
                required: true,
                content: new OA\JsonContent(
                    required: ["email", "password"],
                    properties: [
                        new OA\Property(property: "email", type: "string", format: "email"),
                        new OA\Property(property: "password", type: "string"),
                    ],
                ),
            ),
            responses: [
                new OA\Response(response: 200, description: "Connexion réussie"),
                new OA\Response(response: 422, description: "Identifiants incorrects ou compte désactivé"),
            ],
        ),
    ]
    public function loginPrestataire(Request $request)
    {
        $request->validate([
            'email'    => 'required|email',
            'password' => 'required|string',
        ]);

        $prestataire = Prestataire::where('email', $request->email)->first();

        if (!$prestataire || !Hash::check($request->password, $prestataire->password)) {
            throw ValidationException::withMessages([
                'email' => ['Les identifiants sont incorrects.'],
            ]);
        }

        if (!$prestataire->status) {
            throw ValidationException::withMessages([
                'email' => ['Ce compte prestataire est désactivé.'],
            ]);
        }

        $token = $prestataire->createToken('prestataire_token')->plainTextToken;

        return response()->json([
            'message'     => 'Connexion réussie',
            'prestataire' => $prestataire,
            'token'       => $token,
            'type'        => 'Bearer',
        ]);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();
        return response()->json(['message' => 'Déconnexion réussie']);
    }

    public function me(Request $request)
    {
        // CORRECTION CRITIQUE : on ne charge PLUS les relations lourdes
        // qui causaient la récursion infinie (reservations → user → reservations)
        $user = $request->user();
        return response()->json($user);
    }

    public function updatePassword(Request $request)
    {
        $request->validate([
            'current_password' => 'required|string',
            'password'         => 'required|string|min:8|confirmed',
        ]);

        $user = $request->user();

        if (!Hash::check($request->current_password, $user->password)) {
            throw ValidationException::withMessages([
                'current_password' => ['Le mot de passe actuel est incorrect.'],
            ]);
        }

        $user->update(['password' => Hash::make($request->password)]);
        $user->tokens()->delete();

        return response()->json([
            'message' => 'Mot de passe mis à jour. Veuillez vous reconnecter.',
        ]);
    }
}