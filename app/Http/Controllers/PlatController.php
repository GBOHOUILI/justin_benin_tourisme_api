<?php

namespace App\Http\Controllers;

use App\Models\Plat;
use App\Models\Prestataire;
use App\Models\Restaurant;
use App\Models\ResponsableRegional;
use Illuminate\Http\Request;

class PlatController extends Controller
{
    private function doitVerifierOwnership($user): bool
    {
        return $user instanceof Prestataire || $user instanceof ResponsableRegional;
    }

    private function appartientAuCreateur($user, int $idRestaurant): bool
    {
        $colonne = $user instanceof ResponsableRegional ? 'id_responsable' : 'id_prestataire';
        return Restaurant::where('id', $idRestaurant)->where($colonne, $user->id)->exists();
    }

    public function index(Request $request)
    {
        $query = Plat::query();
        if ($request->filled('id_restaurant')) $query->where('id_restaurant', $request->id_restaurant);
        return response()->json($query->get());
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'id_restaurant' => 'required|exists:restaurant,id',
            'nom' => 'required|string|max:150',
            'prix' => 'required|numeric|min:0',
            'description' => 'nullable|string',
        ]);

        if ($this->doitVerifierOwnership($request->user())
            && !$this->appartientAuCreateur($request->user(), $validated['id_restaurant'])) {
            return response()->json(['message' => "Ce restaurant ne vous appartient pas."], 403);
        }

        $plat = Plat::create($validated);
        return response()->json($plat, 201);
    }

    public function show(Plat $plat)
    {
        return response()->json($plat);
    }

    public function update(Request $request, Plat $plat)
    {
        if ($this->doitVerifierOwnership($request->user())
            && !$this->appartientAuCreateur($request->user(), $plat->id_restaurant)) {
            return response()->json(['message' => "Ce plat ne vous appartient pas."], 403);
        }

        $validated = $request->validate([
            'nom' => 'sometimes|string|max:150',
            'prix' => 'sometimes|numeric|min:0',
            'description' => 'nullable|string',
        ]);

        $plat->update($validated);
        return response()->json($plat);
    }

    public function destroy(Request $request, Plat $plat)
    {
        if ($this->doitVerifierOwnership($request->user())
            && !$this->appartientAuCreateur($request->user(), $plat->id_restaurant)) {
            return response()->json(['message' => "Ce plat ne vous appartient pas."], 403);
        }

        $plat->delete();
        return response()->json(['message' => 'Plat supprimé'], 200);
    }
}
