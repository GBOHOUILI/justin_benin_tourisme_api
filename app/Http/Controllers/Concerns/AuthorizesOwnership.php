<?php

namespace App\Http\Controllers\Concerns;

use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\Request;

/**
 * Centralise le contrôle "cette ressource appartient-elle à l'utilisateur
 * connecté ?", dupliqué à la main dans ReservationController/UserController/
 * AvisController (chacun avec son propre if/return 403) - un seul endroit
 * à corriger si ce contrôle doit évoluer, ce qui évite la récidive du bug
 * IDOR déjà rencontré deux fois (UserController, AvisController).
 *
 * HttpResponseException (pas abort()) : abort() fait du content-negotiation
 * et rend une page HTML si la requête n'envoie pas "Accept: application/json"
 * (tout client qui n'est pas fetch/axios avec ses en-têtes par défaut) - or
 * le reste de l'API renvoie toujours du JSON sans condition. HttpResponseException
 * court-circuite le controller comme abort(), mais avec la réponse JSON exacte
 * qu'on lui donne, sans négociation.
 */
trait AuthorizesOwnership
{
    protected function authorizeOwner(int $ownerId, Request $request): void
    {
        if ($ownerId !== $request->user()->id) {
            throw new HttpResponseException(
                response()->json(['message' => 'Accès refusé.'], 403)
            );
        }
    }
}
