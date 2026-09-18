<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . "/../routes/web.php",
        api: __DIR__ . "/../routes/api.php",
        commands: __DIR__ . "/../routes/console.php",
        health: "/up",
    )
    ->withMiddleware(function (Middleware $middleware) {
        // EnsureFrontendRequestsAreStateful supprimé — cause une récursion
        // infinie dans Sanctum Guard pour une API Bearer token pure

        // Alias utilisables dans les routes : 'admin', 'prestataire', 'responsable'
        $middleware->alias([
            "admin" => \App\Http\Middleware\EnsureIsAdmin::class,
            "prestataire" => \App\Http\Middleware\EnsureIsPrestataire::class,
            "responsable" => \App\Http\Middleware\EnsureIsResponsable::class,
        ]);

        // API pure, aucune route web "login" : sans ce override, le framework
        // branche par défaut redirectGuestsTo(fn () => route('login')) (voir
        // ApplicationBuilder::withMiddleware) - toute requête non authentifiée
        // qui n'envoie pas Accept: application/json (Swagger "Try it out",
        // Postman, curl brut...) plante alors en 500 (RouteNotFoundException,
        // "Route [login] not defined") au lieu d'un 401 propre.
        $middleware->redirectGuestsTo(fn () => null);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })
    ->create();