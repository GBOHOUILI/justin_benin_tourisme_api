<?php

namespace App\Http\Controllers;

use App\Models\Admin;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use OpenApi\Attributes as OA;

class AdminController extends Controller
{
    #[OA\Get(
        path: "/admins",
        tags: ["Admins"],
        summary: "Lister les administrateurs",
        responses: [new OA\Response(response: 200, description: "Liste des admins")]
    )]
    public function index()
    {
        return response()->json(Admin::all());
    }

    #[OA\Post(
        path: "/admins",
        tags: ["Admins"],
        summary: "Créer un admin",
        requestBody: new OA\RequestBody(
            content: new OA\JsonContent(
                required: ["nom","prenom","tel","pass"],
                properties: [
                    new OA\Property(property: "nom", type: "string"),
                    new OA\Property(property: "prenom", type: "string"),
                    new OA\Property(property: "tel", type: "string"),
                    new OA\Property(property: "pass", type: "string"),
                    new OA\Property(property: "status", type: "boolean")
                ]
            )
        ),
        responses: [new OA\Response(response: 201, description: "Admin créé")]
    )]
    public function store(Request $request)
    {
        $validated = $request->validate([
            'nom' => 'required|string|max:100',
            'prenom' => 'required|string|max:100',
            'tel' => 'required|string|max:20',
            'pass' => 'required|string|min:6',
            'status' => 'nullable|boolean',
        ]);
        $validated['pass'] = Hash::make($validated['pass']);
        $admin = Admin::create($validated);
        return response()->json($admin, 201);
    }

    #[OA\Get(
        path: "/admins/{id}",
        tags: ["Admins"],
        summary: "Afficher un admin",
        parameters: [new OA\Parameter(name: "id", in: "path", required: true, schema: new OA\Schema(type: "integer"))],
        responses: [new OA\Response(response: 200, description: "Détails de l'admin")]
    )]
    public function show(Admin $admin)
    {
        return response()->json($admin->load(['sites','evenements','fonctionnalites']));
    }

    #[OA\Put(
        path: "/admins/{id}",
        tags: ["Admins"],
        summary: "Mettre à jour un admin",
        parameters: [new OA\Parameter(name: "id", in: "path", required: true, schema: new OA\Schema(type: "integer"))],
        requestBody: new OA\RequestBody(
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: "nom", type: "string"),
                    new OA\Property(property: "prenom", type: "string"),
                    new OA\Property(property: "tel", type: "string"),
                    new OA\Property(property: "pass", type: "string"),
                    new OA\Property(property: "status", type: "boolean")
                ]
            )
        ),
        responses: [new OA\Response(response: 200, description: "Admin mis à jour")]
    )]
    public function update(Request $request, Admin $admin)
    {
        $validated = $request->validate([
            'nom' => 'sometimes|string|max:100',
            'prenom' => 'sometimes|string|max:100',
            'tel' => 'sometimes|string|max:20',
            'pass' => 'sometimes|string|min:6',
            'status' => 'nullable|boolean',
        ]);
        if (isset($validated['pass'])) $validated['pass'] = Hash::make($validated['pass']);
        $admin->update($validated);
        return response()->json($admin);
    }

    #[OA\Delete(
        path: "/admins/{id}",
        tags: ["Admins"],
        summary: "Supprimer un admin",
        parameters: [new OA\Parameter(name: "id", in: "path", required: true, schema: new OA\Schema(type: "integer"))],
        responses: [new OA\Response(response: 200, description: "Admin supprimé")]
    )]
    public function destroy(Admin $admin)
    {
        $admin->delete();
        return response()->json(['message' => 'Admin supprimé avec succès'], 200);
    }
}