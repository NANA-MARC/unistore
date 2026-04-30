<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\AvisController;
use App\Http\Controllers\BoutiqueController;
use App\Http\Controllers\CategorieController;
use App\Http\Controllers\CommandeController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\PanierController;
use App\Http\Controllers\PaiementController;
use App\Http\Controllers\ProduitController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
| Ici sont définies les routes de l'API UniStore.
*/

// Routes publiques d'authentification
Route::post('/auth/register', [AuthController::class, 'register']);
Route::post('/auth/login', [AuthController::class, 'login']);

// Routes protégées par Sanctum
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::get('/auth/profile', [AuthController::class, 'profile']);

    // Routes boutiques (vendeur uniquement)
    Route::post('/boutiques', [BoutiqueController::class, 'store'])->middleware('role:vendeur');
    Route::put('/boutiques/{id}', [BoutiqueController::class, 'update'])->middleware('role:vendeur');

    // Routes catégories (vendeur uniquement via la boutique)
    Route::post('/boutiques/{boutique_id}/categories', [CategorieController::class, 'store']);
    Route::put('/boutiques/{boutique_id}/categories/{id}', [CategorieController::class, 'update']);
    Route::delete('/boutiques/{boutique_id}/categories/{id}', [CategorieController::class, 'destroy']);

    // Routes produits (vendeur uniquement)
    Route::get('/produits/mes-produits', [ProduitController::class, 'mesProduits'])->middleware('role:vendeur');
    Route::post('/produits', [ProduitController::class, 'store'])->middleware('role:vendeur');
    Route::put('/produits/{id}', [ProduitController::class, 'update'])->middleware('role:vendeur');
    Route::delete('/produits/{id}', [ProduitController::class, 'destroy'])->middleware('role:vendeur');

    // Routes boutiques (super_admin uniquement)
    Route::put('/boutiques/{id}/statut', [BoutiqueController::class, 'toggleStatut'])->middleware('role:super_admin');

    // Routes panier
    Route::get('/panier', [PanierController::class, 'index']);
    Route::post('/panier', [PanierController::class, 'store'])->middleware('role:client');
    Route::put('/panier/{id}', [PanierController::class, 'update']);
    Route::delete('/panier/{id}', [PanierController::class, 'destroy']);
    Route::delete('/panier', [PanierController::class, 'clear']);

    // Routes commandes client
    Route::post('/commandes', [CommandeController::class, 'store'])->middleware('role:client');
    Route::get('/commandes/mes-commandes', [CommandeController::class, 'mesCommandes'])->middleware('role:client');
    Route::put('/commandes/{id}/annuler', [CommandeController::class, 'annuler'])->middleware('role:client');

    // Routes commandes vendeur
    Route::get('/commandes/boutique', [CommandeController::class, 'commandesBoutique'])->middleware('role:vendeur');
    Route::put('/commandes/{id}/statut', [CommandeController::class, 'updateStatut'])->middleware('role:vendeur');

    // Détail commande (client ou vendeur)
    Route::get('/commandes/{id}', [CommandeController::class, 'show']);

    // Routes avis protégées
    Route::post('/produits/{produit_id}/avis', [AvisController::class, 'store'])->middleware('role:client');
    Route::put('/produits/{produit_id}/avis/{id}', [AvisController::class, 'update'])->middleware('role:client');
    Route::delete('/produits/{produit_id}/avis/{id}', [AvisController::class, 'destroy']);

    // Routes paiements
    Route::post('/commandes/{commande_id}/paiement', [PaiementController::class, 'store'])->middleware('role:client');
    Route::get('/commandes/{commande_id}/paiement', [PaiementController::class, 'show']);
    Route::put('/paiements/{id}/valider', [PaiementController::class, 'valider'])->middleware('role:vendeur');
    Route::put('/paiements/{id}/rembourser', [PaiementController::class, 'rembourser'])->middleware('role:vendeur');

    // Routes notifications
    Route::get('/notifications', [NotificationController::class, 'index']);
    Route::put('/notifications/{id}/lu', [NotificationController::class, 'marquerLu']);
    Route::put('/notifications/tout-lu', [NotificationController::class, 'marquerToutLu']);
    Route::delete('/notifications/{id}', [NotificationController::class, 'destroy']);
});

// Routes publiques boutiques, produits et avis
Route::get('/boutiques', [BoutiqueController::class, 'index']);
Route::get('/boutiques/{id}', [BoutiqueController::class, 'show']);
Route::get('/boutiques/{boutique_id}/categories', [CategorieController::class, 'index']);

Route::get('/produits', [ProduitController::class, 'index']);
Route::get('/produits/{id}', [ProduitController::class, 'show']);
Route::get('/produits/{produit_id}/avis', [AvisController::class, 'index']);
