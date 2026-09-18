<?php

use App\Models\User;
use App\Models\Admin;
use App\Models\Prestataire;
use App\Models\ResponsableRegional;

return [
    "defaults" => [
        "guard" => env("AUTH_GUARD", "web"),
        "passwords" => env("AUTH_PASSWORD_BROKER", "users"),
    ],

    "guards" => [
        "web" => [
            "driver" => "session",
            "provider" => "users",
        ],

        // Guard pour les utilisateurs (API publique)
        "sanctum" => [
            "driver" => "sanctum",
            "provider" => "users",
        ],

        // Guard séparé pour les administrateurs
        "admin" => [
            "driver" => "sanctum",
            "provider" => "admins",
        ],

        // Guard séparé pour les prestataires (portail SaaS)
        "prestataire" => [
            "driver" => "sanctum",
            "provider" => "prestataires",
        ],

        // Guard séparé pour les responsables régionaux (validation territoriale)
        "responsable" => [
            "driver" => "sanctum",
            "provider" => "responsables",
        ],
    ],

    "providers" => [
        "users" => [
            "driver" => "eloquent",
            "model" => User::class,
        ],

        "admins" => [
            "driver" => "eloquent",
            "model" => Admin::class,
        ],

        "prestataires" => [
            "driver" => "eloquent",
            "model" => Prestataire::class,
        ],

        "responsables" => [
            "driver" => "eloquent",
            "model" => ResponsableRegional::class,
        ],
    ],

    "passwords" => [
        "users" => [
            "provider" => "users",
            "table" => env(
                "AUTH_PASSWORD_RESET_TOKEN_TABLE",
                "password_reset_tokens",
            ),
            "expire" => 60,
            "throttle" => 60,
        ],

        "admins" => [
            "provider" => "admins",
            "table" => env(
                "AUTH_PASSWORD_RESET_TOKEN_TABLE",
                "password_reset_tokens",
            ),
            "expire" => 60,
            "throttle" => 60,
        ],
    ],

    "password_timeout" => env("AUTH_PASSWORD_TIMEOUT", 10800),
];
