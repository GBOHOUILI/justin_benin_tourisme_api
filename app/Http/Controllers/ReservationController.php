<?php

namespace App\Http\Controllers;

use App\Models\Reservation;
use App\Models\Ticket;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

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

    public function store(Request $request)
    {
        $validated = $request->validate([
            'type'        => 'required|string|in:site,evenement',
            'prix'        => 'required|numeric|min:0',
            'nombre'      => 'required|integer|min:1',
            'description' => 'nullable|string',
            'id_site'     => 'nullable|exists:site,id',
            'id_evnmt'    => 'nullable|exists:evenement,id',
        ]);

        $validated['id_user'] = $request->user()->id;
        $validated['total']   = $validated['prix'] * $validated['nombre'];

        $reservation = Reservation::create($validated);

        // Génération automatique des tickets
        for ($i = 0; $i < $validated['nombre']; $i++) {
            Ticket::create([
                'numero'         => 'TCK-' . strtoupper(Str::random(8)),
                'id_reservation' => $reservation->id,
            ]);
        }

        // CORRECTION : pas de 'user' dans le load (récursion)
        return response()->json(
            $reservation->load(['site', 'evenement', 'tickets']),
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