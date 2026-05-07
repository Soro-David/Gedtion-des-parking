<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\ParkingAttendant;
use App\Models\Driver;
use App\Models\Parking;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function index()
    {
        $user = auth()->user();
        $stats = [];

        switch ($user->role) {

            case User::ROLE_SUPERADMIN:
                return redirect()->intended(route('superadmin.dashboard'));

            case User::ROLE_ADMIN:
                $stats = [
                    'supervisors' => User::where('role', User::ROLE_SUPERVISOR)->count(),
                    'attendants'  => User::where('role', User::ROLE_ATTENDANT)->count(),
                    'drivers'     => User::where('role', User::ROLE_DRIVER)->count(),
                    'revenue'     => '0 FCFA',
                ];
                break;

            case User::ROLE_CAISSIER:
                return redirect()->route('caissier.dashboard');

            case User::ROLE_SUPERVISOR:
                $myAttendants = ParkingAttendant::where('created_by', $user->id)->pluck('user_id');
                $stats = [
                    'attendants' => $myAttendants->count(),
                    'caissiers'  => User::where('role', User::ROLE_CAISSIER)->count(),
                    'parkings'   => Parking::count(),
                ];
                break;

            case User::ROLE_ATTENDANT:
                $myDrivers = Driver::where('created_by', $user->id);
                $stats = [
                    'drivers'       => $myDrivers->count(),
                    'today_drivers' => (clone $myDrivers)->whereDate('created_at', today())->count(),
                ];
                break;

            case User::ROLE_DRIVER:
                $stats = [];
                break;
        }

        return Inertia::render('Dashboard', [
            'stats' => $stats,
        ]);
    }
}
