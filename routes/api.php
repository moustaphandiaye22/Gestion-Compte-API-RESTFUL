<?php

use App\Http\Controllers\CompteController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
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


// Routes d'authentification (sans authentification requise)
Route::prefix('v1')->middleware(['rating', 'logging'])->group(function () {
    Route::post('auth/login', [AuthController::class, 'login'])->name('auth.login');
    Route::post('auth/refresh', [AuthController::class, 'refresh'])->middleware(['auth:api', 'auth.api'])->name('auth.refresh');
    Route::post('auth/logout', [AuthController::class, 'logout'])->middleware(['auth:api', 'auth.api'])->name('auth.logout');
    Route::get('auth/user', [AuthController::class, 'user'])->middleware(['auth:api', 'auth.api'])->name('auth.user');
});

// Protected routes for comptes (index, store, show, archives, search)
Route::prefix('v1')->middleware(['auth:api', 'rating', 'logging'])->group(function () {
    Route::get('comptes', [CompteController::class, 'index'])->name('comptes.index');
    Route::post('comptes', [CompteController::class, 'store'])->name('comptes.store');
    Route::get('comptes/{compte}', [CompteController::class, 'show'])->name('comptes.show');

    // Routes pour les transactions et statistiques d'un compte
    Route::get('comptes/{compteId}/transactions', [CompteController::class, 'getTransactions'])->name('comptes.transactions');
    Route::get('comptes/{compteId}/statistiques', [CompteController::class, 'getStatistiques'])->name('comptes.statistiques');

    // Route spécifique pour les comptes archivés (admin seulement)
    Route::get('comptes-archives', [CompteController::class, 'archives'])->middleware('role:admin')->name('comptes.archives');

    // Routes de recherche de comptes
    Route::get('comptes/recherche/{numero}', [CompteController::class, 'rechercheParNumero'])->name('comptes.recherche.numero');
    Route::get('comptes/recherche/cni/{cni}', [CompteController::class, 'rechercheParCni'])->name('comptes.recherche.cni');
});

// Protected routes for comptes (admin actions)
Route::prefix('v1')->middleware(['auth:api', 'rating', 'logging'])->group(function () {
    Route::patch('comptes/{compte}', [CompteController::class, 'update'])->name('comptes.update');
    Route::delete('comptes/{compte}', [CompteController::class, 'destroy'])->name('comptes.destroy');

    // Route pour bloquer un compte (admin seulement)
    Route::post('comptes/{compteId}/bloquer', [CompteController::class, 'bloquer'])
            ->middleware('role:admin')
            ->name('comptes.bloquer');
});

// Dashboard routes (admin and client access)
Route::prefix('v1')->middleware(['auth:api', 'rating', 'logging'])->group(function () {
    Route::get('dashboard', [DashboardController::class, 'index'])->name('dashboard.index');
});
