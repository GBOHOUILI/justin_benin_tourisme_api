<?php

namespace App\Http\Controllers;

use App\Models\Evenement;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

class EvenementController extends Controller
{
    #[OA\Get(
        path: "/evenements",
        tags: ["Evenements"],
        summary: "Lister les événements",
        parameters: [
            new OA\Parameter(name: "libelle", in: "query", schema: new OA\Schema(type: "string")),
            new OA\Parameter(name: "id_cat_evenmt", in: "query", schema: new OA\Schema(type: "integer")),
            new OA\Parameter(name: "status", in: "query", schema: new OA\Schema(type: "string")),
            new OA\Parameter(name: "date_debut", in: "query", schema: new OA\Schema(type: "string", format: "date"))
        ],
        responses: [new OA\Response(response: 200, description: "Liste des événements")]
    )]
    public function index(Request $request)
    {
        $query = Evenement::with(['categorie', 'galeries', 'prix']);
        if ($request->filled('libelle')) $query->where('libelle', 'like', '%' . $request->libelle . '%');
        if ($request->filled('id_cat_evenmt')) $query->where('id_cat_evenmt', $request->id_cat_evenmt);
        if ($request->filled('status')) $query->where('status', $request->status);
        if ($request->filled('date_debut')) $query->whereDate('date_debut', '>=', $request->date_debut);
        return response()->json($query->paginate(12));
    }

    #[OA\Post(
        path: "/evenements",
        tags: ["Evenements"],
        summary: "Créer un événement",
        requestBody: new OA\RequestBody(
            content: new OA\JsonContent(
                required: ["libelle", "adresse", "longitude", "latitude", "date_debut", "date_fin", "id_cat_evenmt", "id_admin"],
                properties: [
                    new OA\Property(property: "libelle", type: "string"),
                    new OA\Property(property: "adresse", type: "string"),
                    new OA\Property(property: "longitude", type: "number"),
                    new OA\Property(property: "latitude", type: "number"),
                    new OA\Property(property: "description", type: "string"),
                    new OA\Property(property: "date_debut", type: "string", format: "date"),
                    new OA\Property(property: "date_fin", type: "string", format: "date"),
                    new OA\Property(property: "status", type: "string", enum: ["en_attente", "valide", "rejete", "suspendu"]),
                    new OA\Property(property: "id_cat_evenmt", type: "integer"),
                    new OA\Property(property: "id_admin", type: "integer")
                ]
            )
        ),
        responses: [new OA\Response(response: 201, description: "Événement créé")]
    )]
    public function store(Request $request)
    {
        $validated = $request->validate([
            'libelle' => 'required|string|max:200',
            'adresse' => 'required|string|max:255',
            'longitude' => 'required|numeric',
            'latitude' => 'required|numeric',
            'description' => 'nullable|string',
            'date_debut' => 'required|date',
            'date_fin' => 'required|date|after_or_equal:date_debut',
            'status' => 'nullable|string|in:en_attente,valide,rejete,suspendu',
            'id_cat_evenmt' => 'required|exists:cat_evenmts,id',
            'id_admin' => 'required|exists:admins,id',
        ]);
        $evenement = Evenement::create($validated);
        return response()->json($evenement->load(['categorie', 'admin']), 201);
    }

    #[OA\Get(
        path: "/evenements/{id}",
        tags: ["Evenements"],
        summary: "Afficher un événement",
        parameters: [new OA\Parameter(name: "id", in: "path", required: true, schema: new OA\Schema(type: "integer"))],
        responses: [new OA\Response(response: 200, description: "Détails de l'événement")]
    )]
    public function show(Evenement $evenement)
    {
        return response()->json($evenement->load(['categorie', 'admin', 'galeries', 'prix', 'sites']));
    }

    #[OA\Put(
        path: "/evenements/{id}",
        tags: ["Evenements"],
        summary: "Mettre à jour un événement",
        parameters: [new OA\Parameter(name: "id", in: "path", required: true, schema: new OA\Schema(type: "integer"))],
        requestBody: new OA\RequestBody(
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: "libelle", type: "string"),
                    new OA\Property(property: "adresse", type: "string"),
                    new OA\Property(property: "longitude", type: "number"),
                    new OA\Property(property: "latitude", type: "number"),
                    new OA\Property(property: "description", type: "string"),
                    new OA\Property(property: "date_debut", type: "string", format: "date"),
                    new OA\Property(property: "date_fin", type: "string", format: "date"),
                    new OA\Property(property: "status", type: "string", enum: ["en_attente", "valide", "rejete", "suspendu"]),
                    new OA\Property(property: "id_cat_evenmt", type: "integer"),
                    new OA\Property(property: "id_admin", type: "integer")
                ]
            )
        ),
        responses: [new OA\Response(response: 200, description: "Événement mis à jour")]
    )]
    public function update(Request $request, Evenement $evenement)
    {
        $validated = $request->validate([
            'libelle' => 'sometimes|string|max:200',
            'adresse' => 'sometimes|string|max:255',
            'longitude' => 'sometimes|numeric',
            'latitude' => 'sometimes|numeric',
            'description' => 'nullable|string',
            'date_debut' => 'sometimes|date',
            'date_fin' => 'sometimes|date',
            'status' => 'nullable|string|in:en_attente,valide,rejete,suspendu',
            'id_cat_evenmt' => 'sometimes|exists:cat_evenmts,id',
            'id_admin' => 'sometimes|exists:admins,id',
        ]);
        $evenement->update($validated);
        return response()->json($evenement->load(['categorie', 'admin']));
    }

    #[OA\Patch(
        path: "/evenements/{id}/valider",
        tags: ["Evenements"],
        summary: "Valider un événement",
        parameters: [new OA\Parameter(name: "id", in: "path", required: true, schema: new OA\Schema(type: "integer"))],
        responses: [new OA\Response(response: 200, description: "Événement validé")]
    )]
    public function valider(Evenement $evenement)
    {
        $evenement->update(['status' => 'valide']);
        return response()->json(['message' => 'Événement validé', 'evenement' => $evenement]);
    }

    #[OA\Patch(
        path: "/evenements/{id}/rejeter",
        tags: ["Evenements"],
        summary: "Rejeter un événement",
        parameters: [new OA\Parameter(name: "id", in: "path", required: true, schema: new OA\Schema(type: "integer"))],
        responses: [new OA\Response(response: 200, description: "Événement rejeté")]
    )]
    public function rejeter(Evenement $evenement)
    {
        $evenement->update(['status' => 'rejete']);
        return response()->json(['message' => 'Événement rejeté', 'evenement' => $evenement]);
    }

    #[OA\Delete(
        path: "/evenements/{id}",
        tags: ["Evenements"],
        summary: "Supprimer un événement",
        parameters: [new OA\Parameter(name: "id", in: "path", required: true, schema: new OA\Schema(type: "integer"))],
        responses: [new OA\Response(response: 200, description: "Événement supprimé")]
    )]
    public function destroy(Evenement $evenement)
    {
        $evenement->delete();
        return response()->json(['message' => 'Événement supprimé'], 200);
    }
}