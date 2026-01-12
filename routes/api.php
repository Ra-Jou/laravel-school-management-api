<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\StudentController;
use App\Http\Controllers\TeacherController;
use App\Http\Controllers\UserController;

Route::prefix('auth')->group(function () {
	Route::post('login', [AuthController::class, 'login']);
});

// Routes protégées par JWT
Route::middleware('jwt.auth')->group(function () {
	Route::post('logout', [AuthController::class, 'logout']);
	Route::get('me', [AuthController::class, 'me']);

	Route::apiResource('users', UserController::class);
	Route::post('teachers/register', [TeacherController::class, 'registerTeacher']);
	Route::apiResource('teachers', TeacherController::class);

	Route::post('students/register', [StudentController::class, 'registerStudent']);
	Route::apiResource('students', StudentController::class);

	Route::get('/test', function () {
		return response()->json(['message' => 'API is working with JWT!']);
	});
});
