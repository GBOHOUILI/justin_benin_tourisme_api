<?php

namespace App\Http\Controllers;

use OpenApi\Attributes as OA;

#[
    OA\Info(
        title: "Benin Tourisme API",
        version: "1.0.0",
        description: "API de gestion et promotion des sites touristiques et événements culturels du Bénin",
        contact: new OA\Contact(email: "eldomoreogbohouili@gmail.com"),
    ),
]
#[OA\Server(url: L5_SWAGGER_CONST_HOST, description: "Serveur API local")]
#[
    OA\SecurityScheme(
        securityScheme: "bearerAuth",
        type: "http",
        scheme: "bearer",
        bearerFormat: "JWT",
    ),
]
#[OA\Tag(name: "Auth", description: "Authentification")]
#[OA\Tag(name: "Admins", description: "Administrateurs")]
#[OA\Tag(name: "Sites", description: "Sites touristiques")]
#[OA\Tag(name: "Evenements", description: "Événements culturels")]
#[OA\Tag(name: "Categories", description: "Catégories")]
#[OA\Tag(name: "Prix", description: "Tarifs")]
#[OA\Tag(name: "Galeries", description: "Médias galeries")]
#[OA\Tag(name: "Reservations", description: "Réservations")]
#[OA\Tag(name: "Tickets", description: "Tickets de visite")]
#[OA\Tag(name: "Utilisations", description: "Utilisations des tickets")]
#[OA\Tag(name: "Avis", description: "Avis et commentaires")]
#[OA\Tag(name: "Users", description: "Utilisateurs")]
#[OA\Tag(name: "Fonctionnalites", description: "Gestion des droits")]
#[OA\Tag(name: "Test", description: "Endpoints de diagnostic")]
class SwaggerController extends Controller
{
    #[
        OA\Get(
            path: "/api/health",
            summary: "Vérification de l'état de l'API",
            tags: ["Test"],
            responses: [
                new OA\Response(
                    response: 200,
                    description: "API en ligne",
                    content: new OA\JsonContent(
                        properties: [
                            new OA\Property(
                                property: "status",
                                type: "string",
                                example: "ok",
                            ),
                            new OA\Property(
                                property: "message",
                                type: "string",
                                example: "Benin Tourisme API is running",
                            ),
                        ],
                    ),
                ),
            ],
        ),
    ]
    public function health()
    {
        return response()->json([
            "status" => "ok",
            "message" => "Benin Tourisme API is running",
        ]);
    }

    #[
        OA\Get(
            path: "/api/test-swagger",
            summary: "Endpoint de test pour vérifier que Swagger détecte les opérations",
            tags: ["Test"],
            responses: [
                new OA\Response(
                    response: 200,
                    description: "API fonctionne correctement",
                    content: new OA\JsonContent(
                        properties: [
                            new OA\Property(
                                property: "message",
                                type: "string",
                                example: "Test Swagger OK",
                            ),
                        ],
                    ),
                ),
            ],
        ),
    ]
    public function index()
    {
        return response()->json([
            "message" =>
                "Test Swagger OK - Le scanner détecte bien les PathItems",
        ]);
    }
}
