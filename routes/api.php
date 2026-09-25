<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\CatSiteController;
use App\Http\Controllers\CatEvenmtController;
use App\Http\Controllers\SiteController;
use App\Http\Controllers\EvenementController;
use App\Http\Controllers\GalerieSiteController;
use App\Http\Controllers\GallerieEvnmtController;
use App\Http\Controllers\ReservationController;
use App\Http\Controllers\TicketController;
use App\Http\Controllers\UtilisationController;
use App\Http\Controllers\AvisController;
use App\Http\Controllers\FonctionnaliteController;
use App\Http\Controllers\PrixController;
use App\Http\Controllers\CommandeController;
use App\Http\Controllers\PaiementController;
use App\Http\Controllers\CircuitController;
use App\Http\Controllers\CircuitIaController;
use App\Http\Controllers\AssistantIaController;
use App\Http\Controllers\EtapeCircuitController;
use App\Http\Controllers\PrestataireController;
use App\Http\Controllers\RegionController;
use App\Http\Controllers\ResponsableRegionalController;
use App\Http\Controllers\HotelController;
use App\Http\Controllers\ChambreController;
use App\Http\Controllers\GalerieHotelController;
use App\Http\Controllers\RestaurantController;
use App\Http\Controllers\PlatController;
use App\Http\Controllers\GalerieRestaurantController;
use App\Http\Controllers\TransportController;
use App\Http\Controllers\TrajetController;
use App\Http\Controllers\GalerieTransportController;
use App\Http\Controllers\VilleController;
use App\Http\Controllers\PlanController;
use App\Http\Controllers\AbonnementController;
use App\Http\Controllers\TemoignageController;
use App\Http\Controllers\FavoriController;

// ══════════════════════════════════════════════════════
//  ROUTES PUBLIQUES - aucun token requis
// ══════════════════════════════════════════════════════

Route::post("/register", [AuthController::class, "register"]);
Route::post("/login", [AuthController::class, "login"]);
Route::post("/admin/login", [AuthController::class, "loginAdmin"]);
Route::post("/prestataire/register", [AuthController::class, "registerPrestataire"]);
Route::post("/prestataire/login", [AuthController::class, "loginPrestataire"]);
Route::post("/responsable/login", [AuthController::class, "loginResponsable"]);

Route::get("/regions", [RegionController::class, "index"]);
Route::get("/plans", [PlanController::class, "index"]);
Route::get("/temoignages", [TemoignageController::class, "index"]);

// Consultation publique
Route::get("/sites", [SiteController::class, "index"]);
Route::get("/sites/{site}", [SiteController::class, "show"]);

Route::get("/evenements", [EvenementController::class, "index"]);
Route::get("/evenements/{evenement}", [EvenementController::class, "show"]);

Route::get("/hotels", [HotelController::class, "index"]);
Route::get("/hotels/{hotel}", [HotelController::class, "show"]);
Route::get("/restaurants", [RestaurantController::class, "index"]);
Route::get("/restaurants/{restaurant}", [RestaurantController::class, "show"]);
Route::get("/transports", [TransportController::class, "index"]);
Route::get("/transports/{transport}", [TransportController::class, "show"]);
Route::get("/villes", [VilleController::class, "index"]);
Route::get("/villes/{ville}", [VilleController::class, "show"]);

// Circuit IA (module Circuit, public comme le reste de la composition manuelle)
Route::post("/circuits/generer-ia", [CircuitIaController::class, "generer"]);
Route::post("/assistant/chat", [AssistantIaController::class, "discuter"]);

Route::get("/chambres", [ChambreController::class, "index"]);
Route::get("/chambres/{chambre}", [ChambreController::class, "show"]);
Route::get("/plats", [PlatController::class, "index"]);
Route::get("/plats/{plat}", [PlatController::class, "show"]);
Route::get("/trajets", [TrajetController::class, "index"]);
Route::get("/trajets/{trajet}", [TrajetController::class, "show"]);

