<?php

namespace App\Http\Controllers;

use App\Models\GalerieSite;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use OpenApi\Attributes as OA;

class GalerieSiteController extends Controller
{
    #[
        OA\Get(
            path: "/api/galeries/sites",
            tags: ["Galeries Sites"],
            summary: "Lister les galeries de sites",
            parameters: [
                new OA\Parameter(
                    name: "id_site",
                    in: "query",
                    schema: new OA\Schema(type: "integer"),
                ),
                new OA\Parameter(
                    name: "type",
                    in: "query",
                    schema: new OA\Schema(type: "string"),
                ),
                new OA\Parameter(
                    name: "status",
                    in: "query",
                    schema: new OA\Schema(type: "boolean"),
                ),
            ],
            responses: [
                new OA\Response(
                    response: 200,
                    description: "Liste des galeries de sites",
                ),
            ],
        ),
    ]
    public function index(Request $request)
    {
        $query = GalerieSite::with("site");

        if ($request->filled("id_site")) {
            $query->where("id_site", $request->id_site);
        }
        if ($request->filled("type")) {
            $query->where("type", $request->type);
        }
        if ($request->filled("status")) {
            $query->where("status", $request->status);
        }

        return response()->json($query->get());
    }

    #[
        OA\Post(
            path: "/api/admin/galeries/sites",
            tags: ["Galeries Sites"],
            summary: "Créer une entrée de galerie pour un site (admin)",
            security: [["bearerAuth" => []]],
            requestBody: new OA\RequestBody(
                content: [
                    new OA\MediaType(
                        mediaType: "multipart/form-data",
                        schema: new OA\Schema(
                            required: ["libelle", "type", "id_site"],
                            properties: [
                                new OA\Property(
                                    property: "libelle",
                                    type: "string",
                                    description: "Titre/description du média",
                                ),
                                new OA\Property(
                                    property: "type",
                                    type: "string",
                                    enum: ["image", "video"],
                                ),
                                new OA\Property(
                                    property: "status",
                                    type: "boolean",
                                ),
                                new OA\Property(
                                    property: "id_site",
                                    type: "integer",
                                ),
                                new OA\Property(
                                    property: "fichier",
                                    type: "string",
                                    format: "binary",
                                    description: "Fichier image ou vidéo",
                                ),
                            ],
                        ),
                    ),
                ],
            ),
            responses: [
                new OA\Response(response: 201, description: "Galerie créée"),
            ],
        ),
    ]
    public function store(Request $request)
    {
        $validated = $request->validate([
            "libelle" => "required|string|max:255",
            "type" => "required|string|in:image,video",
            "status" => "nullable|boolean",
            "id_site" => "required|exists:site,id",
            "fichier" =>
                "nullable|file|mimes:jpg,jpeg,png,gif,mp4,mov|max:51200",
        ]);

        // CORRECTION : le libelle (titre) est conservé tel quel.
        // Le chemin du fichier est stocké dans un champ séparé 'url_fichier'.
        if ($request->hasFile("fichier")) {
            $validated["url_fichier"] = $request
                ->file("fichier")
                ->store("galeries/sites", "public");
        }

        $galerie = GalerieSite::create($validated);

        return response()->json($galerie->load("site"), 201);
    }

    #[
        OA\Get(
            path: "/api/galeries/sites/{id}",
            tags: ["Galeries Sites"],
            summary: "Afficher une galerie de site",
            parameters: [
                new OA\Parameter(
                    name: "id",
                    in: "path",
                    required: true,
                    schema: new OA\Schema(type: "integer"),
                ),
            ],
            responses: [
                new OA\Response(response: 200, description: "Galerie de site"),
            ],
        ),
    ]
    public function show(GalerieSite $galerieSite)
    {
        return response()->json($galerieSite->load("site"));
    }

    #[
        OA\Put(
            path: "/api/admin/galeries/sites/{id}",
            tags: ["Galeries Sites"],
            summary: "Mettre à jour une galerie de site (admin)",
            security: [["bearerAuth" => []]],
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
                        new OA\Property(property: "libelle", type: "string"),
                        new OA\Property(property: "type", type: "string"),
                        new OA\Property(property: "status", type: "boolean"),
                        new OA\Property(property: "id_site", type: "integer"),
                    ],
                ),
            ),
            responses: [
                new OA\Response(
                    response: 200,
                    description: "Galerie mise à jour",
                ),
            ],
        ),
    ]
    public function update(Request $request, GalerieSite $galerieSite)
    {
        $validated = $request->validate([
            "libelle" => "sometimes|string|max:255",
            "type" => "sometimes|string|in:image,video",
            "status" => "nullable|boolean",
            "id_site" => "sometimes|exists:site,id",
        ]);

        $galerieSite->update($validated);

        return response()->json($galerieSite);
    }

    #[
        OA\Delete(
            path: "/api/admin/galeries/sites/{id}",
            tags: ["Galeries Sites"],
            summary: "Supprimer une galerie de site (admin)",
            security: [["bearerAuth" => []]],
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
                    description: "Galerie supprimée",
                ),
            ],
        ),
    ]
    public function destroy(GalerieSite $galerieSite)
    {
        // Suppression du fichier physique si présent
        if (
            $galerieSite->url_fichier &&
            Storage::disk("public")->exists($galerieSite->url_fichier)
        ) {
            Storage::disk("public")->delete($galerieSite->url_fichier);
        }

        $galerieSite->delete();

        return response()->json(["message" => "Média supprimé"], 200);
    }
}
