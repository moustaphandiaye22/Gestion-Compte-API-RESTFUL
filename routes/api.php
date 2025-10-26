<?php

use App\Http\Controllers\CompteController;
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


// Routes API version 1
Route::prefix('v1')->group(function () {

    /**
      * Routes pour les comptes
      */
    Route::apiResource('comptes', CompteController::class)->parameters([
          'comptes' => 'compte'
      ]);

    // Route spécifique pour les comptes archivés (cloud pour épargne)
    Route::get('comptes-archives', [CompteController::class, 'archives'])
           ->name('comptes.archives');
});
