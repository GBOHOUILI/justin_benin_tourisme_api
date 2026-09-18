<?php

namespace App\Http\Controllers;

use App\Models\GalerieHotel;
use App\Models\Hotel;
use App\Models\Prestataire;
use App\Models\ResponsableRegional;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class GalerieHotelController extends Controller
{
    public function index(Request $request)
    {
        $query = GalerieHotel::with("hotel");
        if ($request->filled("id_hotel")) $query->where("id_hotel", $request->id_hotel);
        return response()->json($query->get());
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            "libelle" => "required|string|max:255",
            "type" => "required|string|in:image,video",
            "status" => "nullable|boolean",
            "id_hotel" => "required|exists:hotel,id",
            "fichier" => "nullable|file|mimes:jpg,jpeg,png,gif,mp4,mov|max:51200",
        ]);

        $user = $request->user();
        $colonne = $user instanceof ResponsableRegional ? 'id_responsable' : 'id_prestataire';
        if (($user instanceof Prestataire || $user instanceof ResponsableRegional)
            && !Hotel::where('id', $validated['id_hotel'])->where($colonne, $user->id)->exists()) {
            return response()->json(["message" => "Cet hôtel ne vous appartient pas."], 403);
        }

        if ($request->hasFile("fichier")) {
            $validated["url_fichier"] = $request->file("fichier")->store("galeries/hotels", "public");
        }

        $galerie = GalerieHotel::create($validated);
        return response()->json($galerie->load("hotel"), 201);
    }

    public function show(GalerieHotel $galerieHotel)
    {
        return response()->json($galerieHotel->load("hotel"));
    }

    public function update(Request $request, GalerieHotel $galerieHotel)
    {
        $user = $request->user();
        if ($user instanceof Prestataire && $galerieHotel->hotel?->id_prestataire !== $user->id) {
            return response()->json(["message" => "Cette galerie ne vous appartient pas."], 403);
        }
        if ($user instanceof ResponsableRegional && $galerieHotel->hotel?->id_responsable !== $user->id) {
            return response()->json(["message" => "Cette galerie ne vous appartient pas."], 403);
        }

        $validated = $request->validate([
            "libelle" => "sometimes|string|max:255",
            "type" => "sometimes|string|in:image,video",
            "status" => "nullable|boolean",
        ]);

        $galerieHotel->update($validated);
        return response()->json($galerieHotel);
    }

    public function destroy(Request $request, GalerieHotel $galerieHotel)
    {
        $user = $request->user();
        if ($user instanceof Prestataire && $galerieHotel->hotel?->id_prestataire !== $user->id) {
            return response()->json(["message" => "Cette galerie ne vous appartient pas."], 403);
        }
        if ($user instanceof ResponsableRegional && $galerieHotel->hotel?->id_responsable !== $user->id) {
            return response()->json(["message" => "Cette galerie ne vous appartient pas."], 403);
        }

        if ($galerieHotel->url_fichier && Storage::disk("public")->exists($galerieHotel->url_fichier)) {
            Storage::disk("public")->delete($galerieHotel->url_fichier);
        }

        $galerieHotel->delete();
        return response()->json(["message" => "Média supprimé"], 200);
    }
}
