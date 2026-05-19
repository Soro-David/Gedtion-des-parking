<?php

namespace App\Http\Controllers;

use App\Models\Parking;
use App\Models\ParkingRate;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ParkingRateController extends Controller
{
    /**
     * Liste des parkings avec accès à leur grille tarifaire.
     */
    public function index()
    {
        $user = auth()->user();

        $query = Parking::where('is_active', true)->orderBy('name');

        if ($user->role === \App\Models\User::ROLE_SUPERVISOR) {
            $parkingIds = $user->parkings()->pluck('parkings.id');
            $query->whereIn('id', $parkingIds);
        }

        $parkings = $query->withCount('rates')->get(['id', 'name', 'reference', 'price']);

        return Inertia::render('ParkingRates/Index', [
            'parkings' => $parkings,
            'role'     => $user->role,
        ]);
    }

    /**
     * Grille tarifaire d'un parking spécifique.
     */
    public function show(Parking $parking)
    {
        $user = auth()->user();

        if ($user->role === \App\Models\User::ROLE_SUPERVISOR) {
            $ids = $user->parkings()->pluck('parkings.id');
            if (!$ids->contains($parking->id)) {
                abort(403);
            }
        }

        $rates = $parking->rates()
            ->orderBy('from_time')
            ->get()
            ->map(fn ($r) => [
                'id'        => $r->id,
                'label'     => $r->label,
                'from_time' => $r->from_time,
                'to_time'   => $r->to_time,
                'amount'    => $r->amount,
            ]);

        return Inertia::render('ParkingRates/Show', [
            'parking' => [
                'id'        => $parking->id,
                'name'      => $parking->name,
                'reference' => $parking->reference,
                'price'     => $parking->price,
            ],
            'rates' => $rates,
            'role'  => $user->role,
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'parking_id' => 'required|exists:parkings,id',
            'label'      => 'nullable|string|max:100',
            'from_time'  => 'required|date_format:H:i',
            'to_time'    => 'required|date_format:H:i|after:from_time',
            'amount'     => 'required|numeric|min:0',
        ]);

        if ($this->hasOverlap($data['from_time'], $data['to_time'], (int) $data['parking_id'])) {
            return back()->withErrors([
                'from_time' => 'Cet intervalle horaire chevauche un tarif existant pour ce parking.',
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
            'label'     => 'nullable|string|max:100',
            'from_time' => 'required|date_format:H:i',
            'to_time'   => 'required|date_format:H:i|after:from_time',
            'amount'    => 'required|numeric|min:0',
        ]);

        if ($this->hasOverlap($data['from_time'], $data['to_time'], (int) $parkingRate->parking_id, $parkingRate->id)) {
            return back()->withErrors([
                'from_time' => 'Cet intervalle horaire chevauche un tarif existant pour ce parking.',
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

    private function hasOverlap(string $fromTime, string $toTime, int $parkingId, ?int $excludeId = null): bool
    {
        $query = ParkingRate::where('parking_id', $parkingId)
            ->whereNotNull('from_time')
            ->whereNotNull('to_time');

        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        $query->where(function ($q) use ($fromTime, $toTime) {
            $q->where('from_time', '<', $toTime)
              ->where('to_time', '>', $fromTime);
        });

        return $query->exists();
    }
}
