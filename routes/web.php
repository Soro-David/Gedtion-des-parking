<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\Auth\InvitationController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Admin\SuperviseurController;
use App\Http\Controllers\Admin\AgentController;
use App\Http\Controllers\Admin\AdminUserController;
use App\Http\Controllers\Admin\CaissierController;
use App\Http\Controllers\Admin\HistoryController;
use App\Http\Controllers\Admin\ReversementController as AdminReversementController;
use App\Http\Controllers\Admin\VersementController as AdminVersementController;
use App\Http\Controllers\SuperAdmin\AdminController;
use App\Http\Controllers\SuperAdmin\DashboardController as SuperAdminDashboardController;
use App\Http\Controllers\SuperAdmin\UserController as SuperAdminUserController;
use App\Http\Controllers\SuperAdmin\HistoryController as SuperAdminHistoryController;
use App\Http\Controllers\SuperAdmin\ParkingsController as SuperAdminParkingsController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ParkingRateController;
use App\Http\Controllers\Caissier\ParkingSessionController;
use App\Http\Controllers\Caissier\ReversementController as CaissierReversementController;
use App\Http\Controllers\ParkingController;
use App\Http\Controllers\Api\LicensePlateOcrController;
use App\Http\Controllers\Api\ParkingAiAssistantController;
use App\Http\Controllers\Supervisor\CaissierController as SupervisorCaissierController;
use App\Http\Controllers\Supervisor\AttendantController;
use App\Http\Controllers\Supervisor\UserController as SupervisorUserController;
use App\Http\Controllers\Supervisor\HistoryController as SupervisorHistoryController;
use App\Http\Controllers\Supervisor\ReversementController as SupervisorReversementController;
use App\Http\Controllers\Supervisor\VersementController as SupervisorVersementController;
use App\Http\Controllers\Attendant\DriverController;
use App\Http\Controllers\Attendant\ParkingSessionController as AttendantParkingSessionController;
use App\Http\Controllers\Attendant\ReversementController as AttendantReversementController;
use App\Http\Controllers\Caissier\RapportController as CaissierRapportController;
use App\Http\Controllers\Caissier\CaissierController as CaissierDashboardController;
use App\Http\Controllers\Caissier\VersementController as CaissierVersementController;
use App\Http\Controllers\Public\PublicController;
// use App\Http\Controllers\Caissier\ParkingSessionController;
use Illuminate\Support\Facades\Route;

/*
| Public Routes
|--------------------------------------------------------------------------
*/
Route::get('/', [PublicController::class, 'accueil'])->name('public.accueil');
Route::get('/a-propos', [PublicController::class, 'aPropos'])->name('public.apropos');
Route::get('/contact', [PublicController::class, 'contact'])->name('public.contact');
Route::post('/contact', [PublicController::class, 'sendContact'])->name('public.contact.send');

// Invitation (set password) — accessible without authentication
Route::get('/invitation/{token}', [InvitationController::class, 'show'])->name('invitation.show');
Route::post('/invitation', [InvitationController::class, 'store'])->name('invitation.store');

