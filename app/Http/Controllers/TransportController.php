<?php

namespace App\Http\Controllers;

use App\Models\Prestataire;
use App\Models\ResponsableRegional;
use App\Models\Transport;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

class TransportController extends Controller
{
    #[
        OA\Get(
            path: "/api/prestataire/transports",
            tags: ["Prestataires"],
            summary: "Lister mes propres transports (prestataire connecté)",
            security: [["bearerAuth" => []]],
            responses: [new OA\Response(response: 200, description: "Liste paginée de mes transports")],
        ),
    ]
    public function mine(Request $request)
    {
        return response()->json(
            $request->user()->transports()->with(["galeries", "trajets.villeDepart", "trajets.villeArrivee", "region"])->latest()->paginate(15)
        );
    }

    private function requeteFiltree(Request $request)
    {
        $query = Transport::with(["galeries", "trajets.villeDepart", "trajets.villeArrivee", "region", "prestataire", "responsable"]);

        if ($request->filled("libelle")) {
            $query->where("libelle", "like", "%" . $request->libelle . "%");
        }
        if ($request->filled("type_transport")) {
            $query->where("type_transport", "like", "%" . $request->type_transport . "%");
        }
        if ($request->filled("id_region")) {
            $query->where("id_region", $request->id_region);
        }

        if ($request->filled("prix_min") || $request->filled("prix_max")) {
            $query->whereHas("trajets", function ($q) use ($request) {
                if ($request->filled("prix_min")) {
                    $q->where("prix", ">=", $request->prix_min);
                }
                if ($request->filled("prix_max")) {
                    $q->where("prix", "<=", $request->prix_max);
                }
            });
        }

        if ($request->filled("lat") && $request->filled("lng")) {
            $lat = (float) $request->lat;
            $lng = (float) $request->lng;
            $haversine =
                "6371 * acos(least(1, greatest(-1, cos(radians(?)) * cos(radians(latitude)) * cos(radians(longitude) - radians(?)) + sin(radians(?)) * sin(radians(latitude)))))";

            $query->selectRaw("transport.*, ($haversine) as distance_km", [$lat, $lng, $lat]);

            if ($request->filled("radius")) {
                $query->whereRaw("$haversine <= ?", [$lat, $lng, $lat, (float) $request->radius]);
            }

            $query->orderByRaw($haversine, [$lat, $lng, $lat]);
        }

        return $query;
    }

    #[
        OA\Get(
            path: "/api/transports",
            tags: ["Transports"],
            summary: "Liste des transports validés",
            parameters: [
                new OA\Parameter(name: "libelle", in: "query", schema: new OA\Schema(type: "string")),
                new OA\Parameter(name: "type_transport", in: "query", schema: new OA\Schema(type: "string")),
                new OA\Parameter(name: "lat", in: "query", schema: new OA\Schema(type: "number")),
                new OA\Parameter(name: "lng", in: "query", schema: new OA\Schema(type: "number")),
                new OA\Parameter(name: "radius", in: "query", schema: new OA\Schema(type: "number")),
                new OA\Parameter(name: "prix_min", in: "query", description: "Prix minimum (filtre sur le prix des trajets)", schema: new OA\Schema(type: "number")),
                new OA\Parameter(name: "prix_max", in: "query", description: "Prix maximum (filtre sur le prix des trajets)", schema: new OA\Schema(type: "number")),
            ],
            responses: [new OA\Response(response: 200, description: "Liste paginée des transports")],
        ),
    ]
    public function index(Request $request)
    {
        $query = $this->requeteFiltree($request)->where("status", "valide");

        return response()->json($query->paginate(12));
    }

    #[
        OA\Get(
            path: "/api/admin/transports",
            tags: ["Transports"],
            summary: "Liste des transports, tous statuts confondus (admin)",
            security: [["bearerAuth" => []]],
            responses: [new OA\Response(response: 200, description: "Liste paginée de tous les transports")],
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

    #[
        OA\Post(
            path: "/api/admin/transports",
            tags: ["Transports"],
            summary: "Créer un transport (admin)",
            security: [["bearerAuth" => []]],
            requestBody: new OA\RequestBody(
                required: true,
                content: new OA\JsonContent(
                    required: ["libelle", "adresse", "longitude", "latitude"],
                    properties: [
                        new OA\Property(property: "libelle", type: "string"),
                        new OA\Property(property: "adresse", type: "string"),
                        new OA\Property(property: "longitude", type: "number"),
                        new OA\Property(property: "latitude", type: "number"),
                        new OA\Property(property: "description", type: "string"),
                        new OA\Property(property: "type_transport", type: "string"),
                        new OA\Property(property: "capacite", type: "integer"),
                    ],
                ),
            ),
            responses: [new OA\Response(response: 201, description: "Transport créé")],
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
            "type_transport" => "nullable|string|max:100",
            "capacite" => "nullable|integer|min:1",
            "status" => "nullable|string|in:en_attente,valide,rejete,suspendu",
            "id_region" => "nullable|exists:region,id",
            "points_forts" => "nullable|array",
            "points_forts.*" => "string|max:200",
            "inclus" => "nullable|array",
            "inclus.*" => "string|max:200",
            "non_inclus" => "nullable|array",
            "non_inclus.*" => "string|max:200",
            "infos_pratiques" => "nullable|string",
            "recommandations" => "nullable|string",
            "duree_trajet_estimee" => "nullable|string|max:100",
        ]);

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
            $validated["status"] = "en_attente";
        } elseif ($user instanceof ResponsableRegional) {
            $validated["id_responsable"] = $user->id;
            $validated["status"] = "en_attente";
            if (!$user->estGlobal()) {
                $validated["id_region"] = $user->id_region;
            }
        } else {
            $validated["id_admin"] = $user->id;
        }

        $transport = Transport::create($validated);

        return response()->json($transport->load(["admin", "prestataire", "responsable", "region"]), 201);
    }

    #[
        OA\Get(
            path: "/api/transports/{id}",
            tags: ["Transports"],
            summary: "Afficher un transport",
            parameters: [new OA\Parameter(name: "id", in: "path", required: true, schema: new OA\Schema(type: "integer"))],
            responses: [new OA\Response(response: 200, description: "Détails du transport")],
        ),
    ]
    public function show(Request $request, Transport $transport)
    {
        if ($transport->status !== "valide" && !$request->user("admin")) {
            abort(404);
        }

        return response()->json(
            $transport->load(["admin", "prestataire", "responsable", "region", "galeries", "trajets.villeDepart", "trajets.villeArrivee"]),
        );
    }

    #[
        OA\Put(
            path: "/api/admin/transports/{id}",
            tags: ["Transports"],
            summary: "Mettre à jour un transport (admin)",
            security: [["bearerAuth" => []]],
            parameters: [new OA\Parameter(name: "id", in: "path", required: true, schema: new OA\Schema(type: "integer"))],
            responses: [new OA\Response(response: 200, description: "Transport mis à jour")],
        ),
    ]
    public function update(Request $request, Transport $transport)
    {
        $user = $request->user();
        $estPrestataire = $user instanceof Prestataire;
        $estResponsable = $user instanceof ResponsableRegional;

        if ($estPrestataire && $transport->id_prestataire !== $user->id) {
            return response()->json(["message" => "Ce transport ne vous appartient pas."], 403);
        }
        if ($estResponsable && $transport->id_responsable !== $user->id) {
            return response()->json(["message" => "Ce transport ne vous appartient pas."], 403);
        }

        $validated = $request->validate([
            "libelle" => "sometimes|string|max:200",
            "adresse" => "sometimes|string|max:255",
            "longitude" => "sometimes|numeric",
            "latitude" => "sometimes|numeric",
            "description" => "nullable|string",
            "type_transport" => "nullable|string|max:100",
            "capacite" => "nullable|integer|min:1",
            "status" => "nullable|string|in:en_attente,valide,rejete,suspendu",
            "id_region" => "nullable|exists:region,id",
            "points_forts" => "nullable|array",
            "points_forts.*" => "string|max:200",
            "inclus" => "nullable|array",
            "inclus.*" => "string|max:200",
            "non_inclus" => "nullable|array",
            "non_inclus.*" => "string|max:200",
            "infos_pratiques" => "nullable|string",
            "recommandations" => "nullable|string",
            "duree_trajet_estimee" => "nullable|string|max:100",
        ]);

        if ($estPrestataire || $estResponsable) {
            unset($validated["status"]);
        }
        // Le prestataire vient de corriger sa fiche suite à une demande de
        // précisions - elle repasse en attente pour revenir dans la file du
        // responsable, sans quoi elle resterait bloquée indéfiniment.
        if ($estPrestataire && $transport->status === "precisions_demandees") {
            $validated["status"] = "en_attente";
            $validated["commentaire_responsable"] = null;
        }
        if ($estResponsable && !$user->estGlobal()) {
            unset($validated["id_region"]);
        }

        $transport->update($validated);

        return response()->json($transport->load(["admin", "prestataire", "responsable", "region"]));
    }

    private function refuserSiHorsPerimetre(Request $request, Transport $transport)
    {
        $responsable = $request->user();
        if (!($responsable instanceof ResponsableRegional)) {
            return null;
        }
        if ($transport->id_responsable !== null) {
            return response()->json(["message" => "Cette fiche a été créée par un responsable régional - seul un admin peut la valider."], 403);
        }
        if (!$responsable->estGlobal() && $transport->id_region !== $responsable->id_region) {
            return response()->json(["message" => "Ce transport est hors de votre région."], 403);
        }
        return null;
    }

    #[
        OA\Patch(
            path: "/api/admin/transports/{id}/valider",
            tags: ["Transports"],
            summary: "Valider un transport (admin ou responsable régional de sa zone)",
            security: [["bearerAuth" => []]],
            parameters: [new OA\Parameter(name: "id", in: "path", required: true, schema: new OA\Schema(type: "integer"))],
            responses: [new OA\Response(response: 200, description: "Transport validé")],
        ),
    ]
    public function valider(Request $request, Transport $transport)
    {
        if ($refus = $this->refuserSiHorsPerimetre($request, $transport)) return $refus;

        $transport->update(["status" => "valide", "commentaire_responsable" => null]);

        return response()->json(["message" => "Transport validé", "transport" => $transport]);
    }

    #[
        OA\Patch(
            path: "/api/admin/transports/{id}/rejeter",
            tags: ["Transports"],
            summary: "Rejeter un transport (admin ou responsable régional de sa zone)",
            security: [["bearerAuth" => []]],
            parameters: [new OA\Parameter(name: "id", in: "path", required: true, schema: new OA\Schema(type: "integer"))],
            responses: [new OA\Response(response: 200, description: "Transport rejeté")],
        ),
    ]
    public function rejeter(Request $request, Transport $transport)
    {
        if ($refus = $this->refuserSiHorsPerimetre($request, $transport)) return $refus;

        $transport->update(["status" => "rejete"]);

        return response()->json(["message" => "Transport rejeté", "transport" => $transport]);
    }

    #[
        OA\Patch(
            path: "/api/admin/transports/{id}/demander-precisions",
            tags: ["Transports"],
            summary: "Demander un complément d'information au prestataire (admin ou responsable régional de sa zone)",
            security: [["bearerAuth" => []]],
            parameters: [new OA\Parameter(name: "id", in: "path", required: true, schema: new OA\Schema(type: "integer"))],
            requestBody: new OA\RequestBody(
                required: true,
                content: new OA\JsonContent(required: ["commentaire"], properties: [
                    new OA\Property(property: "commentaire", type: "string"),
                ]),
            ),
            responses: [new OA\Response(response: 200, description: "Précisions demandées")],
        ),
    ]
    public function demanderPrecisions(Request $request, Transport $transport)
    {
        if ($refus = $this->refuserSiHorsPerimetre($request, $transport)) return $refus;

        $validated = $request->validate([
            "commentaire" => "required|string|min:5|max:1000",
        ]);

        $transport->update(["status" => "precisions_demandees", "commentaire_responsable" => $validated["commentaire"]]);

        return response()->json(["message" => "Précisions demandées", "transport" => $transport]);
    }

    #[
        OA\Delete(
            path: "/api/admin/transports/{id}",
            tags: ["Transports"],
            summary: "Supprimer un transport (admin)",
            security: [["bearerAuth" => []]],
            parameters: [new OA\Parameter(name: "id", in: "path", required: true, schema: new OA\Schema(type: "integer"))],
            responses: [new OA\Response(response: 200, description: "Transport supprimé")],
        ),
    ]
    public function destroy(Request $request, Transport $transport)
    {
        $user = $request->user();
        if ($user instanceof Prestataire && $transport->id_prestataire !== $user->id) {
            return response()->json(["message" => "Ce transport ne vous appartient pas."], 403);
        }
        if ($user instanceof ResponsableRegional && $transport->id_responsable !== $user->id) {
            return response()->json(["message" => "Ce transport ne vous appartient pas."], 403);
        }

        $transport->delete();

        return response()->json(["message" => "Transport supprimé avec succès"], 200);
    }
}