Route::get("/galeries/hotels", [GalerieHotelController::class, "index"]);
Route::get("/galeries/hotels/{galerieHotel}", [GalerieHotelController::class, "show"]);
Route::get("/galeries/restaurants", [GalerieRestaurantController::class, "index"]);
Route::get("/galeries/restaurants/{galerieRestaurant}", [GalerieRestaurantController::class, "show"]);
Route::get("/galeries/transports", [GalerieTransportController::class, "index"]);
Route::get("/galeries/transports/{galerieTransport}", [GalerieTransportController::class, "show"]);

Route::get("/categories/sites", [CatSiteController::class, "index"]);
Route::get("/categories/sites/{catSite}", [CatSiteController::class, "show"]);
Route::get("/categories/evenements", [CatEvenmtController::class, "index"]);
Route::get("/categories/evenements/{catEvenmt}", [
    CatEvenmtController::class,
    "show",
]);

Route::get("/prix", [PrixController::class, "index"]);
Route::get("/prix/{prix}", [PrixController::class, "show"]);

Route::get("/galeries/sites", [GalerieSiteController::class, "index"]);
Route::get("/galeries/sites/{galerieSite}", [
    GalerieSiteController::class,
    "show",
]);
Route::get("/galeries/evenements", [GallerieEvnmtController::class, "index"]);
Route::get("/galeries/evenements/{gallerieEvnmt}", [
    GallerieEvnmtController::class,
    "show",
]);

Route::get("/avis", [AvisController::class, "index"]);
Route::get("/avis/{avi}", [AvisController::class, "show"]);

Route::post("/tickets/verifier", [TicketController::class, "verifier"]);

// Webhook Kkiapay : pas de token Sanctum possible côté serveur-à-serveur,
// l'authenticité est vérifiée via le header x-kkiapay-secret (cf. PaiementController::webhook).
Route::post("/webhooks/kkiapay", [PaiementController::class, "webhook"]);
Route::post("/webhooks/kkiapay-abonnement", [AbonnementController::class, "webhook"]);

// ══════════════════════════════════════════════════════
//  ROUTES UTILISATEURS - token User (auth:sanctum)
// ══════════════════════════════════════════════════════
Route::middleware("auth:sanctum")->group(function () {
    Route::get("/me", [AuthController::class, "me"]);
    Route::post("/logout", [AuthController::class, "logout"]);
    Route::post("/update-password", [AuthController::class, "updatePassword"]);

    // Profil user
    Route::get("/users/{user}", [UserController::class, "show"]);
    Route::put("/users/{user}", [UserController::class, "update"]);
    Route::delete("/users/{user}", [UserController::class, "destroy"]);

    // Réservations
    Route::apiResource("reservations", ReservationController::class);

    // Avis
    Route::post("/avis", [AvisController::class, "store"]);
    Route::put("/avis/{avi}", [AvisController::class, "update"]);
    Route::delete("/avis/{avi}", [AvisController::class, "destroy"]);

    // Commandes & paiements Kkiapay
    Route::get("/commandes", [CommandeController::class, "index"]);
    Route::post("/commandes", [CommandeController::class, "store"]);
    Route::get("/commandes/{commande}", [CommandeController::class, "show"]);
    Route::patch("/paiements/{paiement}/verifier", [PaiementController::class, "verifier"]);

    // Circuits (itinéraires personnalisés)
    Route::apiResource("circuits", CircuitController::class);
    Route::post("/circuits/{circuit}/etapes", [EtapeCircuitController::class, "store"]);
    Route::patch("/circuits/{circuit}/etapes/reordonner", [EtapeCircuitController::class, "reordonner"]);
    Route::put("/etapes/{etape}", [EtapeCircuitController::class, "update"]);
    Route::delete("/etapes/{etape}", [EtapeCircuitController::class, "destroy"]);

    // Favoris (site/evenement/hotel/restaurant/transport)
    Route::get("/favoris", [FavoriController::class, "index"]);
    Route::post("/favoris", [FavoriController::class, "store"]);
    Route::delete("/favoris/{favori}", [FavoriController::class, "destroy"]);
});

