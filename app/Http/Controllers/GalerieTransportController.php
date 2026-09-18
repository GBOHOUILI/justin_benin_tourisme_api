<?php

namespace App\Http\Controllers;

use App\Models\GalerieTransport;
use App\Models\Prestataire;
use App\Models\ResponsableRegional;
use App\Models\Transport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class GalerieTransportController extends Controller
{
    public function index(Request $request)
    {
        $query = GalerieTransport::with("transport");
        if ($request->filled("id_transport")) $query->where("id_transport", $request->id_transport);
        return response()->json($query->get());
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            "libelle" => "required|string|max:255",
            "type" => "required|string|in:image,video",
            "status" => "nullable|boolean",
            "id_transport" => "required|exists:transport,id",
            "fichier" => "nullable|file|mimes:jpg,jpeg,png,gif,mp4,mov|max:51200",
        ]);

        $user = $request->user();
        $colonne = $user instanceof ResponsableRegional ? 'id_responsable' : 'id_prestataire';
        if (($user instanceof Prestataire || $user instanceof ResponsableRegional)
            && !Transport::where('id', $validated['id_transport'])->where($colonne, $user->id)->exists()) {
            return response()->json(["message" => "Ce transport ne vous appartient pas."], 403);
        }

        if ($request->hasFile("fichier")) {
            $validated["url_fichier"] = $request->file("fichier")->store("galeries/transports", "public");
        }

        $galerie = GalerieTransport::create($validated);
        return response()->json($galerie->load("transport"), 201);
    }

    public function show(GalerieTransport $galerieTransport)
    {
        return response()->json($galerieTransport->load("transport"));
    }

    public function update(Request $request, GalerieTransport $galerieTransport)
    {
        $user = $request->user();
        if ($user instanceof Prestataire && $galerieTransport->transport?->id_prestataire !== $user->id) {
            return response()->json(["message" => "Cette galerie ne vous appartient pas."], 403);
        }
        if ($user instanceof ResponsableRegional && $galerieTransport->transport?->id_responsable !== $user->id) {
            return response()->json(["message" => "Cette galerie ne vous appartient pas."], 403);
        }

        $validated = $request->validate([
            "libelle" => "sometimes|string|max:255",
            "type" => "sometimes|string|in:image,video",
            "status" => "nullable|boolean",
        ]);

        $galerieTransport->update($validated);
        return response()->json($galerieTransport);
    }

    public function destroy(Request $request, GalerieTransport $galerieTransport)
    {
        $user = $request->user();
        if ($user instanceof Prestataire && $galerieTransport->transport?->id_prestataire !== $user->id) {
            return response()->json(["message" => "Cette galerie ne vous appartient pas."], 403);
        }
        if ($user instanceof ResponsableRegional && $galerieTransport->transport?->id_responsable !== $user->id) {
            return response()->json(["message" => "Cette galerie ne vous appartient pas."], 403);
        }

        if ($galerieTransport->url_fichier && Storage::disk("public")->exists($galerieTransport->url_fichier)) {
            Storage::disk("public")->delete($galerieTransport->url_fichier);
        }

        $galerieTransport->delete();
        return response()->json(["message" => "Média supprimé"], 200);
    }
}
