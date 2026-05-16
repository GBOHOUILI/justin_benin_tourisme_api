<?php

namespace App\Http\Middleware;

use App\Models\Admin;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Vérifie que l'utilisateur authentifié est bien un Admin.
 * À utiliser APRÈS le middleware auth:sanctum ou auth:admin.
 *
 * Usage dans les routes :
 *   Route::middleware(['auth:sanctum', 'admin'])->group(...)
 *
 * Ou directement avec le guard admin :
 *   Route::middleware('auth:admin')->group(...)
 */
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


