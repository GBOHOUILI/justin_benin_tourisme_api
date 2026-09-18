<?php

namespace App\Http\Controllers;

use App\Models\Evenement;
use App\Models\Prestataire;
use App\Models\ResponsableRegional;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

class EvenementController extends Controller
{
    #[
        OA\Get(
            path: "/api/prestataire/evenements",
            tags: ["Prestataires"],
            summary: "Lister mes propres événements (prestataire connecté)",
            security: [["bearerAuth" => []]],
            responses: [
                new OA\Response(response: 200, description: "Liste paginée de mes événements"),
            ],
        ),
    ]
    public function mine(Request $request)
    {
        return response()->json(
            $request->user()->evenements()->with(["categorie", "galeries", "prix", "region"])->latest()->paginate(15)
        );
    }

    #[
        OA\Get(
            path: "/api/evenements",
            tags: ["Evenements"],
            summary: "Lister les événements",
            parameters: [
                new OA\Parameter(
                    name: "libelle",
                    in: "query",
                    schema: new OA\Schema(type: "string"),
                ),
                new OA\Parameter(
                    name: "id_cat_evenmt",
                    in: "query",
                    schema: new OA\Schema(type: "integer"),
                ),
                new OA\Parameter(
                    name: "status",
                    in: "query",
                    schema: new OA\Schema(type: "string"),
                ),
                new OA\Parameter(
                    name: "date_debut",
                    in: "query",
                    schema: new OA\Schema(type: "string", format: "date"),
                ),
                new OA\Parameter(
                    name: "lat",
                    in: "query",
                    description: "Latitude de référence (recherche par proximité)",
                    schema: new OA\Schema(type: "number"),
                ),
                new OA\Parameter(
                    name: "lng",
                    in: "query",
                    description: "Longitude de référence (recherche par proximité)",
                    schema: new OA\Schema(type: "number"),
                ),
                new OA\Parameter(
                    name: "radius",
                    in: "query",
                    description: "Rayon de recherche en km (nécessite lat/lng)",
                    schema: new OA\Schema(type: "number"),
                ),
                new OA\Parameter(
                    name: "prix_min",
                    in: "query",
                    description: "Prix minimum (filtre sur les tarifs de l'événement)",
                    schema: new OA\Schema(type: "number"),
                ),
                new OA\Parameter(
                    name: "prix_max",
                    in: "query",
                    description: "Prix maximum (filtre sur les tarifs de l'événement)",
                    schema: new OA\Schema(type: "number"),
                ),
            ],
            responses: [
                new OA\Response(
                    response: 200,
                    description: "Liste des événements, triée par distance si lat/lng fournis",
                ),
            ],
        ),
    ]
    /** Filtres communs (recherche, catégorie, date, prix, proximité) - pas le statut, géré par index()/adminIndex(). */
    private function requeteFiltree(Request $request)
    {
        $query = Evenement::with(["categorie", "galeries", "prix", "region", "prestataire", "responsable"]);

        if ($request->filled("libelle")) {
            $query->where("libelle", "like", "%" . $request->libelle . "%");
        }
        if ($request->filled("id_cat_evenmt")) {
            $query->where("id_cat_evenmt", $request->id_cat_evenmt);
        }
        if ($request->filled("date_debut")) {
            $query->whereDate("date_debut", ">=", $request->date_debut);
        }
        if ($request->filled("id_region")) {
            $query->where("id_region", $request->id_region);
        }

        if ($request->filled("prix_min") || $request->filled("prix_max")) {
            $query->whereHas("prix", function ($q) use ($request) {
                if ($request->filled("prix_min")) {
                    $q->where("montant", ">=", $request->prix_min);
                }
                if ($request->filled("prix_max")) {
                    $q->where("montant", "<=", $request->prix_max);
                }
            });
        }

        if ($request->filled("lat") && $request->filled("lng")) {
            $lat = (float) $request->lat;
            $lng = (float) $request->lng;
            $haversine =
                "6371 * acos(least(1, greatest(-1, cos(radians(?)) * cos(radians(latitude)) * cos(radians(longitude) - radians(?)) + sin(radians(?)) * sin(radians(latitude)))))";

            $query->selectRaw("evenement.*, ($haversine) as distance_km", [
                $lat,
                $lng,
                $lat,
            ]);

            if ($request->filled("radius")) {
                $query->whereRaw("$haversine <= ?", [
                    $lat,
                    $lng,
                    $lat,
                    (float) $request->radius,
                ]);
            }

            $query->orderByRaw($haversine, [$lat, $lng, $lat]);
        }

        return $query;
    }

    public function index(Request $request)
    {
        // Public : uniquement les événements validés, quoi que le client
        // demande - un en_attente/rejete/suspendu ne doit jamais apparaître ici.
        $query = $this->requeteFiltree($request)->where("status", "valide");

        return response()->json($query->paginate(12));
    }

    #[
        OA\Get(
            path: "/api/admin/evenements",
            tags: ["Evenements"],
            summary: "Liste des événements, tous statuts confondus (admin)",
            security: [["bearerAuth" => []]],
            responses: [
                new OA\Response(response: 200, description: "Liste paginée de tous les événements"),
            ],
        ),
    ]
    public function adminIndex(Request $request)
    {
        $query = $this->requeteFiltree($request);

        if ($request->filled("status")) {
            $query->where("status", $request->status);
        }

        return response()->json($query->paginate(50));
    }

    // id_admin est retiré du body - déduit du token admin connecté
    #[
        OA\Post(
            path: "/api/admin/evenements",
            tags: ["Evenements"],
            summary: "Créer un événement (admin)",
            security: [["bearerAuth" => []]],
            requestBody: new OA\RequestBody(
                content: new OA\JsonContent(
                    required: [
                        "libelle",
                        "adresse",
                        "longitude",
                        "latitude",
                        "date_debut",
                        "date_fin",
                        "id_cat_evenmt",
                    ],
                    properties: [
                        new OA\Property(property: "libelle", type: "string"),
                        new OA\Property(property: "adresse", type: "string"),
                        new OA\Property(property: "longitude", type: "number"),
                        new OA\Property(property: "latitude", type: "number"),
                        new OA\Property(
                            property: "description",
                            type: "string",
                        ),
                        new OA\Property(
                            property: "date_debut",
                            type: "string",
                            format: "date",
                        ),
                        new OA\Property(
                            property: "date_fin",
                            type: "string",
                            format: "date",
                        ),
                        new OA\Property(
                            property: "status",
                            type: "string",
                            enum: [
                                "en_attente",
                                "valide",
                                "rejete",
                                "suspendu",
                            ],
                        ),
                        new OA\Property(
                            property: "id_cat_evenmt",
                            type: "integer",
                        ),
                    ],
                ),
            ),
            responses: [
                new OA\Response(response: 201, description: "Événement créé"),
            ],
        ),
    ]
    public function store(Request $request)
    {
        $validated = $request->validate([
            "libelle" => "required|string|max:200",
            "adresse" => "required|string|max:255",
            "longitude" => "required|numeric",
            "latitude" => "required|numeric",
            "description" => "nullable|string",
            "date_debut" => "required|date",
            "date_fin" => "required|date|after_or_equal:date_debut",
            "status" => "nullable|string|in:en_attente,valide,rejete,suspendu",
            "id_cat_evenmt" => "required|exists:cat_evenmt,id",
            "id_region" => "nullable|exists:region,id",
        ]);

        // id_admin OU id_prestataire OU id_responsable selon le guard connecté -
        // jamais deux à la fois, jamais fourni par le client (déduit du token).
        $user = $request->user();
        if ($user instanceof Prestataire) {
            // Précondition du module Abonnement (étape 3) : un prestataire sans
            // abonnement actif ne peut créer aucune nouvelle fiche.
            if (!$user->abonnementActif()) {
                return response()->json([
                    "message" => "Votre abonnement n'est plus actif. Souscrivez ou renouvelez un plan pour créer une fiche.",
                ], 403);
            }

            $validated["id_prestataire"] = $user->id;
            // Un prestataire ne décide jamais lui-même que son événement est "valide" :
            // toujours en_attente à la création, quoi que le client envoie.
            $validated["status"] = "en_attente";
        } elseif ($user instanceof ResponsableRegional) {
            $validated["id_responsable"] = $user->id;
            // Même logique que pour Site::store : ne s'auto-valide jamais, région
            // forcée à la sienne s'il est scopé.
            $validated["status"] = "en_attente";
            if (!$user->estGlobal()) {
                $validated["id_region"] = $user->id_region;
            }
        } else {
            $validated["id_admin"] = $user->id;
        }

        $evenement = Evenement::create($validated);

        return response()->json($evenement->load(["categorie", "admin", "prestataire", "responsable", "region"]), 201);
    }

    #[
        OA\Get(
            path: "/evenements/{id}",
            tags: ["Evenements"],
            summary: "Afficher un événement",
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
                    description: "Détails de l'événement",
                ),
            ],
        ),
    ]
    public function show(Request $request, Evenement $evenement)
    {
        // Même règle que Site::show - jamais accessible publiquement tant que
        // non validé, sauf pour un admin (prévisualisation).
        if ($evenement->status !== "valide" && !$request->user("admin")) {
            abort(404);
        }

        return response()->json(
            $evenement->load([
                "categorie",
                "admin",
                "prestataire",
                "responsable",
                "region",
                "galeries",
                "prix",
                "sites",
            ]),
        );
    }

    #[
        OA\Put(
            path: "/api/admin/evenements/{id}",
            tags: ["Evenements"],
            summary: "Mettre à jour un événement (admin)",
            security: [["bearerAuth" => []]],
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
                        new OA\Property(property: "adresse", type: "string"),
                        new OA\Property(property: "longitude", type: "number"),
                        new OA\Property(property: "latitude", type: "number"),
                        new OA\Property(
                            property: "description",
                            type: "string",
                        ),
                        new OA\Property(
                            property: "date_debut",
                            type: "string",
                            format: "date",
                        ),
                        new OA\Property(
                            property: "date_fin",
                            type: "string",
                            format: "date",
                        ),
                        new OA\Property(
                            property: "status",
                            type: "string",
                            enum: [
                                "en_attente",
                                "valide",
                                "rejete",
                                "suspendu",
                            ],
                        ),
                        new OA\Property(
                            property: "id_cat_evenmt",
                            type: "integer",
                        ),
                    ],
                ),
            ),
            responses: [
                new OA\Response(
                    response: 200,
                    description: "Événement mis à jour",
                ),
            ],
        ),
    ]
    public function update(Request $request, Evenement $evenement)
    {
        $user = $request->user();
        $estPrestataire = $user instanceof Prestataire;
        $estResponsable = $user instanceof ResponsableRegional;

        if ($estPrestataire && $evenement->id_prestataire !== $user->id) {
            return response()->json(["message" => "Cet événement ne vous appartient pas."], 403);
        }
        if ($estResponsable && $evenement->id_responsable !== $user->id) {
            return response()->json(["message" => "Cet événement ne vous appartient pas."], 403);
        }

        $validated = $request->validate([
            "libelle" => "sometimes|string|max:200",
            "adresse" => "sometimes|string|max:255",
            "longitude" => "sometimes|numeric",
            "latitude" => "sometimes|numeric",
            "description" => "nullable|string",
            "date_debut" => "sometimes|date",
            "date_fin" => "sometimes|date|after_or_equal:date_debut",
            "status" => "nullable|string|in:en_attente,valide,rejete,suspendu",
            "id_cat_evenmt" => "sometimes|exists:cat_evenmt,id",
            "id_region" => "nullable|exists:region,id",
        ]);

        // Ni un prestataire ni un responsable ne peuvent se revalider après une
        // modif - seul valider()/rejeter() (réservé à l'admin pour une fiche de
        // responsable) change le statut.
        if ($estPrestataire || $estResponsable) {
            unset($validated["status"]);
        }
        if ($estResponsable && !$user->estGlobal()) {
            unset($validated["id_region"]);
        }

        $evenement->update($validated);

        return response()->json($evenement->load(["categorie", "admin", "prestataire", "responsable", "region"]));
    }

    /**
     * 403 si un ResponsableRegional tente de valider une fiche créée par un
     * responsable (seul un Admin le peut), ou une fiche hors de sa région
     * (sauf responsable global).
     */
    private function refuserSiHorsPerimetre(Request $request, Evenement $evenement)
    {
        $responsable = $request->user();
        if (!($responsable instanceof ResponsableRegional)) {
            return null;
        }
        if ($evenement->id_responsable !== null) {
            return response()->json(["message" => "Cette fiche a été créée par un responsable régional - seul un admin peut la valider."], 403);
        }
        if (!$responsable->estGlobal() && $evenement->id_region !== $responsable->id_region) {
            return response()->json(["message" => "Cet événement est hors de votre région."], 403);
        }
        return null;
    }

    #[
        OA\Patch(
            path: "/api/admin/evenements/{id}/valider",
            tags: ["Evenements"],
            summary: "Valider un événement (admin ou responsable régional de sa zone)",
            security: [["bearerAuth" => []]],
            parameters: [
                new OA\Parameter(
                    name: "id",
                    in: "path",
                    required: true,
                    schema: new OA\Schema(type: "integer"),
                ),
            ],
            responses: [
                new OA\Response(response: 200, description: "Événement validé"),
                new OA\Response(response: 403, description: "Événement hors de la région du responsable"),
            ],
        ),
    ]
    public function valider(Request $request, Evenement $evenement)
    {
        if ($refus = $this->refuserSiHorsPerimetre($request, $evenement)) return $refus;

        $evenement->update(["status" => "valide"]);

        return response()->json([
            "message" => "Événement validé",
            "evenement" => $evenement,
        ]);
    }

    #[
        OA\Patch(
            path: "/api/admin/evenements/{id}/rejeter",
            tags: ["Evenements"],
            summary: "Rejeter un événement (admin ou responsable régional de sa zone)",
            security: [["bearerAuth" => []]],
            parameters: [
                new OA\Parameter(
                    name: "id",
                    in: "path",
                    required: true,
                    schema: new OA\Schema(type: "integer"),
                ),
            ],
            responses: [
                new OA\Response(response: 200, description: "Événement rejeté"),
                new OA\Response(response: 403, description: "Événement hors de la région du responsable"),
            ],
        ),
    ]
    public function rejeter(Request $request, Evenement $evenement)
    {
        if ($refus = $this->refuserSiHorsPerimetre($request, $evenement)) return $refus;

        $evenement->update(["status" => "rejete"]);

        return response()->json([
            "message" => "Événement rejeté",
            "evenement" => $evenement,
        ]);
    }

    #[
        OA\Delete(
            path: "/api/admin/evenements/{id}",
            tags: ["Evenements"],
            summary: "Supprimer un événement (admin)",
            security: [["bearerAuth" => []]],
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
                    description: "Événement supprimé",
                ),
            ],
        ),
    ]
    public function destroy(Request $request, Evenement $evenement)
    {
        $user = $request->user();
        if ($user instanceof Prestataire && $evenement->id_prestataire !== $user->id) {
            return response()->json(["message" => "Cet événement ne vous appartient pas."], 403);
        }
        if ($user instanceof ResponsableRegional && $evenement->id_responsable !== $user->id) {
            return response()->json(["message" => "Cet événement ne vous appartient pas."], 403);
        }

        $evenement->delete();

        return response()->json(["message" => "Événement supprimé"], 200);
    }
}
