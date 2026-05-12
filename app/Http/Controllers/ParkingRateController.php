<?php

namespace App\Http\Controllers;

use App\Models\Parking;
use App\Models\ParkingRate;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ParkingRateController extends Controller
{
    public function index()
    {
        $user = auth()->user();

        $parkingIdsForSupervisor = null;
        if ($user->role === \App\Models\User::ROLE_SUPERVISOR) {
            $parkingIdsForSupervisor = $user->parkings()->pluck('parkings.id');
        }

        $rates = ParkingRate::with('parking')
            ->when($parkingIdsForSupervisor, fn ($q) => $q->whereIn('parking_id', $parkingIdsForSupervisor))
            ->orderByRaw('COALESCE(parking_id, 0)')
            ->orderBy('from_minutes')
            ->get()
            ->map(fn ($r) => [
                'id'           => $r->id,
                'parking_id'   => $r->parking_id,
                'parking_name' => $r->parking?->name,
                'label'        => $r->label,
                'from_minutes' => $r->from_minutes,
                'to_minutes'   => $r->to_minutes,
                'amount'       => $r->amount,
            ]);

        $parkingsQuery = Parking::where('is_active', true)->orderBy('name');
        if ($parkingIdsForSupervisor) {
            $parkingsQuery->whereIn('id', $parkingIdsForSupervisor);
        }
        $parkings = $parkingsQuery->get(['id', 'name', 'reference']);

        $role = $user->role;

        return Inertia::render('ParkingRates/Index', [
            'rates'    => $rates,
            'parkings' => $parkings,
            'role'     => $role,
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'parking_id'   => 'nullable|exists:parkings,id',
            'label'        => 'nullable|string|max:100',
            'from_minutes' => 'required|integer|min:0',
            'to_minutes'   => 'nullable|integer|min:1|gt:from_minutes',
            'amount'       => 'required|numeric|min:0',
        ]);

        if ($this->hasOverlap($data['from_minutes'], $data['to_minutes'] ?? null, $data['parking_id'] ?? null)) {
            return back()->withErrors([
                'from_minutes' => 'Cet intervalle chevauche un tarif existant pour ce parking.',
            ])->withInput();
        }

        ParkingRate::create([
            ...$data,
            'created_by' => auth()->id(),
        ]);

        return back()->with('success', 'Tarif créé avec succès.');
    }

    public function update(Request $request, ParkingRate $parkingRate)
    {
        $data = $request->validate([
            'parking_id'   => 'nullable|exists:parkings,id',
            'label'        => 'nullable|string|max:100',
            'from_minutes' => 'required|integer|min:0',
            'to_minutes'   => 'nullable|integer|min:1|gt:from_minutes',
            'amount'       => 'required|numeric|min:0',
        ]);

        if ($this->hasOverlap($data['from_minutes'], $data['to_minutes'] ?? null, $data['parking_id'] ?? null, $parkingRate->id)) {
            return back()->withErrors([
                'from_minutes' => 'Cet intervalle chevauche un tarif existant pour ce parking.',
            ])->withInput();
        }

        $parkingRate->update($data);

        return back()->with('success', 'Tarif mis à jour avec succès.');
    }

    public function destroy(ParkingRate $parkingRate)
    {
        $parkingRate->delete();

        return back()->with('success', 'Tarif supprimé.');
    }

    private function hasOverlap(int $fromMinutes, ?int $toMinutes, ?int $parkingId, ?int $excludeId = null): bool
    {
        $query = ParkingRate::query();

        if (is_null($parkingId)) {
            $query->whereNull('parking_id');
        } else {
            $query->where('parking_id', $parkingId);
        }

        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        // Deux intervalles [F1,T1] et [F2,T2] se chevauchent si :
        // F1 <= T2 (ou T2 est NULL) ET F2 <= T1 (ou T1 est NULL)
        $query->where(function ($q) use ($fromMinutes, $toMinutes) {
            // F_existant <= T_nouveau (ou T_nouveau est null = infini)
            if ($toMinutes !== null) {
                $q->where('from_minutes', '<=', $toMinutes);
            }
            // ET F_nouveau <= T_existant (ou T_existant est null = infini)
            $q->where(function ($q2) use ($fromMinutes) {
                $q2->whereNull('to_minutes')
                   ->orWhere('to_minutes', '>=', $fromMinutes);
            });
        });

        return $query->exists();
    }
}
