<?php

namespace App\Http\Middleware;

use App\Models\Admin;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

// Doit s'exécuter après un middleware de guard (auth:sanctum ou auth:admin).
class EnsureIsAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user || !($user instanceof Admin)) {
            return response()->json(
                [
                    "message" => "Accès réservé aux administrateurs.",
                ],
                403,
            );
        }

        if (!$user->status) {
            return response()->json(
                [
                    "message" => "Ce compte administrateur est désactivé.",
                ],
                403,
            );
        }

        return $next($request);
    }
}


