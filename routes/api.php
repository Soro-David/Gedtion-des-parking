<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\LicensePlateOcrController;
use App\Http\Controllers\Api\ParkingAiAssistantController;

/*
|--------------------------------------------------------------------------
| auth/ — Authentification (public)
|--------------------------------------------------------------------------
*/
Route::prefix('auth')->group(function () {
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/password/forgot', [AuthController::class, 'forgotPassword']);
    Route::post('/password/reset', [AuthController::class, 'resetPassword']);
    Route::post('/email/verify', [AuthController::class, 'sendEmailVerification']);
    Route::get('/email/verify/{id}/{hash}', [AuthController::class, 'verifyEmail'])->name('verification.verify');

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::get('/me',     [AuthController::class, 'me']);
    });
});

/*
|--------------------------------------------------------------------------
| agent/ — Routes réservées aux agents (attendants)
|--------------------------------------------------------------------------
*/
Route::prefix('agent')
    ->middleware(['auth:sanctum', 'role:attendant'])->group(function () {

        // Reconnaissance de plaque d'immatriculation
        Route::prefix('ocr')->group(function () {
            Route::post('/license-plate', [LicensePlateOcrController::class, 'extract']);
        });

        // Assistant IA parking
        Route::prefix('parking-ai')->group(function () {
            Route::post('/ask', [ParkingAiAssistantController::class, 'ask']);
        });
    });

/*
|--------------------------------------------------------------------------
| caissier/ — Routes réservées aux caissiers
|--------------------------------------------------------------------------
*/
Route::prefix('caissier')
    ->middleware(['auth:sanctum', 'role:caissier'])->group(function () {

        // Assistant IA parking
        Route::prefix('parking-ai')->group(function () {
            Route::post('/ask', [ParkingAiAssistantController::class, 'ask']);
        });
    });

