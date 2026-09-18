<?php

namespace App\Http\Controllers;

use App\Models\Evenement;
use App\Models\ResponsableRegional;
use App\Models\Site;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use OpenApi\Attributes as OA;

class ResponsableRegionalController extends Controller
{
    #[
        OA\Get(
            path: "/api/admin/responsables",
            tags: ["Responsables régionaux"],
            summary: "Lister les responsables régionaux (admin)",
            security: [["bearerAuth" => []]],
            responses: [
                new OA\Response(response: 200, description: "Liste des responsables"),
            ],
        ),
    ]
    public function index()
    {
        return response()->json(ResponsableRegional::with('region')->get());
    }

    #[
        OA\Post(
            path: "/api/admin/responsables",
            tags: ["Responsables régionaux"],
            summary: "Créer un responsable régional (admin) — pas d'auto-inscription, poste officiel",
            security: [["bearerAuth" => []]],
            requestBody: new OA\RequestBody(
                required: true,
                content: new OA\JsonContent(
                    required: ["nom", "prenom", "tel", "password"],
                    properties: [
                        new OA\Property(property: "nom", type: "string"),
                        new OA\Property(property: "prenom", type: "string"),
                        new OA\Property(property: "tel", type: "string"),
                        new OA\Property(property: "password", type: "string"),
                        new OA\Property(
                            property: "id_region",
                            type: "integer",
                            description: "Laisser vide pour un responsable global (valide dans toutes les régions)",
                        ),
                    ],
                ),
            ),
            responses: [
                new OA\Response(response: 201, description: "Responsable créé"),
            ],
        ),
    ]
    public function store(Request $request)
    {
        $validated = $request->validate([
            'nom'       => 'required|string|max:100',
            'prenom'    => 'required|string|max:100',
            'tel'       => 'required|string|max:20|unique:responsable_regional,tel',
            'password'  => 'required|string|min:6',
            'status'    => 'nullable|boolean',
            'id_region' => 'nullable|exists:region,id',
        ]);

        $validated['password'] = Hash::make($validated['password']);
        $responsable = ResponsableRegional::create($validated);

        return response()->json($responsable->load('region'), 201);
    }

    #[
        OA\Get(
            path: "/api/admin/responsables/{id}",
            tags: ["Responsables régionaux"],
            summary: "Afficher un responsable régional (admin)",
            security: [["bearerAuth" => []]],
            parameters: [
                new OA\Parameter(name: "id", in: "path", required: true, schema: new OA\Schema(type: "integer")),
            ],
            responses: [
                new OA\Response(response: 200, description: "Détails du responsable"),
            ],
        ),
    ]
    public function show(ResponsableRegional $responsable)
    {
        return response()->json($responsable->load('region'));
    }

    #[
        OA\Put(
            path: "/api/admin/responsables/{id}",
            tags: ["Responsables régionaux"],
            summary: "Mettre à jour un responsable régional (admin)",
            security: [["bearerAuth" => []]],
            parameters: [
                new OA\Parameter(name: "id", in: "path", required: true, schema: new OA\Schema(type: "integer")),
            ],
            responses: [
                new OA\Response(response: 200, description: "Responsable mis à jour"),
            ],
        ),
    ]
    public function update(Request $request, ResponsableRegional $responsable)
    {
        $validated = $request->validate([
            'nom'       => 'sometimes|string|max:100',
            'prenom'    => 'sometimes|string|max:100',
            'tel'       => 'sometimes|string|max:20|unique:responsable_regional,tel,' . $responsable->id,
            'password'  => 'sometimes|string|min:6',
            'status'    => 'nullable|boolean',
            'id_region' => 'nullable|exists:region,id',
        ]);

        if (isset($validated['password'])) {
            $validated['password'] = Hash::make($validated['password']);
        }

        $responsable->update($validated);

        return response()->json($responsable->load('region'));
    }

    #[
        OA\Delete(
            path: "/api/admin/responsables/{id}",
            tags: ["Responsables régionaux"],
            summary: "Supprimer un responsable régional (admin)",
            security: [["bearerAuth" => []]],
            parameters: [
                new OA\Parameter(name: "id", in: "path", required: true, schema: new OA\Schema(type: "integer")),
            ],
            responses: [
                new OA\Response(response: 200, description: "Responsable supprimé"),
            ],
        ),
    ]
    public function destroy(ResponsableRegional $responsable)
    {
        $responsable->delete();

        return response()->json(['message' => 'Responsable supprimé'], 200);
    }

    #[
        OA\Get(
            path: "/api/responsable/a-valider",
            tags: ["Responsables régionaux"],
            summary: "Lister les sites/événements en attente dans mon périmètre (ma région, ou toutes si responsable global)",
            security: [["bearerAuth" => []]],
            responses: [
                new OA\Response(response: 200, description: "Sites et événements en attente"),
            ],
        ),
    ]
    public function aValider(Request $request)
    {
        $responsable = $request->user();

        // Une fiche créée par un responsable régional (id_responsable renseigné)
        // n'apparaît jamais dans la file d'un responsable — seul un admin la
        // valide (cf. SiteController/EvenementController::refuserSiHorsPerimetre).
        $sites = Site::with(['categorie', 'region', 'prestataire', 'admin'])
            ->where('status', 'en_attente')
            ->whereNull('id_responsable')
            ->when(!$responsable->estGlobal(), fn ($q) => $q->where('id_region', $responsable->id_region))
            ->get();

        $evenements = Evenement::with(['categorie', 'region', 'prestataire', 'admin'])
            ->where('status', 'en_attente')
            ->whereNull('id_responsable')
            ->when(!$responsable->estGlobal(), fn ($q) => $q->where('id_region', $responsable->id_region))
            ->get();

        return response()->json(['sites' => $sites, 'evenements' => $evenements]);
    }
}