// ══════════════════════════════════════════════════════
//  ROUTES ADMIN - token Admin uniquement (auth:admin)
//  Le middleware 'admin' vérifie en plus que le compte
//  n'est pas désactivé (status = false).
// ══════════════════════════════════════════════════════
Route::middleware(["auth:admin", "admin"])
    ->prefix("admin")
    ->group(function () {
        // Profil admin connecté
        Route::get("/me", [AuthController::class, "me"]);
        Route::post("/logout", [AuthController::class, "logout"]);
        Route::post("/update-password", [
            AuthController::class,
            "updatePassword",
        ]);

        // Gestion des admins
        Route::apiResource("admins", AdminController::class);

        // Gestion des utilisateurs
        Route::get("/users", [UserController::class, "index"]);
        Route::post("/users", [UserController::class, "store"]);
        Route::delete("/users/{user}", [UserController::class, "destroy"]);

        // Catégories
        Route::post("/categories/sites", [CatSiteController::class, "store"]);
        Route::put("/categories/sites/{catSite}", [
            CatSiteController::class,
            "update",
        ]);
        Route::delete("/categories/sites/{catSite}", [
            CatSiteController::class,
            "destroy",
        ]);

        Route::post("/categories/evenements", [
            CatEvenmtController::class,
            "store",
        ]);
        Route::put("/categories/evenements/{catEvenmt}", [
            CatEvenmtController::class,
            "update",
        ]);
        Route::delete("/categories/evenements/{catEvenmt}", [
            CatEvenmtController::class,
            "destroy",
        ]);

        // Sites
        Route::get("/sites", [SiteController::class, "adminIndex"]);
        Route::post("/sites", [SiteController::class, "store"]);
        Route::put("/sites/{site}", [SiteController::class, "update"]);
        Route::delete("/sites/{site}", [SiteController::class, "destroy"]);
        Route::patch("/sites/{site}/valider", [SiteController::class, "valider"]);
        Route::patch("/sites/{site}/rejeter", [SiteController::class, "rejeter"]);

        // Événements
        Route::get("/evenements", [EvenementController::class, "adminIndex"]);
        Route::post("/evenements", [EvenementController::class, "store"]);
        Route::put("/evenements/{evenement}", [
            EvenementController::class,
            "update",
        ]);
        Route::delete("/evenements/{evenement}", [
            EvenementController::class,
            "destroy",
        ]);
        Route::patch("/evenements/{evenement}/valider", [
            EvenementController::class,
            "valider",
        ]);
        Route::patch("/evenements/{evenement}/rejeter", [
            EvenementController::class,
            "rejeter",
        ]);

        // Responsables régionaux (poste officiel - créé par un admin, pas d'auto-inscription)
        Route::apiResource("responsables", ResponsableRegionalController::class);

        // Villes (liste ouverte, gérée par l'admin - contrairement aux régions fixes/seedées)
        Route::post("/villes", [VilleController::class, "store"]);
        Route::put("/villes/{ville}", [VilleController::class, "update"]);
        Route::delete("/villes/{ville}", [VilleController::class, "destroy"]);

        // Plans d'abonnement SaaS + abonnements des prestataires (module Prestataire, étape 3)
        Route::post("/plans", [PlanController::class, "store"]);
        Route::put("/plans/{plan}", [PlanController::class, "update"]);
        Route::delete("/plans/{plan}", [PlanController::class, "destroy"]);
        Route::get("/abonnements", [AbonnementController::class, "adminIndex"]);

        // Hôtels
        Route::get("/hotels", [HotelController::class, "adminIndex"]);
        Route::post("/hotels", [HotelController::class, "store"]);
        Route::put("/hotels/{hotel}", [HotelController::class, "update"]);
        Route::delete("/hotels/{hotel}", [HotelController::class, "destroy"]);
        Route::patch("/hotels/{hotel}/valider", [HotelController::class, "valider"]);
        Route::patch("/hotels/{hotel}/rejeter", [HotelController::class, "rejeter"]);

        // Restaurants
        Route::get("/restaurants", [RestaurantController::class, "adminIndex"]);
        Route::post("/restaurants", [RestaurantController::class, "store"]);
        Route::put("/restaurants/{restaurant}", [RestaurantController::class, "update"]);
        Route::delete("/restaurants/{restaurant}", [RestaurantController::class, "destroy"]);
        Route::patch("/restaurants/{restaurant}/valider", [RestaurantController::class, "valider"]);
        Route::patch("/restaurants/{restaurant}/rejeter", [RestaurantController::class, "rejeter"]);

        // Transports
        Route::get("/transports", [TransportController::class, "adminIndex"]);
        Route::post("/transports", [TransportController::class, "store"]);
        Route::put("/transports/{transport}", [TransportController::class, "update"]);
        Route::delete("/transports/{transport}", [TransportController::class, "destroy"]);
        Route::patch("/transports/{transport}/valider", [TransportController::class, "valider"]);
        Route::patch("/transports/{transport}/rejeter", [TransportController::class, "rejeter"]);

        // Chambres, plats, trajets (sous-entités)
        Route::post("/chambres", [ChambreController::class, "store"]);
        Route::put("/chambres/{chambre}", [ChambreController::class, "update"]);
        Route::delete("/chambres/{chambre}", [ChambreController::class, "destroy"]);

        Route::post("/plats", [PlatController::class, "store"]);
        Route::put("/plats/{plat}", [PlatController::class, "update"]);
        Route::delete("/plats/{plat}", [PlatController::class, "destroy"]);

        Route::post("/trajets", [TrajetController::class, "store"]);
        Route::put("/trajets/{trajet}", [TrajetController::class, "update"]);
        Route::delete("/trajets/{trajet}", [TrajetController::class, "destroy"]);

        // Galeries
        Route::post("/galeries/sites", [GalerieSiteController::class, "store"]);
        Route::put("/galeries/sites/{galerieSite}", [
            GalerieSiteController::class,
            "update",
        ]);
        Route::delete("/galeries/sites/{galerieSite}", [
            GalerieSiteController::class,
            "destroy",
        ]);

        Route::post("/galeries/evenements", [
            GallerieEvnmtController::class,
            "store",
        ]);
        Route::put("/galeries/evenements/{gallerieEvnmt}", [
            GallerieEvnmtController::class,
            "update",
        ]);
        Route::delete("/galeries/evenements/{gallerieEvnmt}", [
            GallerieEvnmtController::class,
            "destroy",
        ]);

        Route::post("/galeries/hotels", [GalerieHotelController::class, "store"]);
        Route::put("/galeries/hotels/{galerieHotel}", [GalerieHotelController::class, "update"]);
        Route::delete("/galeries/hotels/{galerieHotel}", [GalerieHotelController::class, "destroy"]);

        Route::post("/galeries/restaurants", [GalerieRestaurantController::class, "store"]);
        Route::put("/galeries/restaurants/{galerieRestaurant}", [GalerieRestaurantController::class, "update"]);
        Route::delete("/galeries/restaurants/{galerieRestaurant}", [GalerieRestaurantController::class, "destroy"]);

        Route::post("/galeries/transports", [GalerieTransportController::class, "store"]);
        Route::put("/galeries/transports/{galerieTransport}", [GalerieTransportController::class, "update"]);
        Route::delete("/galeries/transports/{galerieTransport}", [GalerieTransportController::class, "destroy"]);

        // Prix
        Route::post("/prix", [PrixController::class, "store"]);
        Route::put("/prix/{prix}", [PrixController::class, "update"]);
        Route::delete("/prix/{prix}", [PrixController::class, "destroy"]);

        // Tickets & Utilisations
        Route::apiResource("tickets", TicketController::class);
        Route::apiResource("utilisations", UtilisationController::class);

        // Modération des avis
        Route::patch("/avis/{avi}/approuver", [
            AvisController::class,
            "approuver",
        ]);
        Route::patch("/avis/{avi}/rejeter", [AvisController::class, "rejeter"]);

        // Fonctionnalités & permissions
        Route::apiResource("fonctionnalites", FonctionnaliteController::class);
        Route::post("/fonctionnalites/{fonctionnalite}/assigner-admin", [
            FonctionnaliteController::class,
            "assignerAdmin",
        ]);
        Route::post("/fonctionnalites/{fonctionnalite}/assigner-user", [
            FonctionnaliteController::class,
            "assignerUser",
        ]);

        // Témoignages plateforme (Chantier 3 - section "Ce que pensent nos utilisateurs" sur l'Accueil)
        Route::get("/temoignages", [TemoignageController::class, "adminIndex"]);
        Route::post("/temoignages", [TemoignageController::class, "store"]);
        Route::put("/temoignages/{temoignage}", [TemoignageController::class, "update"]);
        Route::delete("/temoignages/{temoignage}", [TemoignageController::class, "destroy"]);
    });

