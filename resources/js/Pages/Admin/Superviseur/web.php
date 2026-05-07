<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Admin\SuperviseurController;
use App\Http\Controllers\Admin\AgentController;
use App\Http\Controllers\SuperAdmin\AdminController;
use App\Http\Controllers\DashboardController;
use Illuminate\Support\Facades\Route;

/*
| Public Routes
|--------------------------------------------------------------------------
*/
Route::get('/', function () {
    return redirect()->route('login');
});

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

    // Gestion du Profil
    Route::prefix('profile')->name('profile.')->group(function () {
        Route::get('/', [ProfileController::class, 'edit'])->name('edit');
        Route::patch('/', [ProfileController::class, 'update'])->name('update');
        Route::delete('/', [ProfileController::class, 'destroy'])->name('destroy');
    });

    // Administration (Super Admin) : Gère les Admins (routes explicites)
    Route::middleware('role:superadmin')->group(function () {
        Route::prefix('super/admin')->name('superadmin.')->group(function () {
            // Use names under `superadmin.admins.*` so frontend (Sidebar and pages) can reference them.
            Route::get('/', [AdminController::class, 'index'])->name('admins.index');
            Route::get('/create', [AdminController::class, 'create'])->name('admins.create');
            Route::post('', [AdminController::class, 'store'])->name('admins.store');
            Route::get('/{admin}/edit', [AdminController::class, 'edit'])->name('admins.edit');
            Route::put('/{admin}', [AdminController::class, 'update'])->name('admins.update');
            Route::delete('/{admin}', [AdminController::class, 'destroy'])->name('admins.destroy');
        });
    });

    // Administration (Admin role) : routes explicites pour Admins et Supervisors
    Route::middleware('role:admin')->group(function () {
        Route::prefix('admin')->name('admin.')->group(function () {
            Route::prefix('superviseur')->name('superviseur.')->group(function () {
                Route::get('/', [SuperviseurController::class, 'index'])->name('index');
                Route::get('/create', [SuperviseurController::class, 'create'])->name('create');
                Route::post('/', [SuperviseurController::class, 'store'])->name('store');
                Route::get('/{supervisor}/edit', [SuperviseurController::class, 'edit'])->name('edit');
                Route::put('/{supervisor}', [SuperviseurController::class, 'update'])->name('update');
            });
            Route::prefix('agent')->name('agent.')->group(function () {
                Route::get('/', [AgentController::class, 'index'])->name('index');
                Route::get('/create', [AgentController::class, 'create'])->name('create');
                Route::post('/', [AgentController::class, 'store'])->name('store');
                Route::get('/{agent}/edit', [AgentController::class, 'edit'])->name('edit');
                Route::put('/{agent}', [AgentController::class, 'update'])->name('update');
                Route::delete('/{attendant}', [AttendantController::class, 'destroy'])->name('destroy');
            });
            // Route::delete('admins/{admin}', [AdminController::class, 'destroy'])->name('admins.destroy');

            // Supervisors
            Route::get('supervisors', [SupervisorController::class, 'index'])->name('supervisors.index');
            Route::get('supervisors/create', [SupervisorController::class, 'create'])->name('supervisors.create');
            Route::post('supervisors', [SupervisorController::class, 'store'])->name('supervisors.store');
            Route::get('supervisors/{supervisor}/edit', [SupervisorController::class, 'edit'])->name('supervisors.edit');
            Route::put('supervisors/{supervisor}', [SupervisorController::class, 'update'])->name('supervisors.update');
            Route::delete('supervisors/{supervisor}', [SupervisorController::class, 'destroy'])->name('supervisors.destroy');
            Route::get('supervisors/{supervisor}', [SupervisorController::class, 'show'])->name('supervisors.show');
        });
    });

    // Superviseur : Gère les Attendants (Agents) - routes explicites
    Route::middleware('role:supervisor')->group(function () {
        Route::prefix('supervisor')->name('supervisor.')->group(function () {
            Route::get('attendants', [\App\Http\Controllers\Supervisor\AttendantController::class, 'index'])->name('attendants.index');
            Route::get('attendants/create', [\App\Http\Controllers\Supervisor\AttendantController::class, 'create'])->name('attendants.create');
            Route::post('attendants', [\App\Http\Controllers\Supervisor\AttendantController::class, 'store'])->name('attendants.store');
            Route::get('attendants/{attendant}/edit', [\App\Http\Controllers\Supervisor\AttendantController::class, 'edit'])->name('attendants.edit');
            Route::put('attendants/{attendant}', [\App\Http\Controllers\Supervisor\AttendantController::class, 'update'])->name('attendants.update');
            Route::delete('attendants/{attendant}', [\App\Http\Controllers\Supervisor\AttendantController::class, 'destroy'])->name('attendants.destroy');
        });
    });

    // Attendant (Agent) : Gère les Conducteurs (Drivers) - routes explicites
    Route::middleware('role:attendant')->group(function () {
        Route::prefix('attendant')->name('attendant.')->group(function () {
            Route::get('drivers', [\App\Http\Controllers\Attendant\DriverController::class, 'index'])->name('drivers.index');
            Route::get('drivers/create', [\App\Http\Controllers\Attendant\DriverController::class, 'create'])->name('drivers.create');
            Route::post('drivers', [\App\Http\Controllers\Attendant\DriverController::class, 'store'])->name('drivers.store');
            Route::get('drivers/{driver}/edit', [\App\Http\Controllers\Attendant\DriverController::class, 'edit'])->name('drivers.edit');
            Route::put('drivers/{driver}', [\App\Http\Controllers\Attendant\DriverController::class, 'update'])->name('drivers.update');
            Route::delete('drivers/{driver}', [\App\Http\Controllers\Attendant\DriverController::class, 'destroy'])->name('drivers.destroy');
        });
    });
    
    // Caissier : accès dédié (ex: tableau de bord caisse)
    Route::middleware('role:caissier')->group(function () {
        Route::prefix('caissier')->name('caissier.')->group(function () {
            Route::get('/', [\App\Http\Controllers\Caissier\CaissierController::class, 'index'])->name('dashboard');
            // Ajoutez ici d'autres routes dédiées aux caissiers (encaissements, rapports...)
        });
    });
    
});
