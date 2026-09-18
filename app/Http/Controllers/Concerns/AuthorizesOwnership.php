<?php

namespace App\Http\Controllers\Concerns;

use Illuminate\Http\Request;

/**
 * Centralise le contrôle "cette ressource appartient-elle à l'utilisateur
 * connecté ?", dupliqué à la main dans ReservationController/UserController/
 * AvisController (chacun avec son propre if/return 403) - un seul endroit
 * à corriger si ce contrôle doit évoluer, ce qui évite la récidive du bug
 * IDOR déjà rencontré deux fois (UserController, AvisController).
 */
trait AuthorizesOwnership
{
    protected function authorizeOwner(int $ownerId, Request $request): void
    {
        if ($ownerId !== $request->user()->id) {
            abort(403, 'Accès refusé.');
        }
    }
}
