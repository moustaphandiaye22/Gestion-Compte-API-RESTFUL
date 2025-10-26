<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});

Route::get('/api.json', function () {
    return response()->file(base_path('api.json'), ['Content-Type' => 'application/json']);
});

Route::get('/docs', function () {
    return '<!DOCTYPE html>
<html>
<head>
    <title>Redirecting...</title>
    <meta http-equiv="refresh" content="0; url=/docs/api">
</head>
<body>
    <p>Redirecting to API documentation...</p>
</body>
</html>';
});
