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

// ══════════════════════════════════════════════════════
//  ROUTES PUBLIQUES — aucun token requis
// ══════════════════════════════════════════════════════

Route::post("/register", [AuthController::class, "register"]);
Route::post("/login", [AuthController::class, "login"]);
Route::post("/admin/login", [AuthController::class, "loginAdmin"]);

// Consultation publique
Route::get("/sites", [SiteController::class, "index"]);
Route::get("/sites/{site}", [SiteController::class, "show"]);

Route::get("/evenements", [EvenementController::class, "index"]);
Route::get("/evenements/{evenement}", [EvenementController::class, "show"]);

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

// ══════════════════════════════════════════════════════
//  ROUTES UTILISATEURS — token User (auth:sanctum)
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
});

// ══════════════════════════════════════════════════════
//  ROUTES ADMIN — token Admin uniquement (auth:admin)
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
        Route::post("/sites", [SiteController::class, "store"]);
        Route::put("/sites/{site}", [SiteController::class, "update"]);
        Route::delete("/sites/{site}", [SiteController::class, "destroy"]);

        // Événements
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
    });
