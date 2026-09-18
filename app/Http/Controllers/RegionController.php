<?php

namespace App\Http\Controllers;

use App\Models\Region;
use OpenApi\Attributes as OA;

/**
 * Les 12 départements du Bénin - liste fixe, seedée, aucune mutation exposée
 * (pas de cas d'usage identifié pour en ajouter/modifier depuis l'app).
 */
class RegionController extends Controller
{
    #[
        OA\Get(
            path: "/api/regions",
            tags: ["Régions"],
            summary: "Lister les régions (départements du Bénin)",
            responses: [
                new OA\Response(response: 200, description: "Liste des régions"),
            ],
        ),
    ]
    public function index()
    {
        return response()->json(Region::orderBy('nom')->get());
    }
}
