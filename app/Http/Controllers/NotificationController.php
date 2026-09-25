<?php

namespace App\Http\Controllers;

use App\Models\Admin;
use App\Models\Notification;
use App\Models\Prestataire;
use App\Models\ResponsableRegional;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

/**
 * Un seul contrôleur, monté sous les 4 guards (sanctum/admin/prestataire/
 * responsable, cf. routes/api.php) - le type de compte se déduit de la
 * classe du modèle authentifié, jamais d'un paramètre fourni par le client.
 */
class NotificationController extends Controller
{
    private function resoudreDestinataire(Request $request): array
    {
        $compte = $request->user();

        return match (true) {
            $compte instanceof Admin => ['admin', $compte->id],
            $compte instanceof Prestataire => ['prestataire', $compte->id],
            $compte instanceof ResponsableRegional => ['responsable', $compte->id],
            default => ['user', $compte->id],
        };
    }

    #[
        OA\Get(
            path: "/api/mes-notifications",
            tags: ["Notifications"],
            summary: "Lister mes notifications (le type de compte se déduit du guard connecté)",
            security: [["bearerAuth" => []]],
            responses: [new OA\Response(response: 200, description: "Notifications paginées, les plus récentes en premier")],
        ),
    ]
    public function index(Request $request)
    {
        [$type, $id] = $this->resoudreDestinataire($request);

        return response()->json(
            Notification::where('type_destinataire', $type)
                ->where('id_destinataire', $id)
                ->latest()
                ->paginate(20),
        );
    }

    #[
        OA\Get(
            path: "/api/mes-notifications/non-lues",
            tags: ["Notifications"],
            summary: "Nombre de notifications non lues (pour un badge)",
            security: [["bearerAuth" => []]],
            responses: [new OA\Response(response: 200, description: "{ nombre }")],
        ),
    ]
    public function nonLues(Request $request)
    {
        [$type, $id] = $this->resoudreDestinataire($request);

        $nombre = Notification::where('type_destinataire', $type)
            ->where('id_destinataire', $id)
            ->where('lu', false)
            ->count();

        return response()->json(['nombre' => $nombre]);
    }

    #[
        OA\Patch(
            path: "/api/mes-notifications/{id}/lu",
            tags: ["Notifications"],
            summary: "Marquer une notification comme lue",
            security: [["bearerAuth" => []]],
            parameters: [new OA\Parameter(name: "id", in: "path", required: true, schema: new OA\Schema(type: "integer"))],
            responses: [
                new OA\Response(response: 200, description: "Notification marquée comme lue"),
                new OA\Response(response: 403, description: "Cette notification ne vous appartient pas"),
            ],
        ),
    ]
    public function marquerLu(Request $request, Notification $notification)
    {
        [$type, $id] = $this->resoudreDestinataire($request);

        if ($notification->type_destinataire !== $type || $notification->id_destinataire !== $id) {
            return response()->json(['message' => 'Accès refusé.'], 403);
        }

        $notification->update(['lu' => true]);

        return response()->json($notification);
    }

    #[
        OA\Patch(
            path: "/api/mes-notifications/tout-lire",
            tags: ["Notifications"],
            summary: "Marquer toutes mes notifications comme lues",
            security: [["bearerAuth" => []]],
            responses: [new OA\Response(response: 200, description: "Notifications marquées comme lues")],
        ),
    ]
    public function marquerToutesLues(Request $request)
    {
        [$type, $id] = $this->resoudreDestinataire($request);

        Notification::where('type_destinataire', $type)
            ->where('id_destinataire', $id)
            ->where('lu', false)
            ->update(['lu' => true]);

        return response()->json(['message' => 'Toutes les notifications ont été marquées comme lues.']);
    }
}
