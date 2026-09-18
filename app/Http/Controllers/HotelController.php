<?php

namespace App\Http\Controllers;

use App\Models\Hotel;
use App\Models\Prestataire;
use App\Models\ResponsableRegional;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

class HotelController extends Controller
{
    #[
        OA\Get(
            path: "/api/prestataire/hotels",
            tags: ["Prestataires"],
            summary: "Lister mes propres hôtels (prestataire connecté)",
            security: [["bearerAuth" => []]],
            responses: [new OA\Response(response: 200, description: "Liste paginée de mes hôtels")],
        ),
    ]
    public function mine(Request $request)
    {
        return response()->json(
            $request->user()->hotels()->with(["galeries", "chambres", "region"])->latest()->paginate(15)
        );
    }

    /** Filtres communs (recherche, proximité) — pas le statut, géré différemment par index()/adminIndex(). */
    private function requeteFiltree(Request $request)
    {
        $query = Hotel::with(["galeries", "chambres", "region", "prestataire", "responsable"]);

        if ($request->filled("libelle")) {
            $query->where("libelle", "like", "%" . $request->libelle . "%");
        }

        if ($request->filled("lat") && $request->filled("lng")) {
            $lat = (float) $request->lat;
            $lng = (float) $request->lng;
            $haversine =
                "6371 * acos(least(1, greatest(-1, cos(radians(?)) * cos(radians(latitude)) * cos(radians(longitude) - radians(?)) + sin(radians(?)) * sin(radians(latitude)))))";

            $query->selectRaw("hotel.*, ($haversine) as distance_km", [$lat, $lng, $lat]);

            if ($request->filled("radius")) {
                $query->whereRaw("$haversine <= ?", [$lat, $lng, $lat, (float) $request->radius]);
            }

            $query->orderByRaw($haversine, [$lat, $lng, $lat]);
        }

        return $query;
    }

    #[
        OA\Get(
            path: "/api/hotels",
            tags: ["Hôtels"],
            summary: "Liste des hôtels validés",
            parameters: [
                new OA\Parameter(name: "libelle", in: "query", schema: new OA\Schema(type: "string")),
                new OA\Parameter(name: "lat", in: "query", schema: new OA\Schema(type: "number")),
                new OA\Parameter(name: "lng", in: "query", schema: new OA\Schema(type: "number")),
                new OA\Parameter(name: "radius", in: "query", schema: new OA\Schema(type: "number")),
            ],
            responses: [new OA\Response(response: 200, description: "Liste paginée des hôtels")],
        ),
    ]
    public function index(Request $request)
    {
        $query = $this->requeteFiltree($request)->where("status", "valide");

        return response()->json($query->paginate(12));
    }

    #[
        OA\Get(
            path: "/api/admin/hotels",
            tags: ["Hôtels"],
            summary: "Liste des hôtels, tous statuts confondus (admin)",
            security: [["bearerAuth" => []]],
            responses: [new OA\Response(response: 200, description: "Liste paginée de tous les hôtels")],
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
            path: "/api/admin/hotels",
            tags: ["Hôtels"],
            summary: "Créer un hôtel (admin)",
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
                        new OA\Property(property: "nombre_etoiles", type: "integer"),
                    ],
                ),
            ),
            responses: [new OA\Response(response: 201, description: "Hôtel créé")],
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
            "nombre_etoiles" => "nullable|integer|min:1|max:5",
            "status" => "nullable|string|in:en_attente,valide,rejete,suspendu",
            "id_region" => "nullable|exists:region,id",
        ]);

        $user = $request->user();
        if ($user instanceof Prestataire) {
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

        $hotel = Hotel::create($validated);

        return response()->json($hotel->load(["admin", "prestataire", "responsable", "region"]), 201);
    }

    #[
        OA\Get(
            path: "/api/hotels/{id}",
            tags: ["Hôtels"],
            summary: "Afficher un hôtel",
            parameters: [new OA\Parameter(name: "id", in: "path", required: true, schema: new OA\Schema(type: "integer"))],
            responses: [new OA\Response(response: 200, description: "Détails de l'hôtel")],
        ),
    ]
    public function show(Request $request, Hotel $hotel)
    {
        if ($hotel->status !== "valide" && !$request->user("admin")) {
            abort(404);
        }

        return response()->json(
            $hotel->load(["admin", "prestataire", "responsable", "region", "galeries", "chambres"]),
        );
    }

    #[
        OA\Put(
            path: "/api/admin/hotels/{id}",
            tags: ["Hôtels"],
            summary: "Mettre à jour un hôtel (admin)",
            security: [["bearerAuth" => []]],
            parameters: [new OA\Parameter(name: "id", in: "path", required: true, schema: new OA\Schema(type: "integer"))],
            responses: [new OA\Response(response: 200, description: "Hôtel mis à jour")],
        ),
    ]
    public function update(Request $request, Hotel $hotel)
    {
        $user = $request->user();
        $estPrestataire = $user instanceof Prestataire;
        $estResponsable = $user instanceof ResponsableRegional;

        if ($estPrestataire && $hotel->id_prestataire !== $user->id) {
            return response()->json(["message" => "Cet hôtel ne vous appartient pas."], 403);
        }
        if ($estResponsable && $hotel->id_responsable !== $user->id) {
            return response()->json(["message" => "Cet hôtel ne vous appartient pas."], 403);
        }

        $validated = $request->validate([
            "libelle" => "sometimes|string|max:200",
            "adresse" => "sometimes|string|max:255",
            "longitude" => "sometimes|numeric",
            "latitude" => "sometimes|numeric",
            "description" => "nullable|string",
            "nombre_etoiles" => "nullable|integer|min:1|max:5",
            "status" => "nullable|string|in:en_attente,valide,rejete,suspendu",
            "id_region" => "nullable|exists:region,id",
        ]);

        if ($estPrestataire || $estResponsable) {
            unset($validated["status"]);
        }
        if ($estResponsable && !$user->estGlobal()) {
            unset($validated["id_region"]);
        }

        $hotel->update($validated);

        return response()->json($hotel->load(["admin", "prestataire", "responsable", "region"]));
    }

    private function refuserSiHorsPerimetre(Request $request, Hotel $hotel)
    {
        $responsable = $request->user();
        if (!($responsable instanceof ResponsableRegional)) {
            return null;
        }
        if ($hotel->id_responsable !== null) {
            return response()->json(["message" => "Cette fiche a été créée par un responsable régional — seul un admin peut la valider."], 403);
        }
        if (!$responsable->estGlobal() && $hotel->id_region !== $responsable->id_region) {
            return response()->json(["message" => "Cet hôtel est hors de votre région."], 403);
        }
        return null;
    }

    #[
        OA\Patch(
            path: "/api/admin/hotels/{id}/valider",
            tags: ["Hôtels"],
            summary: "Valider un hôtel (admin ou responsable régional de sa zone)",
            security: [["bearerAuth" => []]],
            parameters: [new OA\Parameter(name: "id", in: "path", required: true, schema: new OA\Schema(type: "integer"))],
            responses: [new OA\Response(response: 200, description: "Hôtel validé")],
        ),
    ]
    public function valider(Request $request, Hotel $hotel)
    {
        if ($refus = $this->refuserSiHorsPerimetre($request, $hotel)) return $refus;

        $hotel->update(["status" => "valide"]);

        return response()->json(["message" => "Hôtel validé", "hotel" => $hotel]);
    }

    #[
        OA\Patch(
            path: "/api/admin/hotels/{id}/rejeter",
            tags: ["Hôtels"],
            summary: "Rejeter un hôtel (admin ou responsable régional de sa zone)",
            security: [["bearerAuth" => []]],
            parameters: [new OA\Parameter(name: "id", in: "path", required: true, schema: new OA\Schema(type: "integer"))],
            responses: [new OA\Response(response: 200, description: "Hôtel rejeté")],
        ),
    ]
    public function rejeter(Request $request, Hotel $hotel)
    {
        if ($refus = $this->refuserSiHorsPerimetre($request, $hotel)) return $refus;

        $hotel->update(["status" => "rejete"]);

        return response()->json(["message" => "Hôtel rejeté", "hotel" => $hotel]);
    }

    #[
        OA\Delete(
            path: "/api/admin/hotels/{id}",
            tags: ["Hôtels"],
            summary: "Supprimer un hôtel (admin)",
            security: [["bearerAuth" => []]],
            parameters: [new OA\Parameter(name: "id", in: "path", required: true, schema: new OA\Schema(type: "integer"))],
            responses: [new OA\Response(response: 200, description: "Hôtel supprimé")],
        ),
    ]
    public function destroy(Request $request, Hotel $hotel)
    {
        $user = $request->user();
        if ($user instanceof Prestataire && $hotel->id_prestataire !== $user->id) {
            return response()->json(["message" => "Cet hôtel ne vous appartient pas."], 403);
        }
        if ($user instanceof ResponsableRegional && $hotel->id_responsable !== $user->id) {
            return response()->json(["message" => "Cet hôtel ne vous appartient pas."], 403);
        }

        $hotel->delete();

        return response()->json(["message" => "Hôtel supprimé avec succès"], 200);
    }
}
