<?php

namespace App\Http\Middleware;

use App\Models\ResponsableRegional;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Vérifie que l'utilisateur authentifié est bien un Responsable régional.
 * À utiliser APRÈS le middleware auth:responsable - même pattern que
 * EnsureIsAdmin/EnsureIsPrestataire.
 */
class EnsureIsResponsable
{
    public function handle(Request $request, Closure $next): Response
    {
        $responsable = $request->user();

        if (!$responsable || !($responsable instanceof ResponsableRegional)) {
            return response()->json(
                ["message" => "Accès réservé aux responsables régionaux."],
                403,
            );
        }

        if (!$responsable->status) {
            return response()->json(
                ["message" => "Ce compte responsable régional est désactivé."],
                403,
            );
        }

        return $next($request);
    }
}
