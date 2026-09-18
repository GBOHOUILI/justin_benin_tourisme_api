<?php

namespace App\Http\Controllers;

use App\Models\Prestataire;
use App\Models\Prix;
use Illuminate\Http\Request;

class PrixController extends Controller
{
    /** Vrai si le tarif (existant ou ciblé par site/evnmt) appartient à ce prestataire. */
    private function appartientAuPrestataire(Prestataire $prestataire, ?int $idSite, ?int $idEvnmt): bool
    {
        if ($idSite) {
            return \App\Models\Site::where('id', $idSite)->where('id_prestataire', $prestataire->id)->exists();
        }
        if ($idEvnmt) {
            return \App\Models\Evenement::where('id', $idEvnmt)->where('id_prestataire', $prestataire->id)->exists();
        }
        return false;
    }

    public function index(Request $request)
    {
        $query = Prix::query();
        if ($request->filled('id_site'))  $query->where('id_site', $request->id_site);
        if ($request->filled('id_evnmt')) $query->where('id_evnmt', $request->id_evnmt);
        return response()->json($query->get());
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'libelle' => 'required|string|max:200',
            'montant' => 'required|numeric|min:0',
            // CORRECTION: tables correctes (site/evenement sans 's')
            'id_site'  => 'nullable|exists:site,id',
            'id_evnmt' => 'nullable|exists:evenement,id',
        ]);

        if (empty($validated['id_site']) && empty($validated['id_evnmt'])) {
            return response()->json(
                ['message' => 'Un site ou un événement est requis'],
                422
            );
        }

        if ($request->user() instanceof Prestataire
            && !$this->appartientAuPrestataire($request->user(), $validated['id_site'] ?? null, $validated['id_evnmt'] ?? null)) {
            return response()->json(['message' => "Ce site/événement ne vous appartient pas."], 403);
        }

        $prix = Prix::create($validated);
        return response()->json($prix, 201);
    }

    public function show(Prix $prix)
    {
        return response()->json($prix);
    }

    public function update(Request $request, Prix $prix)
    {
        if ($request->user() instanceof Prestataire
            && !$this->appartientAuPrestataire($request->user(), $prix->id_site, $prix->id_evnmt)) {
            return response()->json(['message' => "Ce tarif ne vous appartient pas."], 403);
        }

        $validated = $request->validate([
            'libelle'  => 'sometimes|string|max:200',
            'montant'  => 'sometimes|numeric|min:0',
            'id_site'  => 'nullable|exists:site,id',
            'id_evnmt' => 'nullable|exists:evenement,id',
        ]);

        $prix->update($validated);
        return response()->json($prix);
    }

    public function destroy(Request $request, Prix $prix)
    {
        if ($request->user() instanceof Prestataire
            && !$this->appartientAuPrestataire($request->user(), $prix->id_site, $prix->id_evnmt)) {
            return response()->json(['message' => "Ce tarif ne vous appartient pas."], 403);
        }

        $prix->delete();
        return response()->json(['message' => 'Prix supprimé'], 200);
    }
}