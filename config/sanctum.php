<?php

use Laravel\Sanctum\Sanctum;

return [
    /*
    |--------------------------------------------------------------------------
    | Stateful Domains
    |--------------------------------------------------------------------------
    */
    "stateful" => explode(
        ",",
        env(
            "SANCTUM_STATEFUL_DOMAINS",
            sprintf(
                "%s,%s", // Ajout d'une virgule ici pour séparer les domaines
                "localhost,localhost:3000,127.0.0.1,127.0.0.1:8000,::1",
                Sanctum::currentApplicationUrlWithPort(),
            ),
        ),
    ),

    /*
    |--------------------------------------------------------------------------
    | Sanctum Guards
    |--------------------------------------------------------------------------
    */

    // Si tu as des admins et des users, il est souvent préférable de laisser
    // Sanctum gérer via le guard 'web' par défaut, mais assure-toi que tes
    // providers sont bien définis dans config/auth.php.
    "guard" => ["web"],

    /*
    |--------------------------------------------------------------------------
    | Expiration Minutes
    |--------------------------------------------------------------------------
    */

    "expiration" => null, // Les tokens n'expirent jamais par défaut (Bearer tokens)

    /*
    |--------------------------------------------------------------------------
    | Token Prefix
    |--------------------------------------------------------------------------
    */

    "token_prefix" => env("SANCTUM_TOKEN_PREFIX", ""),

    /*
    |--------------------------------------------------------------------------
    | Sanctum Middleware
    |--------------------------------------------------------------------------
    */

    "middleware" => [
        "authenticate_session" =>
            Laravel\Sanctum\Http\Middleware\AuthenticateSession::class,
        "encrypt_cookies" => Illuminate\Cookie\Middleware\EncryptCookies::class,
        "validate_csrf_token" =>
            Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class,
    ],
];
