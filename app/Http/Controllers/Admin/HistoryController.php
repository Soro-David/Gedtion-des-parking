<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ParkingSession;
use Inertia\Inertia;

class HistoryController extends Controller
{
    /**
     * Affiche l'historique global des entrées et sorties (admin).
     * Entrées  = sessions avec statut 'occupied'
     * Sorties  = sessions avec statut 'released'
     */
    public function index()
    {
        $entries = ParkingSession::with(['parking', 'agent'])
            ->where('status', 'occupied')
            ->latest('started_at')
            ->get()
            ->map(fn($s) => [
                'id'            => $s->id,
                'license_plate' => $s->license_plate,
                'marque'        => $s->marque,
                'modele'        => $s->modele,
                'parking_name'  => $s->parking?->name,
                'parking_id'    => $s->parking_id,
                'agent_name'    => $s->agent?->name,
                'started_at'    => $s->started_at,
                'duration_minutes' => $s->duration_minutes,
            ]);

        $exits = ParkingSession::with(['parking', 'agent', 'closer'])
            ->where('status', 'released')
            ->latest('ended_at')
            ->get()
            ->map(fn($s) => [
                'id'            => $s->id,
                'license_plate' => $s->license_plate,
                'marque'        => $s->marque,
                'modele'        => $s->modele,
                'parking_name'  => $s->parking?->name,
                'parking_id'    => $s->parking_id,
                'agent_name'    => $s->agent?->name,
                'closer_name'   => $s->closer?->name,
                'started_at'    => $s->started_at,
                'ended_at'      => $s->ended_at,
                'duration_minutes' => (int) ceil($s->started_at->diffInMinutes($s->ended_at)),
                'amount'        => $s->amount,
            ]);

        return Inertia::render('Admin/History/Index', [
            'entries' => $entries,
            'exits'   => $exits,
        ]);
    }
}