// ══════════════════════════════════════════════════════
//  ROUTES PRESTATAIRE - token Prestataire (auth:prestataire)
//  Portail SaaS : gère uniquement ses propres fiches (Site/
//  Evenement/Prix/Galerie), jamais celles d'un autre prestataire.
// ══════════════════════════════════════════════════════
Route::middleware(["auth:prestataire", "prestataire"])
    ->prefix("prestataire")
    ->group(function () {
        Route::get("/me", [AuthController::class, "me"]);
        Route::post("/logout", [AuthController::class, "logout"]);
        Route::post("/update-password", [AuthController::class, "updatePassword"]);
        Route::put("/profil", [PrestataireController::class, "updateProfil"]);
        Route::get("/dashboard", [PrestataireController::class, "dashboard"]);

        // Abonnement SaaS (module Prestataire, étape 3)
        Route::get("/abonnement", [AbonnementController::class, "statut"]);
        Route::post("/abonnements", [AbonnementController::class, "souscrire"]);
        Route::patch("/factures-abonnement/{factureAbonnement}/verifier", [AbonnementController::class, "verifier"]);

        // Mes sites
        Route::get("/sites", [SiteController::class, "mine"]);
        Route::post("/sites", [SiteController::class, "store"]);
        Route::put("/sites/{site}", [SiteController::class, "update"]);
        Route::delete("/sites/{site}", [SiteController::class, "destroy"]);

        // Mes événements
        Route::get("/evenements", [EvenementController::class, "mine"]);
        Route::post("/evenements", [EvenementController::class, "store"]);
        Route::put("/evenements/{evenement}", [EvenementController::class, "update"]);
        Route::delete("/evenements/{evenement}", [EvenementController::class, "destroy"]);

        // Mes hôtels
        Route::get("/hotels", [HotelController::class, "mine"]);
        Route::post("/hotels", [HotelController::class, "store"]);
        Route::put("/hotels/{hotel}", [HotelController::class, "update"]);
        Route::delete("/hotels/{hotel}", [HotelController::class, "destroy"]);

        // Mes restaurants
        Route::get("/restaurants", [RestaurantController::class, "mine"]);
        Route::post("/restaurants", [RestaurantController::class, "store"]);
        Route::put("/restaurants/{restaurant}", [RestaurantController::class, "update"]);
        Route::delete("/restaurants/{restaurant}", [RestaurantController::class, "destroy"]);

        // Mes transports
        Route::get("/transports", [TransportController::class, "mine"]);
        Route::post("/transports", [TransportController::class, "store"]);
        Route::put("/transports/{transport}", [TransportController::class, "update"]);
        Route::delete("/transports/{transport}", [TransportController::class, "destroy"]);

        // Chambres, plats, trajets de mes fiches (ownership vérifié dans les controllers)
        Route::post("/chambres", [ChambreController::class, "store"]);
        Route::put("/chambres/{chambre}", [ChambreController::class, "update"]);
        Route::delete("/chambres/{chambre}", [ChambreController::class, "destroy"]);

        Route::post("/plats", [PlatController::class, "store"]);
        Route::put("/plats/{plat}", [PlatController::class, "update"]);
        Route::delete("/plats/{plat}", [PlatController::class, "destroy"]);

        Route::post("/trajets", [TrajetController::class, "store"]);
        Route::put("/trajets/{trajet}", [TrajetController::class, "update"]);
        Route::delete("/trajets/{trajet}", [TrajetController::class, "destroy"]);

        // Tarifs et galeries de mes fiches (ownership vérifié dans les controllers)
        Route::post("/prix", [PrixController::class, "store"]);
        Route::put("/prix/{prix}", [PrixController::class, "update"]);
        Route::delete("/prix/{prix}", [PrixController::class, "destroy"]);

        Route::post("/galeries/sites", [GalerieSiteController::class, "store"]);
        Route::put("/galeries/sites/{galerieSite}", [GalerieSiteController::class, "update"]);
        Route::delete("/galeries/sites/{galerieSite}", [GalerieSiteController::class, "destroy"]);

        Route::post("/galeries/evenements", [GallerieEvnmtController::class, "store"]);
        Route::put("/galeries/evenements/{gallerieEvnmt}", [GallerieEvnmtController::class, "update"]);
        Route::delete("/galeries/evenements/{gallerieEvnmt}", [GallerieEvnmtController::class, "destroy"]);

        Route::post("/galeries/hotels", [GalerieHotelController::class, "store"]);
        Route::put("/galeries/hotels/{galerieHotel}", [GalerieHotelController::class, "update"]);
        Route::delete("/galeries/hotels/{galerieHotel}", [GalerieHotelController::class, "destroy"]);

        Route::post("/galeries/restaurants", [GalerieRestaurantController::class, "store"]);
        Route::put("/galeries/restaurants/{galerieRestaurant}", [GalerieRestaurantController::class, "update"]);
        Route::delete("/galeries/restaurants/{galerieRestaurant}", [GalerieRestaurantController::class, "destroy"]);

        Route::post("/galeries/transports", [GalerieTransportController::class, "store"]);
        Route::put("/galeries/transports/{galerieTransport}", [GalerieTransportController::class, "update"]);
        Route::delete("/galeries/transports/{galerieTransport}", [GalerieTransportController::class, "destroy"]);
    });

