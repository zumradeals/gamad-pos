<?php

use App\Http\Controllers\Abonnements\AbonnementController;
use App\Http\Controllers\Appareils\AppareilMemoriseController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\LogoutController;
use App\Http\Controllers\Clotures\ClotureController;
use App\Http\Controllers\Creances\VersementController;
use App\Http\Controllers\Export\ExportController;
use App\Http\Controllers\Fournisseurs\FournisseurController;
use App\Http\Controllers\Livraisons\LigneLivraisonController;
use App\Http\Controllers\Livraisons\LivraisonController;
use App\Http\Controllers\PointsDeVente\SelectionController;
use App\Http\Controllers\Produits\ProduitController;
use App\Http\Controllers\Stock\CorrectionStockController;
use App\Http\Controllers\Ventes\VenteController;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'welcome')->name('home');

Route::get('/health', function () {
    return response()->json([
        'status' => 'ok',
    ]);
})->name('health');

Route::get('/login', [LoginController::class, 'create'])->name('login');
Route::post('/login', [LoginController::class, 'store'])->name('login.store');

Route::middleware('auth')->group(function () {
    Route::post('/logout', LogoutController::class)->name('logout');

    Route::get('/points-de-vente/selection', [SelectionController::class, 'index'])->name('points-de-vente.selection');
    Route::post('/points-de-vente/selection', [SelectionController::class, 'store'])->name('points-de-vente.selection.store');

    Route::get('/appareils', [AppareilMemoriseController::class, 'index'])->name('appareils.index');
    Route::delete('/appareils/{appareil}', [AppareilMemoriseController::class, 'destroy'])->name('appareils.destroy');

    Route::post('/produits', [ProduitController::class, 'store'])->name('produits.store');

    Route::get('/ventes', [VenteController::class, 'create'])->name('ventes.create');
    Route::post('/ventes', [VenteController::class, 'store'])
        ->middleware('abonnement.actif')
        ->name('ventes.store');

    Route::post('/creances/{creance}/versements', [VersementController::class, 'store'])
        ->middleware('abonnement.actif')
        ->name('creances.versements.store');

    Route::get('/livraisons', [LivraisonController::class, 'index'])->name('livraisons.index');
    Route::patch('/livraisons/{livraison}/responsable', [LivraisonController::class, 'assignerResponsable'])->name('livraisons.responsable.update');
    Route::post('/livraisons/{livraison}/lignes', [LigneLivraisonController::class, 'store'])
        ->middleware('abonnement.actif')
        ->name('livraisons.lignes.store');

    Route::post('/corrections-stock', [CorrectionStockController::class, 'store'])
        ->middleware('abonnement.actif')
        ->name('corrections-stock.store');

    Route::post('/clotures/{cloture}/valider', [ClotureController::class, 'valider'])
        ->middleware('abonnement.actif')
        ->name('clotures.valider');

    Route::post('/abonnements/activer', [AbonnementController::class, 'activer'])->name('abonnements.activer');

    Route::get('/export', [ExportController::class, 'index'])->name('export.index');

    Route::get('/fournisseurs', [FournisseurController::class, 'index'])->name('fournisseurs.index');
    Route::post('/fournisseurs', [FournisseurController::class, 'store'])->name('fournisseurs.store');
    Route::patch('/fournisseurs/{fournisseur}', [FournisseurController::class, 'update'])->name('fournisseurs.update');
});
