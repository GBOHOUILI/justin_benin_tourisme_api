<?php

namespace App\Http\Controllers;

use App\Models\Chambre;
use App\Models\Hotel;
use App\Models\Prestataire;
use App\Models\ResponsableRegional;
use Illuminate\Http\Request;

class ChambreController extends Controller
{
    private function doitVerifierOwnership($user): bool
    {
        return $user instanceof Prestataire || $user instanceof ResponsableRegional;
    }

    private function appartientAuCreateur($user, int $idHotel): bool
    {
        $colonne = $user instanceof ResponsableRegional ? 'id_responsable' : 'id_prestataire';
        return Hotel::where('id', $idHotel)->where($colonne, $user->id)->exists();
    }

    public function index(Request $request)
    {
        $query = Chambre::query();
        if ($request->filled('id_hotel')) $query->where('id_hotel', $request->id_hotel);
        return response()->json($query->get());
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'id_hotel' => 'required|exists:hotel,id',
            'type_chambre' => 'required|string|max:100',
            'prix_nuit' => 'required|numeric|min:0',
            'capacite' => 'nullable|integer|min:1',
            'disponibilite' => 'nullable|boolean',
        ]);

        if ($this->doitVerifierOwnership($request->user())
            && !$this->appartientAuCreateur($request->user(), $validated['id_hotel'])) {
            return response()->json(['message' => "Cet hôtel ne vous appartient pas."], 403);
        }

        $chambre = Chambre::create($validated);
        return response()->json($chambre, 201);
    }

    public function show(Chambre $chambre)
    {
        return response()->json($chambre);
    }

    public function update(Request $request, Chambre $chambre)
    {
        if ($this->doitVerifierOwnership($request->user())
            && !$this->appartientAuCreateur($request->user(), $chambre->id_hotel)) {
            return response()->json(['message' => "Cette chambre ne vous appartient pas."], 403);
        }

        $validated = $request->validate([
            'type_chambre' => 'sometimes|string|max:100',
            'prix_nuit' => 'sometimes|numeric|min:0',
            'capacite' => 'nullable|integer|min:1',
            'disponibilite' => 'nullable|boolean',
        ]);

        $chambre->update($validated);
        return response()->json($chambre);
    }

    public function destroy(Request $request, Chambre $chambre)
    {
        if ($this->doitVerifierOwnership($request->user())
            && !$this->appartientAuCreateur($request->user(), $chambre->id_hotel)) {
            return response()->json(['message' => "Cette chambre ne vous appartient pas."], 403);
        }

        $chambre->delete();
        return response()->json(['message' => 'Chambre supprimée'], 200);
    }
}
