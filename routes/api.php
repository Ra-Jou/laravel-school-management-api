<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\StudentController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Ces routes sont automatiquement préfixées par "/api"
| et chargées par RouteServiceProvider.
|
*/

Route::middleware('api')->group(function () {
	Route::get('/test', function () {
		return response()->json(['message' => 'API is working!']);
	});

	// Routes publiques (pas besoin d'auth)
	Route::prefix('auth')->group(function () {
		Route::post('login', [AuthController::class, 'login']);
	});

	// Routes protégées
	Route::middleware('auth:api')->group(function () {
		Route::post('logout', [AuthController::class, 'logout']);
		Route::get('me', [AuthController::class, 'me']);

		Route::apiResource('students', StudentController::class);
		// Ajoute d'autres ressources ici
	});
});
