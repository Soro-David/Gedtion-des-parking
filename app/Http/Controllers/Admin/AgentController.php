<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Mail\InvitationMail;
use App\Models\Parking;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Inertia\Inertia;

class AgentController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $agents = User::where('role', User::ROLE_ATTENDANT)
            ->with('attendant.creator')
            ->latest()
            ->get()
            ->map(fn ($user) => array_merge($user->toArray(), ['has_password' => ! is_null($user->password)]));

        return Inertia::render('Admin/Attendant/Index', [
            'agents' => $agents
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return Inertia::render('Admin/Attendant/Create', [
            'parkings' => $this->activeParkings(),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
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
            'role'                   => User::ROLE_ATTENDANT,
            'phone'                  => $request->phone,
            'address'                => $request->address,
            'emergency_contact'      => $request->emergency_contact,
            'emergency_relationship' => $request->emergency_relationship,
            'emergency_name'         => $request->emergency_name,
            'invitation_token'       => $token,
            'invitation_expires_at'  => now()->addHours(48),
        ]);

        \App\Models\ParkingAttendant::create([
            'user_id'    => $user->id,
            'created_by' => auth()->id(),
        ]);

        $user->parkings()->sync($request->parking_ids ?? []);

        Mail::to($user->email)->send(new InvitationMail($user));

        Log::info('Agent créé', [
            'user_id'    => $user->id,
            'email'      => $user->email,
            'created_by' => auth()->id(),
        ]);

        return redirect()->route('admin.agent.index')->with('success', 'Agent créé. Un email d\'invitation a été envoyé.');
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $agent = User::with('attendant.creator')->findOrFail($id);

        return Inertia::render('Admin/Attendant/Show', [
            'agent' => $agent,
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        $agent = User::with('parkings')->findOrFail($id);

        return Inertia::render('Admin/Attendant/Edit', [
            'agent'    => $agent,
            'parkings' => $this->activeParkings(),
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $agent = User::findOrFail($id);

        $request->validate([
            'name'                   => 'required|string|max:255',
            'first_name'             => 'required|string|max:255',
            'email'                  => 'required|email|unique:users,email,' . $agent->id,
            'phone'                  => 'nullable|string|max:20',
            'address'                => 'nullable|string',
            'emergency_contact'      => 'nullable|string|max:20',
            'emergency_relationship' => 'nullable|string|max:50',
            'emergency_name'         => 'nullable|string|max:255',
        ]);

        $agent->update($request->only([
            'name', 'first_name', 'email', 'phone', 'address',
            'emergency_contact', 'emergency_relationship', 'emergency_name',
        ]));

        $agent->parkings()->sync($request->parking_ids ?? []);

        Log::info('Agent mis à jour', [
            'user_id'    => $agent->id,
            'updated_by' => auth()->id(),
        ]);

        return redirect()->route('admin.agent.index')->with('success', 'Agent mis à jour avec succès.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $agent = User::findOrFail($id);
        Log::info('Agent supprimé', [
            'user_id'    => $agent->id,
            'email'      => $agent->email,
            'deleted_by' => auth()->id(),
        ]);
        $agent->attendant()->delete();
        $agent->delete();

        return redirect()->route('admin.agent.index')->with('success', 'Agent supprimé avec succès.');
    }

    public function resendInvitation(string $id)
    {
        $agent = User::findOrFail($id);
        $token = Str::random(64);

        $agent->update([
            'invitation_token'      => $token,
            'invitation_expires_at' => now()->addHours(48),
        ]);

        Mail::to($agent->email)->send(new InvitationMail($agent));

        Log::info('Invitation renvoyée (agent)', [
            'user_id' => $agent->id,
            'email'   => $agent->email,
            'by'      => auth()->id(),
        ]);

        return redirect()->route('admin.agent.index')
            ->with('success', 'Email d\'invitation renvoyé à ' . $agent->email . '.');
    }

    private function activeParkings(): array
    {
        return Parking::where('is_active', true)
            ->get(['id', 'name', 'reference', 'capacity'])
            ->map(fn ($p) => [
                'id'             => $p->id,
                'name'           => $p->name,
                'reference'      => $p->reference,
                'capacity'       => $p->capacity,
                'available_spots' => $p->available_spots,
            ])->toArray();
    }
}
