<?php

namespace App\Http\Controllers;

use App\Models\Circuit;
use App\Models\EtapeCircuit;
use App\Models\Reservation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use OpenApi\Attributes as OA;

class EtapeCircuitController extends Controller
{
    #[
        OA\Post(
            path: "/api/circuits/{circuit}/etapes",
            tags: ["Circuits"],
            summary: "Ajouter une étape à un circuit (site XOR événement, ordre auto si omis)",
            security: [["sanctum" => []]],
            parameters: [
                new OA\Parameter(name: "circuit", in: "path", required: true, schema: new OA\Schema(type: "integer")),
            ],
            requestBody: new OA\RequestBody(
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "id_site", type: "integer"),
                        new OA\Property(property: "id_evnmt", type: "integer"),
                        new OA\Property(property: "ordre", type: "integer"),
                    ],
                ),
            ),
            responses: [
                new OA\Response(response: 201, description: "Étape ajoutée"),
                new OA\Response(response: 422, description: "Ni site ni événement, ou les deux à la fois"),
            ],
        ),
    ]
    public function store(Request $request, Circuit $circuit)
    {
        if ($circuit->id_user !== $request->user()->id) {
            return response()->json(['message' => 'Accès refusé.'], 403);
        }

        $validated = $request->validate([
            'id_site'  => 'nullable|exists:site,id',
            'id_evnmt' => 'nullable|exists:evenement,id',
            'ordre'    => 'nullable|integer|min:1',
        ]);

        $aSite   = !empty($validated['id_site']);
        $aEvnmt  = !empty($validated['id_evnmt']);
        if ($aSite === $aEvnmt) {
            return response()->json([
                'message' => 'Une étape doit référencer soit un site, soit un événement (un seul des deux).',
            ], 422);
        }

        $validated['id_circuit'] = $circuit->id;
        $validated['ordre'] = $validated['ordre']
            ?? ((int) $circuit->etapes()->max('ordre') + 1);

        $etape = EtapeCircuit::create($validated);

        return response()->json(
            $etape->load(['site', 'evenement', 'reservation']),
            201,
        );
    }

    #[
        OA\Put(
            path: "/api/etapes/{id}",
            tags: ["Circuits"],
            summary: "Mettre à jour une étape (ordre unitaire, ou lier une Reservation existante)",
            security: [["sanctum" => []]],
            parameters: [
                new OA\Parameter(name: "id", in: "path", required: true, schema: new OA\Schema(type: "integer")),
            ],
            requestBody: new OA\RequestBody(
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "ordre", type: "integer"),
                        new OA\Property(
                            property: "id_reservation",
                            type: "integer",
                            description: "Doit appartenir à l'utilisateur et référencer le même site/événement que l'étape",
                        ),
                    ],
                ),
            ),
            responses: [
                new OA\Response(response: 200, description: "Étape mise à jour"),
                new OA\Response(response: 403, description: "Accès refusé ou réservation appartenant à un autre utilisateur"),
                new OA\Response(response: 422, description: "La réservation ne correspond pas au site/événement de l'étape"),
            ],
        ),
    ]
    public function update(Request $request, EtapeCircuit $etape)
    {
        if ($etape->circuit->id_user !== $request->user()->id) {
            return response()->json(['message' => 'Accès refusé.'], 403);
        }

        $validated = $request->validate([
            'ordre'          => 'sometimes|integer|min:1',
            'id_reservation' => 'nullable|exists:reservation,id',
        ]);

        if (array_key_exists('id_reservation', $validated) && $validated['id_reservation']) {
            $reservation = Reservation::findOrFail($validated['id_reservation']);

            if ($reservation->id_user !== $request->user()->id) {
                return response()->json(['message' => 'Cette réservation ne vous appartient pas.'], 403);
            }

            $memeCible = ($etape->id_site && $reservation->id_site === $etape->id_site)
                || ($etape->id_evnmt && $reservation->id_evnmt === $etape->id_evnmt);

            if (!$memeCible) {
                return response()->json([
                    'message' => "Cette réservation ne correspond pas au site/événement de l'étape.",
                ], 422);
            }
        }

        $etape->update($validated);

        return response()->json($etape->load(['site', 'evenement', 'reservation']));
    }

    #[
        OA\Delete(
            path: "/api/etapes/{id}",
            tags: ["Circuits"],
            summary: "Retirer une étape du circuit (la Reservation liée n'est jamais supprimée)",
            security: [["sanctum" => []]],
            parameters: [
                new OA\Parameter(name: "id", in: "path", required: true, schema: new OA\Schema(type: "integer")),
            ],
            responses: [
                new OA\Response(response: 200, description: "Étape retirée"),
                new OA\Response(response: 403, description: "Accès refusé"),
            ],
        ),
    ]
    public function destroy(Request $request, EtapeCircuit $etape)
    {
        if ($etape->circuit->id_user !== $request->user()->id) {
            return response()->json(['message' => 'Accès refusé.'], 403);
        }

        // On ne supprime jamais la Reservation liée : elle reste valide indépendamment
        // du circuit (peut être déjà payée). Seul le rattachement à l'étape disparaît.
        $etape->delete();

        return response()->json(['message' => 'Étape retirée du circuit.'], 200);
    }

    #[
        OA\Patch(
            path: "/api/circuits/{circuit}/etapes/reordonner",
            tags: ["Circuits"],
            summary: "Réordonner les étapes d'un circuit",
            security: [["sanctum" => []]],
            parameters: [
                new OA\Parameter(name: "circuit", in: "path", required: true, schema: new OA\Schema(type: "integer")),
            ],
            requestBody: new OA\RequestBody(
                required: true,
                content: new OA\JsonContent(
                    required: ["ordre"],
                    properties: [
                        new OA\Property(
                            property: "ordre",
                            type: "array",
                            items: new OA\Items(type: "integer"),
                            description: "Liste des id d'étapes dans le nouvel ordre souhaité — doit contenir exactement toutes les étapes du circuit",
                        ),
                    ],
                ),
            ),
            responses: [
                new OA\Response(response: 200, description: "Circuit avec ses étapes réordonnées"),
                new OA\Response(response: 422, description: "La liste ne correspond pas exactement aux étapes du circuit"),
            ],
        ),
    ]
    public function reordonner(Request $request, Circuit $circuit)
    {
        if ($circuit->id_user !== $request->user()->id) {
            return response()->json(['message' => 'Accès refusé.'], 403);
        }

        $validated = $request->validate([
            'ordre'   => 'required|array|min:1',
            'ordre.*' => 'integer|exists:etape_circuit,id',
        ]);

        $etapesCircuit = $circuit->etapes()->pluck('id');
        $fourni        = collect($validated['ordre']);

        if ($fourni->count() !== $etapesCircuit->count() || $fourni->diff($etapesCircuit)->isNotEmpty()) {
            return response()->json([
                'message' => 'La liste fournie doit contenir exactement les étapes de ce circuit.',
            ], 422);
        }

        DB::transaction(function () use ($fourni, $circuit) {
            foreach ($fourni->values() as $index => $idEtape) {
                EtapeCircuit::where('id', $idEtape)
                    ->where('id_circuit', $circuit->id)
                    ->update(['ordre' => $index + 1]);
            }
        });

        return response()->json($circuit->fresh()->load(['etapes.site', 'etapes.evenement', 'etapes.reservation']));
    }
}
