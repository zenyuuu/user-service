<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\HealthController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| User Service — RESTful API Routes
|--------------------------------------------------------------------------
|
| Base URL: http://<IP_LAPTOP_1>:8001/api
|
*/

// ── Health check (public, untuk dicek service lain) ─────────────────────────
Route::get('/health', [HealthController::class, 'check']);

// ── Auth routes (public) ─────────────────────────────────────────────────────
Route::prefix('auth')->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login',    [AuthController::class, 'login']);

    // Butuh token
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::get('/me',      [AuthController::class, 'me']);
    });
});

// ── Internal endpoint (dipanggil service lain, tanpa auth token user) ────────
Route::prefix('internal')->group(function () {
    Route::get('/users/{id}/validate', [UserController::class, 'validateUser']);
});

// ── User routes (butuh token) ─────────────────────────────────────────────────
Route::middleware('auth:sanctum')->group(function () {

    // User bisa lihat & edit profil sendiri
    Route::get('/users/{id}',  [UserController::class, 'show']);
    Route::put('/users/{id}',  [UserController::class, 'update']);

    // Admin only
    Route::middleware('admin')->group(function () {
        Route::get('/users',          [UserController::class, 'index']);
        Route::delete('/users/{id}',  [UserController::class, 'destroy']);
    });
});

// ── Hasura Action handlers
Route::prefix('actions')->group(function () {
    Route::post('/login', [App\Http\Controllers\HasuraActionController::class, 'login']);
    Route::post('/register', [App\Http\Controllers\HasuraActionController::class, 'register']);
    Route::post('/validate-user', [App\Http\Controllers\HasuraActionController::class, 'validateUser']);
});