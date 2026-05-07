<?php

namespace App\Http\Controllers\Supervisor;

use App\Http\Controllers\Controller;
use App\Models\Parking;
use App\Models\ParkingSession;
use Inertia\Inertia;

class HistoryController extends Controller
{
    public function index()
    {
        $entries = ParkingSession::with(['parking', 'agent'])
            ->where('status', 'occupied')
            ->latest('started_at')
            ->limit(200)
            ->get()
            ->map(fn ($s) => [
                'id'               => $s->id,
                'license_plate'    => $s->license_plate,
                'marque'           => $s->marque,
                'modele'           => $s->modele,
                'parking_name'     => $s->parking?->name,
                'parking_id'       => $s->parking_id,
                'agent_name'       => $s->agent?->name,
                'started_at'       => $s->started_at,
                'duration_minutes' => $s->duration_minutes,
            ]);

        $exits = ParkingSession::with(['parking', 'agent', 'closer'])
            ->where('status', 'released')
            ->latest('ended_at')
            ->limit(200)
            ->get()
            ->map(fn ($s) => [
                'id'               => $s->id,
                'license_plate'    => $s->license_plate,
                'marque'           => $s->marque,
                'modele'           => $s->modele,
                'parking_name'     => $s->parking?->name,
                'parking_id'       => $s->parking_id,
                'agent_name'       => $s->agent?->name,
                'closer_name'      => $s->closer?->name,
                'started_at'       => $s->started_at,
                'ended_at'         => $s->ended_at,
                'duration_minutes' => $s->started_at ? (int) ceil($s->started_at->diffInMinutes($s->ended_at)) : 0,
                'amount'           => $s->amount,
            ]);

        $parkings = Parking::withCount([
            'sessions as active_count' => fn ($q) => $q->where('status', 'occupied'),
        ])->get()->map(fn ($p) => [
            'id'              => $p->id,
            'name'            => $p->name,
            'address'         => $p->address,
            'latitude'        => $p->latitude,
            'longitude'       => $p->longitude,
            'capacity'        => $p->capacity,
            'occupied_spots'  => $p->active_count,
            'available_spots' => max(0, $p->capacity - $p->active_count),
            'is_active'       => $p->is_active,
        ]);

        return Inertia::render('Supervisor/History/Index', [
            'entries'  => $entries,
            'exits'    => $exits,
            'parkings' => $parkings,
        ]);
    }
}
