<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\LicensePlateOcrController;
use App\Http\Controllers\Api\ParkingAiAssistantController;
use App\Http\Controllers\Api\Agent\ParkingSessionController as AgentParkingSessionController;
use App\Http\Controllers\Api\Agent\AgentVersementController;
use App\Http\Controllers\Api\Agent\AgentStatController;

use App\Http\Controllers\Api\Caissier\ParkingSessionController as CaissierParkingSessionController;
use App\Http\Controllers\Api\Caissier\CaissierVersementController;
use App\Http\Controllers\Api\Caissier\CaissierStatController;
use App\Http\Controllers\Api\SignalementController;
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
        Route::post('/profile', [AuthController::class, 'updateProfile']);
    });
});

/*
|--------------------------------------------------------------------------
| agent/ — Routes réservées aux agents (attendants)
|--------------------------------------------------------------------------
*/
Route::prefix('attendant')
    ->middleware(['auth:sanctum', 'role:attendant'])->group(function () {

        // Reconnaissance de plaque d'immatriculation
        Route::prefix('ocr')->group(function () {
            Route::post('/license-plate', [LicensePlateOcrController::class, 'extract']);
        });

        // Assistant IA parking
        Route::prefix('parking-ai')->group(function () {
            Route::post('/ask', [ParkingAiAssistantController::class, 'ask']);
        });

        // Enregistrement de stationnement, sortie, historique, stats
        Route::prefix('parking-sessions')->group(function () {
            Route::post('/', [AgentParkingSessionController::class, 'store']);
            Route::post('/checkout', [AgentParkingSessionController::class, 'checkout']);
            Route::get('/history/entries', [AgentParkingSessionController::class, 'historyEntries']);
            Route::get('/history/exits', [AgentParkingSessionController::class, 'historyExits']);
            Route::get('/stationnement_en_cours', [AgentParkingSessionController::class, 'stationnement_en_cours']);
        });

        Route::prefix('versements')->group(function () {
            Route::get('list', [AgentVersementController::class,'list']);
            Route::get('list/{id}', [AgentVersementController::class,'show']);
            Route::post('/', [AgentVersementController::class, 'store']);
        });
        Route::get('/stats', [AgentParkingSessionController::class, 'stats']);

        Route::prefix('statistiques')->group(function () {
            Route::get('/total-encaisser', [AgentStatController::class, 'total_encaisser']);
            Route::get('/encaisse-non-verse', [AgentStatController::class, 'encaisse_non_verse']);
            Route::get('/dette', [AgentStatController::class, 'dette']);
            Route::get('/stationnement-en-cours', [AgentStatController::class, 'stationnement_en_cours']);
        });

        Route::prefix('stats')->group(function () {
            Route::get('/history/entries', [CaissierParkingSessionController::class, 'historyEntries']);
            Route::get('/history/exits', [CaissierParkingSessionController::class, 'historyExits']);
            Route::get('/stationnement_en_cours', [CaissierParkingSessionController::class, 'stationnement_en_cours']);
        });
    });



Route::prefix('caissier')
    ->middleware(['auth:sanctum', 'role:caissier'])->group(function () {

        // Reconnaissance de plaque d'immatriculation
        Route::prefix('ocr')->group(function () {
            Route::post('/license-plate', [LicensePlateOcrController::class, 'extract']);
        });

        // Assistant IA parking
        Route::prefix('parking-ai')->group(function () {
            Route::post('/ask', [ParkingAiAssistantController::class, 'ask']);
        });

        // Enregistrement de stationnement, sortie, historique, stats
        Route::prefix('parking-sessions')->group(function () {
            Route::post('/', [CaissierParkingSessionController::class, 'store']);
            Route::post('/checkout', [CaissierParkingSessionController::class, 'checkout']);
            Route::get('/history/entries', [CaissierParkingSessionController::class, 'historyEntries']);
            Route::get('/history/exits', [CaissierParkingSessionController::class, 'historyExits']);
            Route::get('/stationnement_en_cours', [CaissierParkingSessionController::class, 'stationnement_en_cours']);
        });

        Route::prefix('versements')->group(function () {
            Route::get('list', [CaissierVersementController::class,'list']);
            Route::get('list/{id}', [CaissierVersementController::class,'show']);
            Route::post('/', [CaissierVersementController::class, 'store']);
        });

        Route::prefix('reports')->group(function () {
            Route::get('/', [CaissierParkingSessionController::class, 'reports']);
            Route::get('/export/pdf', [CaissierParkingSessionController::class, 'exportPdf']);
            Route::get('/export/csv', [CaissierParkingSessionController::class, 'exportCsv']);
        });

        Route::get('/stats', [CaissierParkingSessionController::class, 'stats']);

        Route::prefix('statistiques')->group(function () {
            Route::get('/total-encaisser', [CaissierStatController::class, 'total_encaisser']);
            Route::get('/encaisse-non-verse', [CaissierStatController::class, 'encaisse_non_verse']);
            Route::get('/dette', [CaissierStatController::class, 'dette']);
            Route::get('/stationnement-en-cours', [CaissierStatController::class, 'stationnement_en_cours']);
        });
    });


/*
|--------------------------------------------------------------------------
| notifications/ — Gestion des tokens FCM et historique (protégé)
|--------------------------------------------------------------------------
*/
Route::prefix('notifications')
    ->middleware('auth:sanctum')
    ->group(function () {
        Route::post('/token', [NotificationController::class, 'saveToken']);
        Route::delete('/token', [NotificationController::class, 'removeToken']);
        Route::get('/history', [NotificationController::class, 'history']);
        Route::post('/mark-read/{id}', [NotificationController::class, 'markRead']);
        Route::post('/mark-all-read', [NotificationController::class, 'markAllRead']);
    });

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/vehicles/search', [SignalementController::class, 'searchVehicles']);
    Route::post('/signalements', [SignalementController::class, 'store']);
    Route::get('/signalements', [SignalementController::class, 'index']);
    Route::get('/signalements/{id}', [SignalementController::class, 'show']);
});

