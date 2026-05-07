<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\Parking;
use Inertia\Inertia;

class ParkingsController extends Controller
{
    public function index()
    {
        $parkings = Parking::withCount([
            'sessions as active_count' => fn ($q) => $q->where('status', 'occupied'),
        ])->orderBy('name')->get()->map(fn ($p) => [
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

        return Inertia::render('SuperAdmin/Parkings/Index', [
            'parkings' => $parkings,
        ]);
    }
}
