<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Mail\InvitationMail;
use App\Models\Caissier;
use App\Models\Parking;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Inertia\Inertia;



class CaissierController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $caissiers = User::where('role', User::ROLE_CAISSIER)
            ->with('caissier.creator')
            ->latest()
            ->get()
            ->map(fn ($user) => array_merge($user->toArray(), ['has_password' => ! is_null($user->password)]));

        return Inertia::render('Admin/Caissier/Index', [
            'caissiers' => $caissiers
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return Inertia::render('Admin/Caissier/Create', [
            'parkings' => $this->activeParkings(),
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name'                   => 'required|string|max:255',
            'first_name'             => 'required|string|max:255',
            'email'                  => 'required|email|unique:users,email',
            'phone'                  => 'nullable|string|max:20',
            'address'                => 'nullable|string',
            'emergency_contact'      => 'nullable|string|max:20',
            'emergency_relationship' => 'nullable|string|max:50',
            'emergency_name'         => 'nullable|string|max:255',
            'parking_ids'            => 'nullable|array|max:1',
            'parking_ids.*'          => 'exists:parkings,id',
        ]);

        $token = Str::random(64);

        $user = User::create([
            'name'                   => $request->name,
            'first_name'             => $request->first_name,
            'email'                  => $request->email,
            'password'               => null,
            'role'                   => User::ROLE_CAISSIER,
            'phone'                  => $request->phone,
            'address'                => $request->address,
            'emergency_contact'      => $request->emergency_contact,
            'emergency_relationship' => $request->emergency_relationship,
            'emergency_name'         => $request->emergency_name,
            'invitation_token'       => $token,
            'invitation_expires_at'  => now()->addHours(48),
        ]);

        Caissier::create([
            'user_id'    => $user->id,
            'created_by' => auth()->id(),
        ]);

        $user->parkings()->sync($request->parking_ids ?? []);

        Mail::to($user->email)->send(new InvitationMail($user));

        Log::info('Caissier créé', [
            'user_id'    => $user->id,
            'email'      => $user->email,
            'created_by' => auth()->id(),
        ]);

        return redirect()->route('admin.caissier.index')
            ->with('success', 'Caissier créé. Un email d\'invitation a été envoyé.');
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $caissier = User::with('caissier.creator')->findOrFail($id);

        return Inertia::render('Admin/Caissier/Show', [
            'caissier' => $caissier,
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        $caissier = User::with('parkings')->findOrFail($id);

        return Inertia::render('Admin/Caissier/Edit', [
            'caissier' => $caissier,
            'parkings' => $this->activeParkings(),
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $caissier = User::findOrFail($id);

        $request->validate([
            'name'                   => 'required|string|max:255',
            'first_name'             => 'required|string|max:255',
            'email'                  => 'required|email|unique:users,email,' . $caissier->id,
            'phone'                  => 'nullable|string|max:20',
            'address'                => 'nullable|string',
            'emergency_contact'      => 'nullable|string|max:20',
            'emergency_relationship' => 'nullable|string|max:50',
            'emergency_name'         => 'nullable|string|max:255',
        ]);

        $caissier->update($request->only([
            'name', 'first_name', 'email', 'phone', 'address',
            'emergency_contact', 'emergency_relationship', 'emergency_name',
        ]));

        $caissier->parkings()->sync($request->parking_ids ?? []);

        Log::info('Caissier mis à jour', [
            'user_id'    => $caissier->id,
            'updated_by' => auth()->id(),
        ]);

        return redirect()->route('admin.caissier.index')->with('success', 'Caissier mis à jour avec succès.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $caissier = User::findOrFail($id);
        Log::info('Caissier supprimé', [
            'user_id'    => $caissier->id,
            'email'      => $caissier->email,
            'deleted_by' => auth()->id(),
        ]);
        $caissier->caissier()->delete();
        $caissier->delete();

        return redirect()->route('admin.caissier.index')->with('success', 'Caissier supprimé avec succès.');
    }

    public function resendInvitation(string $id)
    {
        $caissier = User::findOrFail($id);
        $token = Str::random(64);

        $caissier->update([
            'invitation_token'      => $token,
            'invitation_expires_at' => now()->addHours(48),
        ]);

        Mail::to($caissier->email)->send(new InvitationMail($caissier));

        Log::info('Invitation renvoyée (caissier)', [
            'user_id' => $caissier->id,
            'email'   => $caissier->email,
            'by'      => auth()->id(),
        ]);

        return redirect()->route('admin.caissier.index')
            ->with('success', 'Email d\'invitation renvoyé à ' . $caissier->email . '.');
    }

    private function activeParkings(): array
    {
        return Parking::where('is_active', true)
            ->get(['id', 'name', 'reference', 'capacity'])
            ->map(fn ($p) => [
                'id'              => $p->id,
                'name'            => $p->name,
                'reference'       => $p->reference,
                'capacity'        => $p->capacity,
                'available_spots' => $p->available_spots,
            ])->toArray();
    }
}
