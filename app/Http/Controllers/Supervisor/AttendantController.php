<?php

namespace App\Http\Controllers\Supervisor;

use App\Http\Controllers\Controller;
use App\Mail\InvitationMail;
use App\Models\Parking;
use App\Models\User;
use App\Models\ParkingAttendant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Str;
use Inertia\Inertia;

class AttendantController extends Controller
{
    public function index()
    {
        $supervisorId = auth()->user()?->id;

        $attendants = User::where('role', User::ROLE_ATTENDANT)
            ->whereHas('attendant', fn ($q) => $q->where('created_by', $supervisorId))
            ->with('attendant.creator')
            ->latest()
            ->get();

        return Inertia::render('Supervisor/Attendants/Index', [
            'attendants' => $attendants,
        ]);
    }

    public function create()
    {
        return Inertia::render('Supervisor/Attendants/Create', [
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
            'role'                   => User::ROLE_ATTENDANT,
            'phone'                  => $request->phone,
            'address'                => $request->address,
            'emergency_contact'      => $request->emergency_contact,
            'emergency_relationship' => $request->emergency_relationship,
            'emergency_name'         => $request->emergency_name,
            'invitation_token'       => $token,
            'invitation_expires_at'  => now()->addHours(48),
        ]);

        ParkingAttendant::create([
            'user_id'    => $user->id,
            'created_by' => auth()->id(),
        ]);

        $user->parkings()->sync($request->parking_ids ?? []);

        Mail::to($user->email)->send(new InvitationMail($user));

        Log::info('Agent créé (superviseur)', [
            'user_id'    => $user->id,
            'email'      => $user->email,
            'created_by' => auth()->id(),
        ]);

        return redirect()->route('supervisor.attendants.index')
            ->with('success', 'Agent créé. Un email d\'invitation a été envoyé.');
    }

    public function edit(User $attendant)
    {
        $attendant->load('parkings');

        return Inertia::render('Supervisor/Attendants/Edit', [
            'attendant' => $attendant,
            'parkings'  => $this->activeParkings(),
        ]);
    }

    public function update(Request $request, User $attendant)
    {
        $request->validate([
            'name'                   => 'required|string|max:255',
            'first_name'             => 'required|string|max:255',
            'email'                  => 'required|string|email|max:255|unique:users,email,' . $attendant->id,
            'phone'                  => 'nullable|string|max:20',
            'address'                => 'nullable|string',
            'emergency_contact'      => 'nullable|string|max:20',
            'emergency_relationship' => 'nullable|string|max:50',
            'emergency_name'         => 'nullable|string|max:255',
            'parking_ids'            => 'nullable|array|max:1',
            'parking_ids.*'          => 'exists:parkings,id',
        ]);

        $attendant->update($request->only([
            'name', 'first_name', 'email', 'phone', 'address',
            'emergency_contact', 'emergency_relationship', 'emergency_name',
        ]));

        $attendant->parkings()->sync($request->parking_ids ?? []);

        Log::info('Agent mis à jour (superviseur)', [
            'user_id'    => $attendant->id,
            'updated_by' => auth()->id(),
        ]);

        return redirect()->route('supervisor.attendants.index')
            ->with('success', 'Agent mis à jour avec succès.');
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
