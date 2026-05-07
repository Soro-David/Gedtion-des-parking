<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\ParkingAttendant;
use App\Models\Driver;
use App\Models\Parking;
use App\Models\ParkingSession;
use Illuminate\Support\Carbon;
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
                $now  = Carbon::now();
                $myDrivers = Driver::where('created_by', $user->id);

                // Base query : sessions clôturées par cet agent
                $base = ParkingSession::where('closed_by', $user->id)
                    ->where('status', 'released');

                // Dette = montant total encaissé − total des reversements effectués
                $montantTotal = (clone $base)->sum('amount');
                $totalReverse = \App\Models\Reversement::where('user_id', $user->id)->sum('amount');
                $dette        = max(0, $montantTotal - $totalReverse);

                // Montants encaissés
                $montantJour  = (clone $base)->whereDate('ended_at', $now->toDateString())->sum('amount');
                $montantMois  = (clone $base)->whereYear('ended_at', $now->year)->whereMonth('ended_at', $now->month)->sum('amount');
                $montantAnnee = (clone $base)->whereYear('ended_at', $now->year)->sum('amount');

                // Nombre de sorties
                $nbJour  = (clone $base)->whereDate('ended_at', $now->toDateString())->count();
                $nbMois  = (clone $base)->whereYear('ended_at', $now->year)->whereMonth('ended_at', $now->month)->count();
                $nbAnnee = (clone $base)->whereYear('ended_at', $now->year)->count();

                // Dernières sorties enregistrées par l'agent
                $dernieresSessions = ParkingSession::with('parking')
                    ->where('closed_by', $user->id)
                    ->where('status', 'released')
                    ->latest('ended_at')
                    ->take(5)
                    ->get()
                    ->map(fn($s) => [
                        'id'            => $s->id,
                        'license_plate' => $s->license_plate,
                        'marque'        => $s->marque,
                        'modele'        => $s->modele,
                        'parking_name'  => $s->parking?->name,
                        'ended_at'      => $s->ended_at?->toDateTimeString(),
                        'amount'        => $s->amount,
                    ]);

                $stats = [
                    'drivers'          => $myDrivers->count(),
                    'today_drivers'    => (clone $myDrivers)->whereDate('created_at', today())->count(),
                    'dette'            => $dette,
                    'montantJour'      => $montantJour,
                    'montantMois'      => $montantMois,
                    'montantAnnee'     => $montantAnnee,
                    'nbJour'           => $nbJour,
                    'nbMois'           => $nbMois,
                    'nbAnnee'          => $nbAnnee,
                    'dernieresSessions'=> $dernieresSessions,
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