Route::middleware('guest')->group(function () {
    Route::get('login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('login', [AuthenticatedSessionController::class, 'store']);
    
    // Si tu souhaites garder l'inscription publique, laisse cette ligne
    Route::get('register', [RegisteredUserController::class, 'create'])->name('register');
    Route::post('register', [RegisteredUserController::class, 'store']);
});

/*
|--------------------------------------------------------------------------
| Authenticated Routes
|--------------------------------------------------------------------------
*/
Route::middleware('auth')->group(function () {
    
    // Déconnexion
    Route::post('logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');

    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // OCR : reconnaissance de plaque (accessible à tous les utilisateurs authentifiés)
    Route::post('api/ocr/license-plate', [LicensePlateOcrController::class, 'extract'])->name('api.ocr.license-plate');

    // Assistant IA Parking (Gemini)
    Route::post('api/parking-ai/ask', [ParkingAiAssistantController::class, 'ask'])->name('api.parking-ai.ask');

    // Gestion du Profil
    Route::prefix('profile')->name('profile.')->group(function () {
        Route::get('/', [ProfileController::class, 'edit'])->name('edit');
        Route::patch('/', [ProfileController::class, 'update'])->name('update');
        Route::delete('/', [ProfileController::class, 'destroy'])->name('destroy');
    });

    // Administration (Super Admin) : Gère les Admins (routes explicites)
    Route::middleware('role:superadmin')->group(function () {
        Route::prefix('super/admin')->name('superadmin.')->group(function () {
            // SuperAdmin Dashboard
            Route::get('/dashboard', [SuperAdminDashboardController::class, 'index'])->name('dashboard');
            // Historique entrées/sorties
            Route::get('/history', [SuperAdminHistoryController::class, 'index'])->name('history.index');
            // Parkings
            Route::get('/parkings', [SuperAdminParkingsController::class, 'index'])->name('parkings.index');
            // Use names under `superadmin.admins.*` so frontend (Sidebar and pages) can reference them.
            Route::get('/', [AdminController::class, 'index'])->name('admins.index');
            Route::get('/create', [AdminController::class, 'create'])->name('admins.create');
            Route::post('', [AdminController::class, 'store'])->name('admins.store');
            Route::get('/{admin}/edit', [AdminController::class, 'edit'])->name('admins.edit');
            Route::put('/{admin}', [AdminController::class, 'update'])->name('admins.update');
            Route::delete('/{admin}', [AdminController::class, 'destroy'])->name('admins.destroy');
            // Gestion unifiée Admin + Superviseur
            Route::get('/users', [SuperAdminUserController::class, 'index'])->name('users.index');
            Route::post('/users', [SuperAdminUserController::class, 'store'])->name('users.store');
            Route::delete('/users/{user}', [SuperAdminUserController::class, 'destroy'])->name('users.destroy');
        });
    });

    // Gestion Globale des Parkings (Admin & Supervisor)
    Route::middleware('role:admin,supervisor')->group(function () {
        Route::resource('parkings', ParkingController::class);
        Route::patch('parkings/{parking}/toggle-status', [ParkingController::class, 'toggleStatus'])->name('parkings.toggleStatus');

        // Tarifs de stationnement (admin et supervisor)
        Route::prefix('parking-rates')->name('parking-rates.')->group(function () {
            Route::get('/', [ParkingRateController::class, 'index'])->name('index');
            Route::post('/', [ParkingRateController::class, 'store'])->name('store');
            Route::put('/{parkingRate}', [ParkingRateController::class, 'update'])->name('update');
            Route::delete('/{parkingRate}', [ParkingRateController::class, 'destroy'])->name('destroy');
        });
    });

    // Administration (Admin role) : routes explicites pour Admins
    Route::middleware('role:admin')->group(function () {
        Route::prefix('admin')->name('admin.')->group(function () {
            Route::prefix('superviseur')->name('superviseur.')->group(function () {
                Route::get('/', [SuperviseurController::class, 'index'])->name('index');
                Route::get('/create', [SuperviseurController::class, 'create'])->name('create');
                Route::post('/', [SuperviseurController::class, 'store'])->name('store');
                Route::get('/{supervisor}', [SuperviseurController::class, 'show'])->name('show');
                Route::get('/{supervisor}/edit', [SuperviseurController::class, 'edit'])->name('edit');
                Route::put('/{supervisor}', [SuperviseurController::class, 'update'])->name('update');
                Route::delete('/{supervisor}', [SuperviseurController::class, 'destroy'])->name('destroy');
                Route::post('/{supervisor}/resend-invitation', [SuperviseurController::class, 'resendInvitation'])->name('resend-invitation');
            });
            Route::prefix('agent')->name('agent.')->group(function () {
                Route::get('/', [AgentController::class, 'index'])->name('index');
                Route::get('/create', [AgentController::class, 'create'])->name('create');
                Route::post('/', [AgentController::class, 'store'])->name('store');
                Route::get('/{agent}', [AgentController::class, 'show'])->name('show');
                Route::get('/{agent}/edit', [AgentController::class, 'edit'])->name('edit');
                Route::put('/{agent}', [AgentController::class, 'update'])->name('update');
                Route::delete('/{agent}', [AgentController::class, 'destroy'])->name('destroy');
                Route::post('/{agent}/resend-invitation', [AgentController::class, 'resendInvitation'])->name('resend-invitation');
            });

             Route::prefix('caissier')->name('caissier.')->group(function () {
                Route::get('/', [CaissierController::class, 'index'])->name('index');
                Route::get('/create', [CaissierController::class, 'create'])->name('create');
                Route::post('/', [CaissierController::class, 'store'])->name('store');
                Route::get('/{caissier}', [CaissierController::class, 'show'])->name('show');
                Route::get('/{caissier}/edit', [CaissierController::class, 'edit'])->name('edit');
                Route::put('/{caissier}', [CaissierController::class, 'update'])->name('update');
                Route::delete('/{caissier}', [CaissierController::class, 'destroy'])->name('destroy');
                Route::post('/{caissier}/resend-invitation', [CaissierController::class, 'resendInvitation'])->name('resend-invitation');
            });

            // Gestion unifiée des utilisateurs (superviseurs, agents, caissiers)
            Route::get('users', [AdminUserController::class, 'index'])->name('users.index');
            Route::get('users/create', [AdminUserController::class, 'create'])->name('users.create');
            Route::post('users', [AdminUserController::class, 'store'])->name('users.store');
            Route::delete('users/{user}', [AdminUserController::class, 'destroy'])->name('users.destroy');

            // Historique global entrées/sorties (admin)
            Route::get('history', [HistoryController::class, 'index'])->name('history.index');

            // Reversements reçus par l'admin
            Route::get('reversements', [AdminReversementController::class, 'index'])->name('reversements.index');
            Route::post('reversements/{reversement}/confirm', [AdminReversementController::class, 'confirm'])->name('reversements.confirm');

            // Versements (admin collecte les montants des agents)
            Route::get('versements', [AdminVersementController::class, 'index'])->name('versements.index');
            Route::post('versements', [AdminVersementController::class, 'store'])->name('versements.store');
        });
    });

    // Superviseur : Gère les Attendants (Agents) et les Caissiers - routes explicites
    Route::middleware('role:supervisor')->group(function () {
        Route::prefix('supervisor')->name('supervisor.')->group(function () {
            Route::get('users', [SupervisorUserController::class, 'index'])->name('users.index');
            Route::post('users', [SupervisorUserController::class, 'store'])->name('users.store');
            Route::delete('users/{user}', [SupervisorUserController::class, 'destroy'])->name('users.destroy');
            Route::get('attendants', [AttendantController::class, 'index'])->name('attendants.index');
            Route::get('attendants/create', [AttendantController::class, 'create'])->name('attendants.create');
            Route::post('attendants', [AttendantController::class, 'store'])->name('attendants.store');
            Route::get('attendants/{attendant}/edit', [AttendantController::class, 'edit'])->name('attendants.edit');
            Route::put('attendants/{attendant}', [AttendantController::class, 'update'])->name('attendants.update');
            Route::delete('attendants/{attendant}', [AttendantController::class, 'destroy'])->name('attendants.destroy');
            Route::get('caissiers', [CaissierController::class, 'index'])->name('caissiers.index');
            Route::get('caissiers/create', [CaissierController::class, 'create'])->name('caissiers.create');
            Route::post('caissiers', [CaissierController::class, 'store'])->name('caissiers.store');
            Route::get('caissiers/{caissier}', [SupervisorCaissierController::class, 'show'])->name('caissiers.show');
            Route::get('caissiers/{caissier}/edit', [SupervisorCaissierController::class, 'edit'])->name('caissiers.edit');
            Route::put('caissiers/{caissier}', [SupervisorCaissierController::class, 'update'])->name('caissiers.update');
            Route::delete('caissiers/{caissier}', [SupervisorCaissierController::class, 'destroy'])->name('caissiers.destroy');
            Route::post('caissiers/{caissier}/resend-invitation', [SupervisorCaissierController::class, 'resendInvitation'])->name('caissiers.resend-invitation');
            // Reversements reçus par le superviseur
            Route::get('reversements', [SupervisorReversementController::class, 'index'])->name('reversements.index');
            Route::post('reversements/{reversement}/confirm', [SupervisorReversementController::class, 'confirm'])->name('reversements.confirm');

            // Versements (superviseur collecte les montants des agents)
            Route::get('versements', [SupervisorVersementController::class, 'index'])->name('versements.index');
            Route::post('versements', [SupervisorVersementController::class, 'store'])->name('versements.store');
            // Historique entrées/sorties + parkings
            Route::get('history', [SupervisorHistoryController::class, 'index'])->name('history.index');
        });
    });

    // Attendant (Agent) : Gère les Conducteurs (Drivers) - routes explicites
    Route::middleware('role:attendant')->group(function () {
        Route::prefix('attendant')->name('attendant.')->group(function () {
            Route::get('drivers', [DriverController::class, 'index'])->name('drivers.index');
            Route::get('drivers/create', [DriverController::class, 'create'])->name('drivers.create');
            Route::post('drivers', [DriverController::class, 'store'])->name('drivers.store');
            Route::get('drivers/{driver}/edit', [DriverController::class, 'edit'])->name('drivers.edit');
            Route::put('drivers/{driver}', [DriverController::class, 'update'])->name('drivers.update');
            Route::delete('drivers/{driver}', [DriverController::class, 'destroy'])->name('drivers.destroy');

            // Stationnements
            Route::get('parking-sessions', [AttendantParkingSessionController::class, 'index'])->name('parking-sessions.index');
            Route::get('parking-sessions/create', [AttendantParkingSessionController::class, 'create'])->name('parking-sessions.create');
            Route::post('parking-sessions', [AttendantParkingSessionController::class, 'store'])->name('parking-sessions.store');
            Route::get('parking-sessions/{session}/ticket', [AttendantParkingSessionController::class, 'ticket'])->name('parking-sessions.ticket');
            Route::get('parking-sessions/{session}/receipt', [AttendantParkingSessionController::class, 'receipt'])->name('parking-sessions.receipt');

            // Enregistrement des sorties
            Route::get('checkout', [AttendantParkingSessionController::class, 'checkout'])->name('checkout');
            Route::post('checkout', [AttendantParkingSessionController::class, 'checkoutStore'])->name('checkout.store');

            // Historique des sorties
            Route::get('history', [AttendantParkingSessionController::class, 'history'])->name('history');

            // Reversements (agent)
            Route::get('reversements', [AttendantReversementController::class, 'index'])->name('reversements.index');
            Route::post('reversements', [AttendantReversementController::class, 'store'])->name('reversements.store');
        });
    });
    
    // Caissier : accès dédié (ex: tableau de bord caisse)
    Route::middleware('role:caissier')->group(function () {
        Route::prefix('caissier')->name('caissier.')->group(function () {
            Route::get('/', [CaissierDashboardController::class, 'index'])->name('dashboard');

            // Stationnements caissier
            Route::get('parking-sessions', [ParkingSessionController::class, 'index'])->name('parking-sessions.index');
            Route::get('parking-sessions/create', [ParkingSessionController::class, 'create'])->name('parking-sessions.create');
            Route::post('parking-sessions', [ParkingSessionController::class, 'store'])->name('parking-sessions.store');
            Route::get('parking-sessions/{session}/ticket', [ParkingSessionController::class, 'ticket'])->name('parking-sessions.ticket');
            Route::get('parking-sessions/{session}/receipt', [ParkingSessionController::class, 'receipt'])->name('parking-sessions.receipt');

            // Enregistrement des sorties
            Route::get('checkout', [ParkingSessionController::class, 'checkout'])->name('checkout');
            Route::post('checkout', [ParkingSessionController::class, 'checkoutStore'])->name('checkout.store');

            // Historique caisse
            Route::get('history', [ParkingSessionController::class, 'history'])->name('history');

            // Reversements (caissier)
            Route::get('reversements', [CaissierReversementController::class, 'index'])->name('reversements.index');
            Route::post('reversements', [CaissierReversementController::class, 'store'])->name('reversements.store');

            // Rapport caissier
            Route::get('rapport', [CaissierRapportController::class, 'index'])->name('rapport.index');
            Route::get('rapport/export-pdf', [CaissierRapportController::class, 'exportPdf'])->name('rapport.export-pdf');
            Route::get('rapport/export-csv', [CaissierRapportController::class, 'exportCsv'])->name('rapport.export-csv');

            // Versements reçus par le caissier
            Route::get('versements', [CaissierVersementController::class, 'index'])->name('versements.index');
        });
    });
    
});
