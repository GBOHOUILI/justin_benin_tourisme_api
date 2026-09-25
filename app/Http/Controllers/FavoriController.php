<?php

namespace App\Http\Controllers;

use App\Models\Evenement;
use App\Models\Favori;
use App\Models\Hotel;
use App\Models\Restaurant;
use App\Models\Site;
use App\Models\Transport;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

class FavoriController extends Controller
{
    private const TYPE_COLUMN = [
        'site' => 'id_site',
        'evenement' => 'id_evnmt',
        'hotel' => 'id_hotel',
        'restaurant' => 'id_restaurant',
        'transport' => 'id_transport',
    ];

    private const TYPE_MODEL = [
        'site' => Site::class,
        'evenement' => Evenement::class,
        'hotel' => Hotel::class,
        'restaurant' => Restaurant::class,
        'transport' => Transport::class,
    ];

    #[
        OA\Get(
            path: "/api/favoris",
            tags: ["Favoris"],
            summary: "Lister les favoris du touriste connecté",
            security: [["bearerAuth" => []]],
            responses: [new OA\Response(response: 200, description: "Liste des favoris, chacun avec sa fiche chargée ({id, type, item})")],
        ),
    ]
    public function index(Request $request)
    {
        $favoris = Favori::where('id_user', $request->user()->id)
            ->with(['site.galeries', 'evenement.galeries', 'hotel.galeries', 'restaurant.galeries', 'transport.galeries'])
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(fn (Favori $f) => [
                'id' => $f->id,
                'type' => $f->type,
                'item' => $f->{$f->type},
            ]);

        return response()->json($favoris);
    }

    #[
        OA\Post(
            path: "/api/favoris",
            tags: ["Favoris"],
            summary: "Ajouter une fiche aux favoris (idempotent)",
            security: [["bearerAuth" => []]],
            requestBody: new OA\RequestBody(
                required: true,
                content: new OA\JsonContent(
                    required: ["type", "id"],
                    properties: [
                        new OA\Property(property: "type", type: "string", enum: ["site", "evenement", "hotel", "restaurant", "transport"]),
                        new OA\Property(property: "id", type: "integer", description: "id de la fiche à favoriter"),
                    ],
                ),
            ),
            responses: [
                new OA\Response(response: 201, description: "Favori créé (ou déjà existant, réponse identique)"),
                new OA\Response(response: 404, description: "Fiche introuvable"),
            ],
        ),
    ]
    public function store(Request $request)
    {
        $validated = $request->validate([
            'type' => 'required|string|in:site,evenement,hotel,restaurant,transport',
            'id' => 'required|integer',
        ]);

        $column = self::TYPE_COLUMN[$validated['type']];
        $model = self::TYPE_MODEL[$validated['type']];

        if (! $model::where('id', $validated['id'])->exists()) {
            return response()->json(['message' => 'Fiche introuvable.'], 404);
        }

        // firstOrCreate rend l'appel idempotent - un double-clic ou un retry
        // réseau ne crée jamais de doublon (en plus de l'index unique en base).
        $favori = Favori::firstOrCreate([
            'id_user' => $request->user()->id,
            $column => $validated['id'],
        ]);

        return response()->json(['id' => $favori->id, 'type' => $favori->type], 201);
    }

    #[
        OA\Delete(
            path: "/api/favoris/{id}",
            tags: ["Favoris"],
            summary: "Retirer un favori",
            security: [["bearerAuth" => []]],
            parameters: [new OA\Parameter(name: "id", in: "path", required: true, schema: new OA\Schema(type: "integer"))],
            responses: [
                new OA\Response(response: 200, description: "Favori supprimé"),
                new OA\Response(response: 403, description: "Ce favori ne vous appartient pas"),
            ],
        ),
    ]
    public function destroy(Request $request, Favori $favori)
    {
        if ($favori->id_user !== $request->user()->id) {
            return response()->json(['message' => 'Ce favori ne vous appartient pas.'], 403);
        }

        $favori->delete();

        return response()->json(['message' => 'Favori supprimé'], 200);
    }
}
