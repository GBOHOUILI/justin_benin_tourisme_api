<?php

namespace App\Http\Controllers;

use App\Models\Prix;
use App\Models\Reservation;
use App\Models\Ticket;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use OpenApi\Attributes as OA;

class ReservationController extends Controller
{
    public function index(Request $request)
    {
        // CORRECTION : on charge site et evenement SANS user (évite récursion)
        $query = Reservation::with(['site', 'evenement', 'tickets'])
            ->where('id_user', $request->user()->id);

        if ($request->filled('id_site'))  $query->where('id_site', $request->id_site);
        if ($request->filled('id_evnmt')) $query->where('id_evnmt', $request->id_evnmt);
        if ($request->filled('type'))     $query->where('type', $request->type);

        return response()->json($query->latest()->paginate(15));
    }

    #[
        OA\Post(
            path: "/api/reservations",
            tags: ["Réservations"],
            summary: "Créer une réservation - gratuite (tarif à 0 ou absent) ou payante (statut en_attente_paiement, ticket différé au paiement)",
            security: [["sanctum" => []]],
            requestBody: new OA\RequestBody(
                required: true,
                content: new OA\JsonContent(
                    required: ["type", "nombre"],
                    properties: [
                        new OA\Property(property: "type", type: "string", enum: ["site", "evenement"]),
                        new OA\Property(property: "nombre", type: "integer", minimum: 1),
                        new OA\Property(property: "description", type: "string"),
                        new OA\Property(property: "id_site", type: "integer"),
                        new OA\Property(property: "id_evnmt", type: "integer"),
                        new OA\Property(
                            property: "id_prix",
                            type: "integer",
                            description: "Tarif choisi (table Prix) - le montant n'est jamais fourni par le client, il est résolu côté serveur depuis ce tarif",
                        ),
                    ],
                ),
            ),
            responses: [
                new OA\Response(response: 201, description: "Réservation créée (ticket immédiat si gratuite, en attente de paiement sinon)"),
                new OA\Response(response: 422, description: "Erreur de validation ou tarif ne correspondant pas au site/événement"),
            ],
        ),
    ]
    public function store(Request $request)
    {
        $validated = $request->validate([
            'type'        => 'required|string|in:site,evenement',
            'nombre'      => 'required|integer|min:1',
            'description' => 'nullable|string',
            'id_site'     => 'nullable|exists:site,id',
            'id_evnmt'    => 'nullable|exists:evenement,id',
            'id_prix'     => 'nullable|exists:prix,id',
        ]);

        $prix = null;
        if (!empty($validated['id_prix'])) {
            $prix = Prix::find($validated['id_prix']);

            if (!empty($validated['id_site']) && $prix->id_site !== (int) $validated['id_site']) {
                return response()->json(['message' => "Ce tarif n'appartient pas au site sélectionné."], 422);
            }
            if (!empty($validated['id_evnmt']) && $prix->id_evnmt !== (int) $validated['id_evnmt']) {
                return response()->json(['message' => "Ce tarif n'appartient pas à l'événement sélectionné."], 422);
            }
        }

        // Le montant n'est jamais accepté depuis le client : il vient du tarif
        // (Prix) résolu côté serveur, jamais d'un decimal libre saisi côté client.
        $montantUnitaire = $prix->montant ?? 0;

        $validated['id_user'] = $request->user()->id;
        $validated['prix']    = $montantUnitaire;
        $validated['total']   = $montantUnitaire * $validated['nombre'];
        $validated['statut']  = $montantUnitaire > 0 ? 'en_attente_paiement' : 'confirmee';

        $reservation = Reservation::create($validated);

        // Ticket immédiat uniquement pour une réservation gratuite (statut confirmee) :
        // pour une réservation payante, le ticket n'existe qu'une fois le paiement confirmé
        // (cf. PaiementController::reconcilierCommande).
        if ($reservation->statut === 'confirmee') {
            for ($i = 0; $i < $validated['nombre']; $i++) {
                Ticket::create([
                    'numero'         => 'TCK-' . strtoupper(Str::random(8)),
                    'id_reservation' => $reservation->id,
                ]);
            }
        }

        // CORRECTION : pas de 'user' dans le load (récursion)
        return response()->json(
            $reservation->load(['site', 'evenement', 'tickets', 'tarif']),
            201
        );
    }

    public function show(Request $request, Reservation $reservation)
    {
        if ($reservation->id_user !== $request->user()->id) {
            return response()->json(['message' => 'Accès refusé.'], 403);
        }

        return response()->json(
            $reservation->load(['site', 'evenement', 'tickets'])
        );
    }

    public function update(Request $request, Reservation $reservation)
    {
        if ($reservation->id_user !== $request->user()->id) {
            return response()->json(['message' => 'Accès refusé.'], 403);
        }

        $validated = $request->validate([
            'type'        => 'sometimes|string|in:site,evenement',
            'prix'        => 'sometimes|numeric|min:0',
            'nombre'      => 'sometimes|integer|min:1',
            'description' => 'nullable|string',
            'id_site'     => 'nullable|exists:site,id',
            'id_evnmt'    => 'nullable|exists:evenement,id',
        ]);

        if (isset($validated['prix']) || isset($validated['nombre'])) {
            $prix   = $validated['prix']   ?? $reservation->prix;
            $nombre = $validated['nombre'] ?? $reservation->nombre;
            $validated['total'] = $prix * $nombre;
        }

        $reservation->update($validated);
        return response()->json($reservation->load(['site', 'evenement', 'tickets']));
    }

    public function destroy(Request $request, Reservation $reservation)
    {
        if ($reservation->id_user !== $request->user()->id) {
            return response()->json(['message' => 'Accès refusé.'], 403);
        }

        $reservation->delete();
        return response()->json(['message' => 'Réservation annulée'], 200);
    }
}