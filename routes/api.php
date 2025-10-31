<?php

use App\Http\Controllers\CompteController;
use App\Http\Controllers\AuthController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});


// Routes d'authentification (sans authentification requise)
Route::prefix('v1')->middleware(['rating', 'logging'])->group(function () {
    Route::post('auth/login', [AuthController::class, 'login'])->name('auth.login');
    Route::post('auth/refresh', [AuthController::class, 'refresh'])->middleware(['auth:api', 'auth.api'])->name('auth.refresh');
    Route::post('auth/logout', [AuthController::class, 'logout'])->middleware(['auth:api', 'auth.api'])->name('auth.logout');
    Route::get('auth/user', [AuthController::class, 'user'])->middleware(['auth:api', 'auth.api'])->name('auth.user');
});

// Routes API version 1 (avec authentification)
Route::prefix('v1')->middleware(['auth:api', 'rating', 'logging'])->group(function () {

    /**
      * Routes pour les comptes
      */
    Route::apiResource('comptes', CompteController::class)->parameters([
          'comptes' => 'compte'
      ]);

    // Route spécifique pour les comptes archivés (cloud pour épargne)
    Route::get('comptes-archives', [CompteController::class, 'archives'])
            ->name('comptes.archives');

    // Route pour bloquer un compte (admin seulement)
    Route::post('comptes/{compteId}/bloquer', [CompteController::class, 'bloquer'])
            ->middleware('role:admin')
            ->name('comptes.bloquer');

    // Routes de recherche de comptes
    Route::get('comptes/recherche/{numero}', [CompteController::class, 'rechercheParNumero'])
            ->name('comptes.recherche.numero');

    Route::get('comptes/recherche/cni/{cni}', [CompteController::class, 'rechercheParCni'])
            ->name('comptes.recherche.cni');

});
