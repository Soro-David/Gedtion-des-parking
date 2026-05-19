<?php

namespace App\Http\Controllers;

use App\Models\Parking;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ParkingController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $user  = auth()->user();
        $query = Parking::with([
                'creator',
                'rates' => fn ($q) => $q->orderBy('from_time'),
            ])
            ->withCount('activeSessions')
            ->latest();

        // Un superviseur ne voit que les parkings auxquels il est affecté
        if ($user->role === \App\Models\User::ROLE_SUPERVISOR) {
            $query->whereHas('users', fn ($q) => $q->where('users.id', $user->id));
        }

        return Inertia::render('Parking/Index', [
            'parkings' => $query->get(),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return Inertia::render('Parking/Create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'              => 'required|string|max:255',
            'address'           => 'required|string',
            'longitude'         => 'nullable|numeric|between:-180,180',
            'latitude'          => 'nullable|numeric|between:-90,90',
            'capacity'          => 'required|integer|min:1',
            'price'             => 'nullable|numeric|min:0',
            'image'             => 'nullable|image|max:2048',
            'rates'             => 'nullable|array',
            'rates.*.label'     => 'nullable|string|max:100',
            'rates.*.from_time' => 'required|date_format:H:i',
            'rates.*.to_time'   => 'required|date_format:H:i|after:rates.*.from_time',
            'rates.*.amount'    => 'required|numeric|min:0',
        ]);

        $rates = $validated['rates'] ?? [];
        unset($validated['rates']);

        // Vérification des chevauchements d'intervalles horaires
        for ($i = 0; $i < count($rates); $i++) {
            for ($j = $i + 1; $j < count($rates); $j++) {
                if ($rates[$i]['from_time'] < $rates[$j]['to_time'] && $rates[$j]['from_time'] < $rates[$i]['to_time']) {
                    return back()->withErrors(['rates' => 'Les intervalles ' . ($i + 1) . ' et ' . ($j + 1) . ' se chevauchent.'])->withInput();
                }
            }
        }

        if ($request->hasFile('image')) {
            $validated['image'] = $request->file('image')->store('parkings', 'public');
        }

        $validated['reference']  = $this->generateReference();
        $validated['created_by'] = auth()->id();
        $validated['is_active']  = true;

        $parking = Parking::create($validated);

        foreach ($rates as $rate) {
            $parking->rates()->create([
                'label'      => $rate['label'] ?? null,
                'from_time'  => $rate['from_time'],
                'to_time'    => $rate['to_time'],
                'amount'     => (float) $rate['amount'],
                'created_by' => auth()->id(),
            ]);
        }

        return redirect()->route('parkings.index')->with('success', 'Parking créé avec succès.');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Parking $parking)
    {
        $parking->load(['rates' => fn ($q) => $q->orderBy('from_time')]);

        return Inertia::render('Parking/Create', [
            'parking' => $parking,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Parking $parking)
    {
        $validated = $request->validate([
            'name'              => 'required|string|max:255',
            'address'           => 'required|string',
            'longitude'         => 'nullable|numeric|between:-180,180',
            'latitude'          => 'nullable|numeric|between:-90,90',
            'capacity'          => 'required|integer|min:1',
            'price'             => 'nullable|numeric|min:0',
            'image'             => 'nullable|image|max:2048',
            'rates'             => 'nullable|array',
            'rates.*.label'     => 'nullable|string|max:100',
            'rates.*.from_time' => 'required|date_format:H:i',
            'rates.*.to_time'   => 'required|date_format:H:i|after:rates.*.from_time',
            'rates.*.amount'    => 'required|numeric|min:0',
        ]);

        $rates = $validated['rates'] ?? [];
        unset($validated['rates']);

        // Vérification des chevauchements d'intervalles horaires
        for ($i = 0; $i < count($rates); $i++) {
            for ($j = $i + 1; $j < count($rates); $j++) {
                if ($rates[$i]['from_time'] < $rates[$j]['to_time'] && $rates[$j]['from_time'] < $rates[$i]['to_time']) {
                    return back()->withErrors(['rates' => 'Les intervalles ' . ($i + 1) . ' et ' . ($j + 1) . ' se chevauchent.'])->withInput();
                }
            }
        }

        if ($request->hasFile('image')) {
            if ($parking->image) {
                Storage::disk('public')->delete($parking->image);
            }
            $validated['image'] = $request->file('image')->store('parkings', 'public');
        }

        $parking->update($validated);

        // Sync rates: replace all existing rates with the new set
        $parking->rates()->delete();
        foreach ($rates as $rate) {
            $parking->rates()->create([
                'label'      => $rate['label'] ?? null,
                'from_time'  => $rate['from_time'],
                'to_time'    => $rate['to_time'],
                'amount'     => (float) $rate['amount'],
                'created_by' => auth()->id(),
            ]);
        }

        return redirect()->route('parkings.index')->with('success', 'Parking mis à jour avec succès.');
    }

    /**
     * Toggle the active status of a parking (supervisor / admin only).
     */
    public function toggleStatus(Parking $parking)
    {
        $parking->update(['is_active' => !$parking->is_active]);

        $status = $parking->is_active ? 'activÃ©' : 'dÃ©sactivÃ©';

        return redirect()->route('parkings.index')->with('success', "Parking {$status} avec succÃ¨s.");
    }

    /**
     * Remove the specified resource from storage.
     * Deletion is blocked if the parking has vehicles currently checked in.
     */
    public function destroy(Parking $parking)
    {
        // Block deletion when there are vehicles currently in the parking
        if (method_exists($parking, 'vehicles') && $parking->vehicles()->where('status', 'waiting')->exists()) {
            return redirect()->route('parkings.index')
                ->with('error', 'Impossible de supprimer ce parking : il contient des vÃ©hicules en attente.');
        }

        if ($parking->image) {
            Storage::disk('public')->delete($parking->image);
        }
        $parking->delete();

        return redirect()->route('parkings.index')->with('success', 'Parking supprimÃ© avec succÃ¨s.');
    }

    /**
     * Generate a unique parking reference (e.g. PKG-A3F7K2).
     */
    private function generateReference(): string
    {
        do {
            $reference = 'PKG-' . strtoupper(Str::random(6));
        } while (Parking::where('reference', $reference)->exists());

        return $reference;
    }
}

    {
        $parkings = Parking::with('creator')->latest()->get();

        return Inertia::render('Parking/Index', [
            'parkings' => $parkings
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
