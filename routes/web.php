<?php

use App\Http\Controllers\Appareils\AppareilMemoriseController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\LogoutController;
use App\Http\Controllers\PointsDeVente\SelectionController;
use App\Http\Controllers\Produits\ProduitController;
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
    Route::post('/ventes', [VenteController::class, 'store'])->name('ventes.store');
});
