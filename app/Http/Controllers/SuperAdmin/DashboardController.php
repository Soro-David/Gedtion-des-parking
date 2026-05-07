<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\Parking;
use App\Models\ParkingSession;
use App\Models\User;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function index()
    {
        $stats = [
            'total_admins'    => User::where('role', User::ROLE_ADMIN)->count(),
            'total_parkings'  => Parking::count(),
            'active_sessions' => ParkingSession::where('status', 'occupied')->count(),
            'today_sessions'  => ParkingSession::whereDate('started_at', today())->count(),
            'today_exits'     => ParkingSession::where('status', 'released')
                ->whereDate('ended_at', today())->count(),
            'today_revenue'   => ParkingSession::where('status', 'released')
                ->whereDate('ended_at', today())->sum('amount'),
        ];

        return Inertia::render('SuperAdmin/Dashboard', [
            'stats' => $stats,
        ]);
    }
}
