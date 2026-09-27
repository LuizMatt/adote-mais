<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\PetController;
use Illuminate\Support\Facades\Route;

Route::get('/ping', function () {
    return response()->json([
        'status' => 'ok',
        'message' => 'Adota+ API online',
        'timestamp' => now()->toIso8601String(),
    ]);
});

// Authentication routes
Route::post('/login', [AuthController::class, 'login']);

// Public Pet Catalog
Route::get('/pets', [PetController::class, 'index']);
Route::get('/pets/{id}', [PetController::class, 'show']);

// Protected Management Routes
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user', [AuthController::class, 'me']);

    Route::middleware('role.admin')->get('/admin-check', function () {
        return response()->json(['status' => 'admin-confirmed']);
    });

    // Agent and Admin Actions
    Route::put('/pets/{id}', [PetController::class, 'update']);
    Route::patch('/pets/{id}/status', [PetController::class, 'updateStatus']);

    // Admin-Only Actions
    Route::middleware('role.admin')->group(function () {
        Route::post('/pets', [PetController::class, 'store']);
        Route::delete('/pets/{id}', [PetController::class, 'destroy']);
    });
});

