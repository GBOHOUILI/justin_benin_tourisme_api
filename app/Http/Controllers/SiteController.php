<?php

namespace App\Http\Controllers;

use App\Models\Prestataire;
use App\Models\ResponsableRegional;
use App\Models\Site;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

class SiteController extends Controller
{
    #[
        OA\Get(
            path: "/api/prestataire/sites",
            tags: ["Prestataires"],
            summary: "Lister mes propres sites (prestataire connecté)",
            security: [["bearerAuth" => []]],
            responses: [
                new OA\Response(response: 200, description: "Liste paginée de mes sites"),
            ],
        ),
    ]
    public function mine(Request $request)
    {
        return response()->json(
            $request->user()->sites()->with(["categorie", "galeries", "prix", "region"])->latest()->paginate(15)
        );
    }

    #[
        OA\Get(
            path: "/api/sites",
            tags: ["Sites"],
            summary: "Liste des sites",
            parameters: [
                new OA\Parameter(
                    name: "libelle",
                    in: "query",
                    schema: new OA\Schema(type: "string"),
                ),
                new OA\Parameter(
                    name: "id_cat_site",
                    in: "query",
                    schema: new OA\Schema(type: "integer"),
                ),
                new OA\Parameter(
                    name: "status",
                    in: "query",
                    schema: new OA\Schema(type: "string", enum: ["en_attente", "valide", "rejete", "suspendu"]),
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
                    description: "Prix minimum (filtre sur les tarifs du site)",
                    schema: new OA\Schema(type: "number"),
                ),
                new OA\Parameter(
                    name: "prix_max",
                    in: "query",
                    description: "Prix maximum (filtre sur les tarifs du site)",
                    schema: new OA\Schema(type: "number"),
                ),
            ],
            responses: [
                new OA\Response(
                    response: 200,
                    description: "Liste paginée des sites, triée par distance si lat/lng fournis",
                ),
            ],
        ),
    ]
    /** Filtres communs (recherche, catégorie, prix, proximité) - pas le statut, géré différemment par index()/adminIndex(). */
    private function requeteFiltree(Request $request)
    {
        $query = Site::with(["categorie", "galeries", "prix", "region", "prestataire", "responsable"]);

        if ($request->filled("libelle")) {
            $query->where("libelle", "like", "%" . $request->libelle . "%");
        }
        if ($request->filled("id_cat_site")) {
            $query->where("id_cat_site", $request->id_cat_site);
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

            $query->selectRaw("site.*, ($haversine) as distance_km", [
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
        // Public : uniquement les sites validés, quoi que le client demande -
        // un en_attente/rejete/suspendu ne doit jamais apparaître ici.
        $query = $this->requeteFiltree($request)->where("status", "valide");

        return response()->json($query->paginate(12));
    }

    #[
        OA\Get(
            path: "/api/admin/sites",
            tags: ["Sites"],
            summary: "Liste des sites, tous statuts confondus (admin)",
            security: [["bearerAuth" => []]],
            responses: [
                new OA\Response(response: 200, description: "Liste paginée de tous les sites"),
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
            path: "/api/admin/sites",
            tags: ["Sites"],
            summary: "Créer un site (admin)",
            security: [["bearerAuth" => []]],
            requestBody: new OA\RequestBody(
                required: true,
                content: new OA\JsonContent(
                    required: [
                        "libelle",
                        "adresse",
                        "longitude",
                        "latitude",
                        "id_cat_site",
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
                            property: "ouverture",
                            type: "string",
                            example: "08:00",
                        ),
                        new OA\Property(
                            property: "fermeture",
                            type: "string",
                            example: "18:00",
                        ),
                        new OA\Property(property: "status", type: "boolean"),
                        new OA\Property(
                            property: "id_cat_site",
                            type: "integer",
                        ),
                    ],
                ),
            ),
            responses: [
                new OA\Response(response: 201, description: "Site créé"),
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
            "ouverture" => "nullable|date_format:H:i",
            "fermeture" => "nullable|date_format:H:i",
            "status" => "nullable|string|in:en_attente,valide,rejete,suspendu",
            "id_cat_site" => "required|exists:cat_site,id",
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
            // Un prestataire ne décide jamais lui-même que son site est validé :
            // en_attente à la création, quoi que le client envoie (même logique
            // que pour Evenement::store).
            $validated["status"] = "en_attente";
        } elseif ($user instanceof ResponsableRegional) {
            $validated["id_responsable"] = $user->id;
            // Un responsable connaît sa région mais ne peut pas non plus s'auto-
            // valider - seul un Admin valide une fiche créée par un responsable.
            $validated["status"] = "en_attente";
            // Région forcée à la sienne s'il est scopé (jamais celle envoyée par
            // le client) ; un responsable global doit en choisir une explicitement.
            if (!$user->estGlobal()) {
                $validated["id_region"] = $user->id_region;
            }
        } else {
            $validated["id_admin"] = $user->id;
        }

        $site = Site::create($validated);

        return response()->json($site->load(["categorie", "admin", "prestataire", "responsable", "region"]), 201);
    }

    #[
        OA\Get(
            path: "/api/sites/{id}",
            tags: ["Sites"],
            summary: "Afficher un site",
            parameters: [
                new OA\Parameter(
                    name: "id",
                    in: "path",
                    required: true,
                    schema: new OA\Schema(type: "integer"),
                ),
            ],
            responses: [
                new OA\Response(response: 200, description: "Détails du site"),
            ],
        ),
    ]
    public function show(Request $request, Site $site)
    {
        // Une fiche non validée n'est jamais accessible publiquement, même en
        // devinant/partageant son id - seul un admin peut la prévisualiser
        // (utile pour vérifier avant validation depuis un lien direct).
        if ($site->status !== "valide" && !$request->user("admin")) {
            abort(404);
        }

        return response()->json(
            $site->load([
                "categorie",
                "admin",
                "prestataire",
                "responsable",
                "region",
                "galeries",
                "prix",
                "evenements",
            ]),
        );
    }

    #[
        OA\Put(
            path: "/api/admin/sites/{id}",
            tags: ["Sites"],
            summary: "Mettre à jour un site (admin)",
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
                            property: "ouverture",
                            type: "string",
                            example: "08:00",
                        ),
                        new OA\Property(
                            property: "fermeture",
                            type: "string",
                            example: "18:00",
                        ),
                        new OA\Property(property: "status", type: "boolean"),
                        new OA\Property(
                            property: "id_cat_site",
                            type: "integer",
                        ),
                    ],
                ),
            ),
            responses: [
                new OA\Response(response: 200, description: "Site mis à jour"),
            ],
        ),
    ]
    public function update(Request $request, Site $site)
    {
        $user = $request->user();
        $estPrestataire = $user instanceof Prestataire;
        $estResponsable = $user instanceof ResponsableRegional;

        if ($estPrestataire && $site->id_prestataire !== $user->id) {
            return response()->json(["message" => "Ce site ne vous appartient pas."], 403);
        }
        if ($estResponsable && $site->id_responsable !== $user->id) {
            return response()->json(["message" => "Ce site ne vous appartient pas."], 403);
        }

        $validated = $request->validate([
            "libelle" => "sometimes|string|max:200",
            "adresse" => "sometimes|string|max:255",
            "longitude" => "sometimes|numeric",
            "latitude" => "sometimes|numeric",
            "description" => "nullable|string",
            "ouverture" => "nullable|date_format:H:i",
            "fermeture" => "nullable|date_format:H:i",
            "status" => "nullable|string|in:en_attente,valide,rejete,suspendu",
            "id_cat_site" => "sometimes|exists:cat_site,id",
            "id_region" => "nullable|exists:region,id",
        ]);

        // Même règle qu'à la création : ni un prestataire ni un responsable ne
        // s'auto-valident (cf. EvenementController::update) - seul valider()/
        // rejeter() (réservés à l'admin pour une fiche de responsable) change le statut.
        if ($estPrestataire || $estResponsable) {
            unset($validated["status"]);
        }
        // Un responsable régional scopé ne déplace pas sa fiche hors de sa région.
        if ($estResponsable && !$user->estGlobal()) {
            unset($validated["id_region"]);
        }

        $site->update($validated);

        return response()->json($site->load(["categorie", "admin", "prestataire", "responsable", "region"]));
    }

    /**
     * 403 si un ResponsableRegional tente de valider une fiche créée par un
     * responsable (lui ou un autre - seul un Admin valide ces fiches-là, un
     * responsable connaît sa région mais ne s'auto-valide/ne valide jamais un
     * pair), ou une fiche hors de sa région (sauf responsable global).
     */
    private function refuserSiHorsPerimetre(Request $request, Site $site)
    {
        $responsable = $request->user();
        if (!($responsable instanceof ResponsableRegional)) {
            return null;
        }
        if ($site->id_responsable !== null) {
            return response()->json(["message" => "Cette fiche a été créée par un responsable régional - seul un admin peut la valider."], 403);
        }
        if (!$responsable->estGlobal() && $site->id_region !== $responsable->id_region) {
            return response()->json(["message" => "Ce site est hors de votre région."], 403);
        }
        return null;
    }

    #[
        OA\Patch(
            path: "/api/admin/sites/{id}/valider",
            tags: ["Sites"],
            summary: "Valider un site (admin ou responsable régional de sa zone)",
            security: [["bearerAuth" => []]],
            parameters: [
                new OA\Parameter(name: "id", in: "path", required: true, schema: new OA\Schema(type: "integer")),
            ],
            responses: [
                new OA\Response(response: 200, description: "Site validé"),
                new OA\Response(response: 403, description: "Site hors de la région du responsable"),
            ],
        ),
    ]
    public function valider(Request $request, Site $site)
    {
        if ($refus = $this->refuserSiHorsPerimetre($request, $site)) return $refus;

        $site->update(["status" => "valide"]);

        return response()->json(["message" => "Site validé", "site" => $site]);
    }

    #[
        OA\Patch(
            path: "/api/admin/sites/{id}/rejeter",
            tags: ["Sites"],
            summary: "Rejeter un site (admin ou responsable régional de sa zone)",
            security: [["bearerAuth" => []]],
            parameters: [
                new OA\Parameter(name: "id", in: "path", required: true, schema: new OA\Schema(type: "integer")),
            ],
            responses: [
                new OA\Response(response: 200, description: "Site rejeté"),
                new OA\Response(response: 403, description: "Site hors de la région du responsable"),
            ],
        ),
    ]
    public function rejeter(Request $request, Site $site)
    {
        if ($refus = $this->refuserSiHorsPerimetre($request, $site)) return $refus;

        $site->update(["status" => "rejete"]);

        return response()->json(["message" => "Site rejeté", "site" => $site]);
    }

    #[
        OA\Delete(
            path: "/api/admin/sites/{id}",
            tags: ["Sites"],
            summary: "Supprimer un site (admin)",
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
                new OA\Response(response: 200, description: "Site supprimé"),
            ],
        ),
    ]
    public function destroy(Request $request, Site $site)
    {
        $user = $request->user();
        if ($user instanceof Prestataire && $site->id_prestataire !== $user->id) {
            return response()->json(["message" => "Ce site ne vous appartient pas."], 403);
        }
        if ($user instanceof ResponsableRegional && $site->id_responsable !== $user->id) {
            return response()->json(["message" => "Ce site ne vous appartient pas."], 403);
        }

        $site->delete();

        return response()->json(
            ["message" => "Site supprimé avec succès"],
            200,
        );
    }
}
