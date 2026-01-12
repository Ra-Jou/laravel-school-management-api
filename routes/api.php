<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\AuthController;
use App\Http\Controllers\SchoolClassController;
use App\Http\Controllers\StudentController;
use App\Http\Controllers\SubjectController;
use App\Http\Controllers\TeacherController;
use App\Http\Controllers\UserController;

// Authentication routes
Route::prefix('auth')->group(function () {
	Route::post('login', [AuthController::class, 'login']);
});

// Routes protected by JWT authentication
Route::middleware('jwt.auth')->group(function () {
	Route::post('logout', [AuthController::class, 'logout']);
	Route::get('profile', [AuthController::class, 'profile']);

	// Combined registration routes (must be declared BEFORE resource routes)
	Route::post('students/register', [StudentController::class, 'registerStudent']);
	Route::post('teachers/register', [TeacherController::class, 'registerTeacher']);

	// CRUD API resources
	Route::apiResource('students', StudentController::class);
	Route::apiResource('teachers', TeacherController::class);
	Route::apiResource('users', UserController::class);
	Route::apiResource('subjects', SubjectController::class);
	Route::apiResource('school-classes', SchoolClassController::class);

	// Test endpoint
	Route::get('/test', function () {
		return response()->json(['message' => 'API is working with JWT!']);
	});
});
