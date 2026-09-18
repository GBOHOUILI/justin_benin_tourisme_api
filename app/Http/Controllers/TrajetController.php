<?php

namespace App\Http\Controllers;

use App\Models\Prestataire;
use App\Models\ResponsableRegional;
use App\Models\Trajet;
use App\Models\Transport;
use Illuminate\Http\Request;

class TrajetController extends Controller
{
    private function doitVerifierOwnership($user): bool
    {
        return $user instanceof Prestataire || $user instanceof ResponsableRegional;
    }

    private function appartientAuCreateur($user, int $idTransport): bool
    {
        $colonne = $user instanceof ResponsableRegional ? 'id_responsable' : 'id_prestataire';
        return Transport::where('id', $idTransport)->where($colonne, $user->id)->exists();
    }

    public function index(Request $request)
    {
        $query = Trajet::with(['villeDepart', 'villeArrivee']);
        if ($request->filled('id_transport')) $query->where('id_transport', $request->id_transport);
        if ($request->filled('id_ville_depart')) $query->where('id_ville_depart', $request->id_ville_depart);
        if ($request->filled('id_ville_arrivee')) $query->where('id_ville_arrivee', $request->id_ville_arrivee);
        return response()->json($query->get());
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'id_transport' => 'required|exists:transport,id',
            'id_ville_depart' => 'required|exists:ville,id',
            'id_ville_arrivee' => 'required|different:id_ville_depart|exists:ville,id',
            'horaire_depart' => 'required|date_format:H:i',
            'prix' => 'required|numeric|min:0',
        ]);

        if ($this->doitVerifierOwnership($request->user())
            && !$this->appartientAuCreateur($request->user(), $validated['id_transport'])) {
            return response()->json(['message' => "Ce transport ne vous appartient pas."], 403);
        }

        $trajet = Trajet::create($validated);
        return response()->json($trajet->load(['villeDepart', 'villeArrivee']), 201);
    }

    public function show(Trajet $trajet)
    {
        return response()->json($trajet->load(['villeDepart', 'villeArrivee']));
    }

    public function update(Request $request, Trajet $trajet)
    {
        if ($this->doitVerifierOwnership($request->user())
            && !$this->appartientAuCreateur($request->user(), $trajet->id_transport)) {
            return response()->json(['message' => "Ce trajet ne vous appartient pas."], 403);
        }

        $validated = $request->validate([
            'id_ville_depart' => 'sometimes|exists:ville,id',
            'id_ville_arrivee' => 'sometimes|different:id_ville_depart|exists:ville,id',
            'horaire_depart' => 'sometimes|date_format:H:i',
            'prix' => 'sometimes|numeric|min:0',
        ]);

        $trajet->update($validated);
        return response()->json($trajet->load(['villeDepart', 'villeArrivee']));
    }

    public function destroy(Request $request, Trajet $trajet)
    {
        if ($this->doitVerifierOwnership($request->user())
            && !$this->appartientAuCreateur($request->user(), $trajet->id_transport)) {
            return response()->json(['message' => "Ce trajet ne vous appartient pas."], 403);
        }

        $trajet->delete();
        return response()->json(['message' => 'Trajet supprimé'], 200);
    }
}
