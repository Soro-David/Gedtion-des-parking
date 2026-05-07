<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Mail\InvitationMail;
use App\Models\User;
use App\Models\Supervisor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
// use Illuminate\Support\Facades\Str;
use Illuminate\Support\Str;
use Inertia\Inertia;

class SuperviseurController extends Controller
{
    public function index()
    {
        $supervisors = User::where('role', User::ROLE_SUPERVISOR)
            ->with('supervisor.creator')
            ->latest()
            ->get()
            ->map(fn ($user) => array_merge($user->toArray(), ['has_password' => ! is_null($user->password)]));

        return Inertia::render('Admin/Superviseur/Index', [
            'supervisors' => $supervisors
        ]);
    }

    public function create()
    {
        $parkings = \App\Models\Parking::where('is_active', true)
            ->get(['id', 'name', 'reference', 'capacity'])
            ->map(fn ($p) => [
                'id'              => $p->id,
                'name'            => $p->name,
                'reference'       => $p->reference,
                'capacity'        => $p->capacity,
                'available_spots' => $p->available_spots,
            ])->toArray();

        return Inertia::render('Admin/Superviseur/Create', [
            'parkings' => $parkings,
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
            'parking_ids'            => 'nullable|array',
            'parking_ids.*'          => 'exists:parkings,id',
        ]);

        $token = Str::random(64);

        $user = User::create([
            'name'                   => $request->name,
            'first_name'             => $request->first_name,
            'email'                  => $request->email,
            'password'               => null,
            'role'                   => User::ROLE_SUPERVISOR,
            'phone'                  => $request->phone,
            'address'                => $request->address,
            'emergency_contact'      => $request->emergency_contact,
            'emergency_relationship' => $request->emergency_relationship,
            'emergency_name'         => $request->emergency_name,
            'invitation_token'       => $token,
            'invitation_expires_at'  => now()->addHours(48),
        ]);

        Supervisor::create([
            'user_id'    => $user->id,
            'created_by' => auth()->id(),
        ]);

        $user->parkings()->sync($request->parking_ids ?? []);

        Mail::to($user->email)->send(new InvitationMail($user));

        return redirect()->route('admin.superviseur.index')
            ->with('success', 'Superviseur créé. Un email d\'invitation a été envoyé.');
    }

    public function show(string $id)
    {
        $supervisor = User::with('supervisor.creator')->findOrFail($id);

        return Inertia::render('Admin/Superviseur/Show', [
            'supervisor' => $supervisor,
        ]);
    }

    public function edit(string $id)
    {
        $supervisor = User::findOrFail($id);

        return Inertia::render('Admin/Superviseur/Edit', [
            'supervisor' => $supervisor,
        ]);
    }

    public function update(Request $request, string $id)
    {
        $supervisor = User::findOrFail($id);

        $request->validate([
            'name'                   => 'required|string|max:255',
            'first_name'             => 'required|string|max:255',
            'email'                  => 'required|string|email|max:255|unique:users,email,' . $supervisor->id,
            'phone'                  => 'nullable|string|max:20',
            'address'                => 'nullable|string',
            'emergency_contact'      => 'nullable|string|max:20',
            'emergency_relationship' => 'nullable|string|max:50',
            'emergency_name'         => 'nullable|string|max:255',
        ]);

        $supervisor->update($request->only([
            'name', 'first_name', 'email', 'phone', 'address',
            'emergency_contact', 'emergency_relationship', 'emergency_name',
        ]));

        return redirect()->route('admin.superviseur.index')
            ->with('success', 'Superviseur mis à jour avec succès.');
    }

    public function destroy(string $id)
    {
        $supervisor = User::findOrFail($id);
        $supervisor->supervisor()->delete();
        $supervisor->delete();

        return redirect()->route('admin.superviseur.index')
            ->with('success', 'Superviseur supprimé avec succès.');
    }

    public function resendInvitation(string $id)
    {
        $supervisor = User::findOrFail($id);
        $token = Str::random(64);

        $supervisor->update([
            'invitation_token'      => $token,
            'invitation_expires_at' => now()->addHours(48),
        ]);

        Mail::to($supervisor->email)->send(new InvitationMail($supervisor));

        return redirect()->route('admin.superviseur.index')
            ->with('success', 'Email d\'invitation renvoyé à ' . $supervisor->email . '.');
    }
}
