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
        $rates = ParkingRate::with('parking')
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

        $parkings = Parking::where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'reference']);

        $role = auth()->user()->role;

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
            'to_minutes'   => 'nullable|integer|min:1',
            'amount'       => 'required|numeric|min:0',
        ]);

        $parkingRate->update($data);

        return back()->with('success', 'Tarif mis à jour avec succès.');
    }

    public function destroy(ParkingRate $parkingRate)
    {
        $parkingRate->delete();

        return back()->with('success', 'Tarif supprimé.');
    }
}
