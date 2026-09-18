<?php

namespace App\Http\Controllers;

use App\Models\GalerieRestaurant;
use App\Models\Prestataire;
use App\Models\Restaurant;
use App\Models\ResponsableRegional;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class GalerieRestaurantController extends Controller
{
    public function index(Request $request)
    {
        $query = GalerieRestaurant::with("restaurant");
        if ($request->filled("id_restaurant")) $query->where("id_restaurant", $request->id_restaurant);
        return response()->json($query->get());
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            "libelle" => "required|string|max:255",
            "type" => "required|string|in:image,video",
            "status" => "nullable|boolean",
            "id_restaurant" => "required|exists:restaurant,id",
            "fichier" => "nullable|file|mimes:jpg,jpeg,png,gif,mp4,mov|max:51200",
        ]);

        $user = $request->user();
        $colonne = $user instanceof ResponsableRegional ? 'id_responsable' : 'id_prestataire';
        if (($user instanceof Prestataire || $user instanceof ResponsableRegional)
            && !Restaurant::where('id', $validated['id_restaurant'])->where($colonne, $user->id)->exists()) {
            return response()->json(["message" => "Ce restaurant ne vous appartient pas."], 403);
        }

        if ($request->hasFile("fichier")) {
            $validated["url_fichier"] = $request->file("fichier")->store("galeries/restaurants", "public");
        }

        $galerie = GalerieRestaurant::create($validated);
        return response()->json($galerie->load("restaurant"), 201);
    }

    public function show(GalerieRestaurant $galerieRestaurant)
    {
        return response()->json($galerieRestaurant->load("restaurant"));
    }

    public function update(Request $request, GalerieRestaurant $galerieRestaurant)
    {
        $user = $request->user();
        if ($user instanceof Prestataire && $galerieRestaurant->restaurant?->id_prestataire !== $user->id) {
            return response()->json(["message" => "Cette galerie ne vous appartient pas."], 403);
        }
        if ($user instanceof ResponsableRegional && $galerieRestaurant->restaurant?->id_responsable !== $user->id) {
            return response()->json(["message" => "Cette galerie ne vous appartient pas."], 403);
        }

        $validated = $request->validate([
            "libelle" => "sometimes|string|max:255",
            "type" => "sometimes|string|in:image,video",
            "status" => "nullable|boolean",
        ]);

        $galerieRestaurant->update($validated);
        return response()->json($galerieRestaurant);
    }

    public function destroy(Request $request, GalerieRestaurant $galerieRestaurant)
    {
        $user = $request->user();
        if ($user instanceof Prestataire && $galerieRestaurant->restaurant?->id_prestataire !== $user->id) {
            return response()->json(["message" => "Cette galerie ne vous appartient pas."], 403);
        }
        if ($user instanceof ResponsableRegional && $galerieRestaurant->restaurant?->id_responsable !== $user->id) {
            return response()->json(["message" => "Cette galerie ne vous appartient pas."], 403);
        }

        if ($galerieRestaurant->url_fichier && Storage::disk("public")->exists($galerieRestaurant->url_fichier)) {
            Storage::disk("public")->delete($galerieRestaurant->url_fichier);
        }

        $galerieRestaurant->delete();
        return response()->json(["message" => "Média supprimé"], 200);
    }
}
