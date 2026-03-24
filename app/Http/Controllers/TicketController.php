<?php

namespace App\Http\Controllers;

use App\Models\Ticket;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

class TicketController extends Controller
{
    #[OA\Get(
        path: "/tickets",
        tags: ["Tickets"],
        summary: "Liste des tickets",
        parameters: [
            new OA\Parameter(name: "id_reservation", in: "query", description: "Filtrer par réservation", schema: new OA\Schema(type: "integer"))
        ],
        responses: [new OA\Response(response: 200, description: "Liste des tickets")]
    )]
    public function index(Request $request)
    {
        $query = Ticket::with(['reservation.user', 'utilisations']);
        if ($request->filled('id_reservation')) {
            $query->where('id_reservation', $request->id_reservation);
        }
        return response()->json($query->get());
    }

    #[OA\Post(
        path: "/tickets",
        tags: ["Tickets"],
        summary: "Créer un ticket",
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ["numero", "id_reservation"],
                properties: [
                    new OA\Property(property: "numero", type: "string", example: "TCK-1234"),
                    new OA\Property(property: "id_reservation", type: "integer")
                ]
            )
        ),
        responses: [
            new OA\Response(response: 201, description: "Ticket créé"),
            new OA\Response(response: 422, description: "Erreur de validation")
        ]
    )]
    public function store(Request $request)
    {
        $validated = $request->validate([
            'numero'         => 'required|string|unique:tickets,numero',
            'id_reservation' => 'required|exists:reservations,id',
        ]);

        $ticket = Ticket::create($validated);
        return response()->json($ticket->load('reservation'), 201);
    }

    #[OA\Get(
        path: "/tickets/{id}",
        tags: ["Tickets"],
        summary: "Afficher un ticket",
        parameters: [new OA\Parameter(name: "id", in: "path", required: true, schema: new OA\Schema(type: "integer"))],
        responses: [new OA\Response(response: 200, description: "Ticket affiché")]
    )]
    public function show(Ticket $ticket)
    {
        return response()->json($ticket->load(['reservation.user', 'reservation.site', 'reservation.evenement', 'utilisations.avis']));
    }

    #[OA\Put(
        path: "/tickets/{id}",
        tags: ["Tickets"],
        summary: "Mettre à jour un ticket",
        parameters: [new OA\Parameter(name: "id", in: "path", required: true, schema: new OA\Schema(type: "integer"))],
        requestBody: new OA\RequestBody(
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: "numero", type: "string")
                ]
            )
        ),
        responses: [new OA\Response(response: 200, description: "Ticket mis à jour")]
    )]
    public function update(Request $request, Ticket $ticket)
    {
        $validated = $request->validate([
            'numero' => 'sometimes|string|unique:tickets,numero,' . $ticket->id,
        ]);

        $ticket->update($validated);
        return response()->json($ticket);
    }

    #[OA\Delete(
        path: "/tickets/{id}",
        tags: ["Tickets"],
        summary: "Supprimer un ticket",
        parameters: [new OA\Parameter(name: "id", in: "path", required: true, schema: new OA\Schema(type: "integer"))],
        responses: [new OA\Response(response: 200, description: "Ticket supprimé")]
    )]
    public function destroy(Ticket $ticket)
    {
        $ticket->delete();
        return response()->json(['message' => 'Ticket supprimé'], 200);
    }

    #[OA\Post(
        path: "/tickets/verifier",
        tags: ["Tickets"],
        summary: "Vérifier un ticket par numéro",
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ["numero"],
                properties: [
                    new OA\Property(property: "numero", type: "string", example: "TCK-1234")
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: "Ticket vérifié"),
            new OA\Response(response: 404, description: "Ticket introuvable")
        ]
    )]
    public function verifier(Request $request)
    {
        $request->validate(['numero' => 'required|string']);

        $ticket = Ticket::where('numero', $request->numero)
                        ->with(['reservation', 'utilisations'])
                        ->first();

        if (!$ticket) {
            return response()->json(['message' => 'Ticket introuvable', 'valide' => false], 404);
        }

        $dejaUtilise = $ticket->utilisations()->exists();

        return response()->json([
            'valide'       => !$dejaUtilise,
            'ticket'       => $ticket,
            'deja_utilise' => $dejaUtilise,
        ]);
    }
}