<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Mail\InvitationMail;
use App\Models\Caissier;
use App\Models\Parking;
use App\Models\ParkingAttendant;
use App\Models\Supervisor;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Inertia\Inertia;

class AdminUserController extends Controller
{
    public function index()
    {
        $roles = [User::ROLE_SUPERVISOR, User::ROLE_ATTENDANT, User::ROLE_CAISSIER];

        $users = User::whereIn('role', $roles)
            ->latest()
            ->get()
            ->map(fn ($user) => array_merge($user->toArray(), [
                'has_password' => ! is_null($user->password),
            ]));

        return Inertia::render('Admin/Users/Index', [
            'users'    => $users,
            'parkings' => $this->activeParkings(),
        ]);
    }

    public function create()
    {
        return Inertia::render('Admin/Users/Create', [
            'parkings' => $this->activeParkings(),
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'role'                   => 'required|in:supervisor,attendant,caissier',
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
            'role'                   => $request->role,
            'phone'                  => $request->phone,
            'address'                => $request->address,
            'emergency_contact'      => $request->emergency_contact,
            'emergency_relationship' => $request->emergency_relationship,
            'emergency_name'         => $request->emergency_name,
            'invitation_token'       => $token,
            'invitation_expires_at'  => now()->addHours(48),
        ]);

        match ($request->role) {
            User::ROLE_SUPERVISOR => Supervisor::create(['user_id' => $user->id, 'created_by' => auth()->id()]),
            User::ROLE_ATTENDANT  => ParkingAttendant::create(['user_id' => $user->id, 'created_by' => auth()->id()]),
            User::ROLE_CAISSIER   => Caissier::create(['user_id' => $user->id, 'created_by' => auth()->id()]),
        };

        if (in_array($request->role, [User::ROLE_SUPERVISOR, User::ROLE_ATTENDANT, User::ROLE_CAISSIER])) {
            $user->parkings()->sync($request->parking_ids ?? []);
        }

        Mail::to($user->email)->send(new InvitationMail($user));

        Log::info('Utilisateur créé (unifié)', [
            'user_id'    => $user->id,
            'role'       => $user->role,
            'created_by' => auth()->id(),
        ]);

        return redirect()->route('admin.users.index')
            ->with('success', 'Utilisateur créé. Un email d\'invitation a été envoyé.');
    }

    public function destroy(User $user)
    {
        abort_if(
            ! in_array($user->role, [User::ROLE_SUPERVISOR, User::ROLE_ATTENDANT, User::ROLE_CAISSIER]),
            404
        );

        match ($user->role) {
            User::ROLE_SUPERVISOR => $user->supervisor()->delete(),
            User::ROLE_ATTENDANT  => $user->attendant()->delete(),
            User::ROLE_CAISSIER   => $user->caissier()->delete(),
        };

        Log::info('Utilisateur supprimé (unifié admin)', [
            'user_id'    => $user->id,
            'role'       => $user->role,
            'deleted_by' => auth()->id(),
        ]);

        $user->delete();

        return redirect()->route('admin.users.index')
            ->with('success', 'Utilisateur supprimé avec succès.');
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
