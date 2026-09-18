<?php

namespace App\Http\Controllers;

use App\Models\Prestataire;
use App\Models\Restaurant;
use App\Models\ResponsableRegional;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

class RestaurantController extends Controller
{
    #[
        OA\Get(
            path: "/api/prestataire/restaurants",
            tags: ["Prestataires"],
            summary: "Lister mes propres restaurants (prestataire connecté)",
            security: [["bearerAuth" => []]],
            responses: [new OA\Response(response: 200, description: "Liste paginée de mes restaurants")],
        ),
    ]
    public function mine(Request $request)
    {
        return response()->json(
            $request->user()->restaurants()->with(["galeries", "plats", "region"])->latest()->paginate(15)
        );
    }

    private function requeteFiltree(Request $request)
    {
        $query = Restaurant::with(["galeries", "plats", "region", "prestataire", "responsable"]);

        if ($request->filled("libelle")) {
            $query->where("libelle", "like", "%" . $request->libelle . "%");
        }
        if ($request->filled("type_cuisine")) {
            $query->where("type_cuisine", "like", "%" . $request->type_cuisine . "%");
        }

        if ($request->filled("lat") && $request->filled("lng")) {
            $lat = (float) $request->lat;
            $lng = (float) $request->lng;
            $haversine =
                "6371 * acos(least(1, greatest(-1, cos(radians(?)) * cos(radians(latitude)) * cos(radians(longitude) - radians(?)) + sin(radians(?)) * sin(radians(latitude)))))";

            $query->selectRaw("restaurant.*, ($haversine) as distance_km", [$lat, $lng, $lat]);

            if ($request->filled("radius")) {
                $query->whereRaw("$haversine <= ?", [$lat, $lng, $lat, (float) $request->radius]);
            }

            $query->orderByRaw($haversine, [$lat, $lng, $lat]);
        }

        return $query;
    }

    #[
        OA\Get(
            path: "/api/restaurants",
            tags: ["Restaurants"],
            summary: "Liste des restaurants validés",
            parameters: [
                new OA\Parameter(name: "libelle", in: "query", schema: new OA\Schema(type: "string")),
                new OA\Parameter(name: "type_cuisine", in: "query", schema: new OA\Schema(type: "string")),
                new OA\Parameter(name: "lat", in: "query", schema: new OA\Schema(type: "number")),
                new OA\Parameter(name: "lng", in: "query", schema: new OA\Schema(type: "number")),
                new OA\Parameter(name: "radius", in: "query", schema: new OA\Schema(type: "number")),
            ],
            responses: [new OA\Response(response: 200, description: "Liste paginée des restaurants")],
        ),
    ]
    public function index(Request $request)
    {
        $query = $this->requeteFiltree($request)->where("status", "valide");

        return response()->json($query->paginate(12));
    }

    #[
        OA\Get(
            path: "/api/admin/restaurants",
            tags: ["Restaurants"],
            summary: "Liste des restaurants, tous statuts confondus (admin)",
            security: [["bearerAuth" => []]],
            responses: [new OA\Response(response: 200, description: "Liste paginée de tous les restaurants")],
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
            path: "/api/admin/restaurants",
            tags: ["Restaurants"],
            summary: "Créer un restaurant (admin)",
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
                        new OA\Property(property: "type_cuisine", type: "string"),
                        new OA\Property(property: "gamme_prix", type: "string", enum: ["economique", "moyen", "eleve"]),
                    ],
                ),
            ),
            responses: [new OA\Response(response: 201, description: "Restaurant créé")],
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
            "type_cuisine" => "nullable|string|max:100",
            "gamme_prix" => "nullable|string|in:economique,moyen,eleve",
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

        $restaurant = Restaurant::create($validated);

        return response()->json($restaurant->load(["admin", "prestataire", "responsable", "region"]), 201);
    }

    #[
        OA\Get(
            path: "/api/restaurants/{id}",
            tags: ["Restaurants"],
            summary: "Afficher un restaurant",
            parameters: [new OA\Parameter(name: "id", in: "path", required: true, schema: new OA\Schema(type: "integer"))],
            responses: [new OA\Response(response: 200, description: "Détails du restaurant")],
        ),
    ]
    public function show(Request $request, Restaurant $restaurant)
    {
        if ($restaurant->status !== "valide" && !$request->user("admin")) {
            abort(404);
        }

        return response()->json(
            $restaurant->load(["admin", "prestataire", "responsable", "region", "galeries", "plats"]),
        );
    }

    #[
        OA\Put(
            path: "/api/admin/restaurants/{id}",
            tags: ["Restaurants"],
            summary: "Mettre à jour un restaurant (admin)",
            security: [["bearerAuth" => []]],
            parameters: [new OA\Parameter(name: "id", in: "path", required: true, schema: new OA\Schema(type: "integer"))],
            responses: [new OA\Response(response: 200, description: "Restaurant mis à jour")],
        ),
    ]
    public function update(Request $request, Restaurant $restaurant)
    {
        $user = $request->user();
        $estPrestataire = $user instanceof Prestataire;
        $estResponsable = $user instanceof ResponsableRegional;

        if ($estPrestataire && $restaurant->id_prestataire !== $user->id) {
            return response()->json(["message" => "Ce restaurant ne vous appartient pas."], 403);
        }
        if ($estResponsable && $restaurant->id_responsable !== $user->id) {
            return response()->json(["message" => "Ce restaurant ne vous appartient pas."], 403);
        }

        $validated = $request->validate([
            "libelle" => "sometimes|string|max:200",
            "adresse" => "sometimes|string|max:255",
            "longitude" => "sometimes|numeric",
            "latitude" => "sometimes|numeric",
            "description" => "nullable|string",
            "type_cuisine" => "nullable|string|max:100",
            "gamme_prix" => "nullable|string|in:economique,moyen,eleve",
            "status" => "nullable|string|in:en_attente,valide,rejete,suspendu",
            "id_region" => "nullable|exists:region,id",
        ]);

        if ($estPrestataire || $estResponsable) {
            unset($validated["status"]);
        }
        if ($estResponsable && !$user->estGlobal()) {
            unset($validated["id_region"]);
        }

        $restaurant->update($validated);

        return response()->json($restaurant->load(["admin", "prestataire", "responsable", "region"]));
    }

    private function refuserSiHorsPerimetre(Request $request, Restaurant $restaurant)
    {
        $responsable = $request->user();
        if (!($responsable instanceof ResponsableRegional)) {
            return null;
        }
        if ($restaurant->id_responsable !== null) {
            return response()->json(["message" => "Cette fiche a été créée par un responsable régional — seul un admin peut la valider."], 403);
        }
        if (!$responsable->estGlobal() && $restaurant->id_region !== $responsable->id_region) {
            return response()->json(["message" => "Ce restaurant est hors de votre région."], 403);
        }
        return null;
    }

    #[
        OA\Patch(
            path: "/api/admin/restaurants/{id}/valider",
            tags: ["Restaurants"],
            summary: "Valider un restaurant (admin ou responsable régional de sa zone)",
            security: [["bearerAuth" => []]],
            parameters: [new OA\Parameter(name: "id", in: "path", required: true, schema: new OA\Schema(type: "integer"))],
            responses: [new OA\Response(response: 200, description: "Restaurant validé")],
        ),
    ]
    public function valider(Request $request, Restaurant $restaurant)
    {
        if ($refus = $this->refuserSiHorsPerimetre($request, $restaurant)) return $refus;

        $restaurant->update(["status" => "valide"]);

        return response()->json(["message" => "Restaurant validé", "restaurant" => $restaurant]);
    }

    #[
        OA\Patch(
            path: "/api/admin/restaurants/{id}/rejeter",
            tags: ["Restaurants"],
            summary: "Rejeter un restaurant (admin ou responsable régional de sa zone)",
            security: [["bearerAuth" => []]],
            parameters: [new OA\Parameter(name: "id", in: "path", required: true, schema: new OA\Schema(type: "integer"))],
            responses: [new OA\Response(response: 200, description: "Restaurant rejeté")],
        ),
    ]
    public function rejeter(Request $request, Restaurant $restaurant)
    {
        if ($refus = $this->refuserSiHorsPerimetre($request, $restaurant)) return $refus;

        $restaurant->update(["status" => "rejete"]);

        return response()->json(["message" => "Restaurant rejeté", "restaurant" => $restaurant]);
    }

    #[
        OA\Delete(
            path: "/api/admin/restaurants/{id}",
            tags: ["Restaurants"],
            summary: "Supprimer un restaurant (admin)",
            security: [["bearerAuth" => []]],
            parameters: [new OA\Parameter(name: "id", in: "path", required: true, schema: new OA\Schema(type: "integer"))],
            responses: [new OA\Response(response: 200, description: "Restaurant supprimé")],
        ),
    ]
    public function destroy(Request $request, Restaurant $restaurant)
    {
        $user = $request->user();
        if ($user instanceof Prestataire && $restaurant->id_prestataire !== $user->id) {
            return response()->json(["message" => "Ce restaurant ne vous appartient pas."], 403);
        }
        if ($user instanceof ResponsableRegional && $restaurant->id_responsable !== $user->id) {
            return response()->json(["message" => "Ce restaurant ne vous appartient pas."], 403);
        }

        $restaurant->delete();

        return response()->json(["message" => "Restaurant supprimé avec succès"], 200);
    }
}
