<?php

use App\Http\Controllers\Api\AuthController;
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

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user', [AuthController::class, 'me']);

    Route::middleware('role.admin')->get('/admin-check', function () {
        return response()->json(['status' => 'admin-confirmed']);
    });
});
