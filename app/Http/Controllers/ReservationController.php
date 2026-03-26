<?php

namespace App\Http\Controllers;

use App\Models\Reservation;
use App\Models\Ticket;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use OpenApi\Attributes as OA;

class ReservationController extends Controller
{
    #[
        OA\Get(
            path: "/api/reservations",
            tags: ["Reservations"],
            summary: "Lister les réservations",
            parameters: [
                new OA\Parameter(
                    name: "id_user",
                    in: "query",
                    schema: new OA\Schema(type: "integer"),
                ),
                new OA\Parameter(
                    name: "id_site",
                    in: "query",
                    schema: new OA\Schema(type: "integer"),
                ),
                new OA\Parameter(
                    name: "id_evnmt",
                    in: "query",
                    schema: new OA\Schema(type: "integer"),
                ),
                new OA\Parameter(
                    name: "type",
                    in: "query",
                    schema: new OA\Schema(
                        type: "string",
                        enum: ["site", "evenement"],
                    ),
                ),
            ],
            responses: [
                new OA\Response(
                    response: 200,
                    description: "Liste des réservations",
                ),
            ],
        ),
    ]
    public function index(Request $request)
    {
        $query = Reservation::query();
        if ($request->filled("id_user")) {
            $query->where("id_user", $request->id_user);
        }
        if ($request->filled("id_site")) {
            $query->where("id_site", $request->id_site);
        }
        if ($request->filled("id_evnmt")) {
            $query->where("id_evnmt", $request->id_evnmt);
        }
        if ($request->filled("type")) {
            $query->where("type", $request->type);
        }

        return response()->json($query->latest()->paginate(15));
    }

    #[
        OA\Post(
            path: "/api/reservations",
            tags: ["Reservations"],
            summary: "Créer une réservation",
            requestBody: new OA\RequestBody(
                content: new OA\JsonContent(
                    required: ["type", "prix", "nombre", "id_user"],
                    properties: [
                        new OA\Property(
                            property: "type",
                            type: "string",
                            example: "site",
                        ),
                        new OA\Property(property: "prix", type: "number"),
                        new OA\Property(property: "nombre", type: "integer"),
                        new OA\Property(
                            property: "description",
                            type: "string",
                        ),
                        new OA\Property(property: "id_site", type: "integer"),
                        new OA\Property(property: "id_evnmt", type: "integer"),
                        new OA\Property(property: "id_user", type: "integer"),
                    ],
                ),
            ),
            responses: [
                new OA\Response(
                    response: 201,
                    description: "Réservation créée",
                ),
            ],
        ),
    ]
    public function store(Request $request)
    {
        $validated = $request->validate([
            "type" => "required|string|in:site,evenement",
            "prix" => "required|numeric|min:0",
            "nombre" => "required|integer|min:1",
            "description" => "nullable|string",
            "id_site" => "nullable|exists:sites,id",
            "id_evnmt" => "nullable|exists:evenements,id",
            "id_user" => "required|exists:users,id",
        ]);

        $validated["total"] = $validated["prix"] * $validated["nombre"];
        $reservation = Reservation::create($validated);

        for ($i = 0; $i < $validated["nombre"]; $i++) {
            Ticket::create([
                "numero" => strtoupper(Str::random(10)),
                "id_reservation" => $reservation->id,
            ]);
        }

        return response()->json(
            $reservation->load(["user", "site", "evenement", "tickets"]),
            201,
        );
    }

    #[
        OA\Get(
            path: "/api/reservations/{id}",
            tags: ["Reservations"],
            summary: "Afficher une réservation",
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
                    description: "Détails de la réservation",
                ),
            ],
        ),
    ]
    public function show(Reservation $reservation)
    {
        return response()->json(
            $reservation->load([
                "user",
                "site",
                "evenement",
                "tickets.utilisations.avis",
            ]),
        );
    }

    #[
        OA\Put(
            path: "/api/reservations/{id}",
            tags: ["Reservations"],
            summary: "Mettre à jour une réservation",
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
                        new OA\Property(property: "type", type: "string"),
                        new OA\Property(property: "prix", type: "number"),
                        new OA\Property(property: "nombre", type: "integer"),
                        new OA\Property(
                            property: "description",
                            type: "string",
                        ),
                        new OA\Property(property: "id_site", type: "integer"),
                        new OA\Property(property: "id_evnmt", type: "integer"),
                    ],
                ),
            ),
            responses: [
                new OA\Response(
                    response: 200,
                    description: "Réservation mise à jour",
                ),
            ],
        ),
    ]
    public function update(Request $request, Reservation $reservation)
    {
        $validated = $request->validate([
            "type" => "sometimes|string|in:site,evenement",
            "prix" => "sometimes|numeric|min:0",
            "nombre" => "sometimes|integer|min:1",
            "description" => "nullable|string",
            "id_site" => "nullable|exists:sites,id",
            "id_evnmt" => "nullable|exists:evenements,id",
        ]);

        if (isset($validated["prix"]) || isset($validated["nombre"])) {
            $prix = $validated["prix"] ?? $reservation->prix;
            $nombre = $validated["nombre"] ?? $reservation->nombre;
            $validated["total"] = $prix * $nombre;
        }

        $reservation->update($validated);
        return response()->json(
            $reservation->load(["user", "site", "evenement", "tickets"]),
        );
    }

    #[
        OA\Delete(
            path: "/api/reservations/{id}",
            tags: ["Reservations"],
            summary: "Supprimer une réservation",
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
                    description: "Réservation annulée",
                ),
            ],
        ),
    ]
    public function destroy(Reservation $reservation)
    {
        $reservation->delete();
        return response()->json(["message" => "Réservation annulée"], 200);
    }
}