// ══════════════════════════════════════════════════════
//  ROUTES RESPONSABLE RÉGIONAL - token Responsable (auth:responsable)
//  Valide/rejette les Site/Evenement de sa région (ou de toutes les
//  régions si id_region est NULL - responsable "global"). Peut aussi
//  créer ses propres fiches (il connaît son territoire) - jamais
//  auto-validées, seul un Admin les valide (cf. refuserSiHorsPerimetre).
// ══════════════════════════════════════════════════════
Route::middleware(["auth:responsable", "responsable"])
    ->prefix("responsable")
    ->group(function () {
        Route::get("/me", [AuthController::class, "me"]);
        Route::post("/logout", [AuthController::class, "logout"]);
        Route::post("/update-password", [AuthController::class, "updatePassword"]);

        Route::get("/a-valider", [ResponsableRegionalController::class, "aValider"]);

        Route::patch("/sites/{site}/valider", [SiteController::class, "valider"]);
        Route::patch("/sites/{site}/rejeter", [SiteController::class, "rejeter"]);
        Route::patch("/evenements/{evenement}/valider", [EvenementController::class, "valider"]);
        Route::patch("/evenements/{evenement}/rejeter", [EvenementController::class, "rejeter"]);
        Route::patch("/hotels/{hotel}/valider", [HotelController::class, "valider"]);
        Route::patch("/hotels/{hotel}/rejeter", [HotelController::class, "rejeter"]);
        Route::patch("/restaurants/{restaurant}/valider", [RestaurantController::class, "valider"]);
        Route::patch("/restaurants/{restaurant}/rejeter", [RestaurantController::class, "rejeter"]);
        Route::patch("/transports/{transport}/valider", [TransportController::class, "valider"]);
        Route::patch("/transports/{transport}/rejeter", [TransportController::class, "rejeter"]);

        // Mes sites
        Route::get("/sites", [SiteController::class, "mine"]);
        Route::post("/sites", [SiteController::class, "store"]);
        Route::put("/sites/{site}", [SiteController::class, "update"]);
        Route::delete("/sites/{site}", [SiteController::class, "destroy"]);

        // Mes événements
        Route::get("/evenements", [EvenementController::class, "mine"]);
        Route::post("/evenements", [EvenementController::class, "store"]);
        Route::put("/evenements/{evenement}", [EvenementController::class, "update"]);
        Route::delete("/evenements/{evenement}", [EvenementController::class, "destroy"]);

        // Mes hôtels
        Route::get("/hotels", [HotelController::class, "mine"]);
        Route::post("/hotels", [HotelController::class, "store"]);
        Route::put("/hotels/{hotel}", [HotelController::class, "update"]);
        Route::delete("/hotels/{hotel}", [HotelController::class, "destroy"]);

        // Mes restaurants
        Route::get("/restaurants", [RestaurantController::class, "mine"]);
        Route::post("/restaurants", [RestaurantController::class, "store"]);
        Route::put("/restaurants/{restaurant}", [RestaurantController::class, "update"]);
        Route::delete("/restaurants/{restaurant}", [RestaurantController::class, "destroy"]);

        // Mes transports
        Route::get("/transports", [TransportController::class, "mine"]);
        Route::post("/transports", [TransportController::class, "store"]);
        Route::put("/transports/{transport}", [TransportController::class, "update"]);
        Route::delete("/transports/{transport}", [TransportController::class, "destroy"]);

        // Chambres, plats, trajets de mes fiches (ownership vérifié dans les controllers)
        Route::post("/chambres", [ChambreController::class, "store"]);
        Route::put("/chambres/{chambre}", [ChambreController::class, "update"]);
        Route::delete("/chambres/{chambre}", [ChambreController::class, "destroy"]);

        Route::post("/plats", [PlatController::class, "store"]);
        Route::put("/plats/{plat}", [PlatController::class, "update"]);
        Route::delete("/plats/{plat}", [PlatController::class, "destroy"]);

        Route::post("/trajets", [TrajetController::class, "store"]);
        Route::put("/trajets/{trajet}", [TrajetController::class, "update"]);
        Route::delete("/trajets/{trajet}", [TrajetController::class, "destroy"]);

        // Tarifs et galeries de mes fiches (ownership vérifié dans les controllers)
        Route::post("/prix", [PrixController::class, "store"]);
        Route::put("/prix/{prix}", [PrixController::class, "update"]);
        Route::delete("/prix/{prix}", [PrixController::class, "destroy"]);

        Route::post("/galeries/sites", [GalerieSiteController::class, "store"]);
        Route::put("/galeries/sites/{galerieSite}", [GalerieSiteController::class, "update"]);
        Route::delete("/galeries/sites/{galerieSite}", [GalerieSiteController::class, "destroy"]);

        Route::post("/galeries/evenements", [GallerieEvnmtController::class, "store"]);
        Route::put("/galeries/evenements/{gallerieEvnmt}", [GallerieEvnmtController::class, "update"]);
        Route::delete("/galeries/evenements/{gallerieEvnmt}", [GallerieEvnmtController::class, "destroy"]);

        Route::post("/galeries/hotels", [GalerieHotelController::class, "store"]);
        Route::put("/galeries/hotels/{galerieHotel}", [GalerieHotelController::class, "update"]);
        Route::delete("/galeries/hotels/{galerieHotel}", [GalerieHotelController::class, "destroy"]);

        Route::post("/galeries/restaurants", [GalerieRestaurantController::class, "store"]);
        Route::put("/galeries/restaurants/{galerieRestaurant}", [GalerieRestaurantController::class, "update"]);
        Route::delete("/galeries/restaurants/{galerieRestaurant}", [GalerieRestaurantController::class, "destroy"]);

        Route::post("/galeries/transports", [GalerieTransportController::class, "store"]);
        Route::put("/galeries/transports/{galerieTransport}", [GalerieTransportController::class, "update"]);
        Route::delete("/galeries/transports/{galerieTransport}", [GalerieTransportController::class, "destroy"]);
    });
