<?php

namespace App\Http\Controllers;

use App\Models\Reservation;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

class PrestataireController extends Controller
{
    #[
        OA\Put(
            path: "/api/prestataire/profil",
            tags: ["Prestataires"],
            summary: "Mettre à jour son propre profil prestataire",
            security: [["bearerAuth" => []]],
            requestBody: new OA\RequestBody(
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "nom_entreprise", type: "string"),
                        new OA\Property(property: "type_prestataire", type: "string", enum: ["site", "evenement", "hotel", "restaurant", "transport"]),
                        new OA\Property(property: "email", type: "string", format: "email"),
                        new OA\Property(property: "tel", type: "string"),
                    ],
                ),
            ),
            responses: [
                new OA\Response(response: 200, description: "Profil mis à jour"),
            ],
        ),
    ]
    public function updateProfil(Request $request)
    {
        $prestataire = $request->user();

        $validated = $request->validate([
            'nom_entreprise'   => 'sometimes|string|max:200',
            'type_prestataire' => 'sometimes|string|in:site,evenement,hotel,restaurant,transport',
            'email'            => 'sometimes|email|unique:prestataire,email,' . $prestataire->id,
            'tel'              => 'nullable|string|max:20',
        ]);

        $prestataire->update($validated);

        return response()->json($prestataire);
    }

    #[
        OA\Get(
            path: "/api/prestataire/dashboard",
            tags: ["Prestataires"],
            summary: "Statistiques du tableau de bord prestataire (nombre de fiches, réservations reçues)",
            security: [["bearerAuth" => []]],
            responses: [
                new OA\Response(response: 200, description: "Statistiques"),
            ],
        ),
    ]
    public function dashboard(Request $request)
    {
        $prestataire = $request->user();

        $idsSites = $prestataire->sites()->pluck('id');
        $idsEvenements = $prestataire->evenements()->pluck('id');

        $reservations = Reservation::whereIn('id_site', $idsSites)
            ->orWhereIn('id_evnmt', $idsEvenements)
            ->get();

        return response()->json([
            'nombre_sites'         => $idsSites->count(),
            'nombre_evenements'    => $idsEvenements->count(),
            'nombre_reservations'  => $reservations->count(),
            'reservations_confirmees' => $reservations->where('statut', 'confirmee')->count(),
            'montant_total_confirme' => $reservations->where('statut', 'confirmee')->sum('total'),
        ]);
    }
}
