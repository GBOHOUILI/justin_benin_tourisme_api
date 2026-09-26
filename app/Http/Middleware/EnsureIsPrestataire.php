<?php

namespace App\Http\Middleware;

use App\Models\Prestataire;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

// Doit s'exécuter après le middleware auth:prestataire - même pattern que EnsureIsAdmin.
class EnsureIsPrestataire
{
    public function handle(Request $request, Closure $next): Response
    {
        $prestataire = $request->user();

        if (!$prestataire || !($prestataire instanceof Prestataire)) {
            return response()->json(
                ["message" => "Accès réservé aux prestataires."],
                403,
            );
        }

        if (!$prestataire->status) {
            return response()->json(
                ["message" => "Ce compte prestataire est désactivé."],
                403,
            );
        }

        return $next($request);
    }
}
