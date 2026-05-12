<?php

namespace App\Http\Controllers\Supervisor;

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
    public function index()
    {
        $supervisorId = auth()->id();

        $caissiers = User::where('role', User::ROLE_CAISSIER)
            ->whereHas('caissier', fn ($q) => $q->where('created_by', $supervisorId))
            ->with('caissier.creator')
            ->latest()
            ->get()
            ->map(fn ($user) => array_merge($user->toArray(), ['has_password' => ! is_null($user->password)]));

        return Inertia::render('Supervisor/Caissiers/Index', [
            'caissiers' => $caissiers,
        ]);
    }

    public function create()
    {
        return Inertia::render('Supervisor/Caissiers/Create', [
            'parkings' => $this->activeParkings(),
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name'                   => 'required|string|max:255',
            'first_name'             => 'required|string|max:255',
            'email'                  => 'required|string|email|max:255|unique:users',
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

        Log::info('Caissier créé (superviseur)', [
            'user_id'    => $user->id,
            'email'      => $user->email,
            'created_by' => auth()->id(),
        ]);

        return redirect()->route('supervisor.caissiers.index')
            ->with('success', 'Caissier créé. Un email d\'invitation a été envoyé.');
    }

    public function show(User $caissier)
    {
        $caissier->load('caissier.creator');

        return Inertia::render('Supervisor/Caissiers/Show', [
            'caissier' => $caissier,
        ]);
    }

    public function edit(User $caissier)
    {
        $caissier->load('parkings');

        return Inertia::render('Supervisor/Caissiers/Edit', [
            'caissier' => $caissier,
            'parkings' => $this->activeParkings(),
        ]);
    }

    public function update(Request $request, User $caissier)
    {
        $request->validate([
            'name'                   => 'required|string|max:255',
            'first_name'             => 'required|string|max:255',
            'email'                  => 'required|string|email|max:255|unique:users,email,' . $caissier->id,
            'phone'                  => 'nullable|string|max:20',
            'address'                => 'nullable|string',
            'emergency_contact'      => 'nullable|string|max:20',
            'emergency_relationship' => 'nullable|string|max:50',
            'emergency_name'         => 'nullable|string|max:255',
            'parking_ids'            => 'nullable|array|max:1',
            'parking_ids.*'          => 'exists:parkings,id',
        ]);

        $caissier->update($request->only([
            'name', 'first_name', 'email', 'phone', 'address',
            'emergency_contact', 'emergency_relationship', 'emergency_name',
        ]));

        $caissier->parkings()->sync($request->parking_ids ?? []);

        return redirect()->route('supervisor.caissiers.index')
            ->with('success', 'Caissier mis à jour avec succès.');
    }

    public function destroy(User $caissier)
    {
        $caissier->caissier()->delete();
        $caissier->delete();

        return redirect()->route('supervisor.caissiers.index')
            ->with('success', 'Caissier supprimé avec succès.');
    }

    public function resendInvitation(User $caissier)
    {
        $token = Str::random(64);

        $caissier->update([
            'invitation_token'      => $token,
            'invitation_expires_at' => now()->addHours(48),
        ]);

        Mail::to($caissier->email)->send(new InvitationMail($caissier));

        return redirect()->route('supervisor.caissiers.index')
            ->with('success', 'Email d\'invitation renvoyé à ' . $caissier->email . '.');
    }

    private function activeParkings(): array
    {
        return auth()->user()->parkings()
            ->where('is_active', true)
            ->get(['parkings.id', 'parkings.name', 'parkings.reference', 'parkings.capacity'])
            ->map(fn ($p) => [
                'id'              => $p->id,
                'name'            => $p->name,
                'reference'       => $p->reference,
                'capacity'        => $p->capacity,
                'available_spots' => $p->available_spots,
            ])->toArray();
    }
}
