<?php

namespace App\Http\Controllers\Supervisor;

use App\Http\Controllers\Controller;
use App\Mail\InvitationMail;
use App\Models\Caissier;
use App\Models\Parking;
use App\Models\ParkingAttendant;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Inertia\Inertia;

class UserController extends Controller
{
    /**
     * Liste unifiée des agents et caissiers gérés par ce superviseur.
     */
    public function index()
    {
        $supervisorId = auth()->id();

        $attendants = User::where('role', User::ROLE_ATTENDANT)
            ->whereHas('attendant', fn ($q) => $q->where('created_by', $supervisorId))
            ->latest()
            ->get()
            ->map(fn ($u) => array_merge($u->toArray(), [
                'has_password' => ! is_null($u->password),
            ]));

        $caissiers = User::where('role', User::ROLE_CAISSIER)
            ->whereHas('caissier', fn ($q) => $q->where('created_by', $supervisorId))
            ->latest()
            ->get()
            ->map(fn ($u) => array_merge($u->toArray(), [
                'has_password' => ! is_null($u->password),
            ]));

        $users = $attendants->concat($caissiers)->sortByDesc('created_at')->values();

        return Inertia::render('Supervisor/Users/Index', [
            'users'    => $users,
            'parkings' => $this->supervisorParkings(),
        ]);
    }

    /**
     * Créer un agent ou caissier sous ce superviseur.
     */
    public function store(Request $request)
    {
        $request->validate([
            'role'                   => 'required|in:attendant,caissier',
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
            User::ROLE_ATTENDANT => ParkingAttendant::create(['user_id' => $user->id, 'created_by' => auth()->id()]),
            User::ROLE_CAISSIER  => Caissier::create(['user_id' => $user->id, 'created_by' => auth()->id()]),
        };

        $user->parkings()->sync($request->parking_ids ?? []);

        Mail::to($user->email)->send(new InvitationMail($user));

        Log::info('Utilisateur créé (superviseur unifié)', [
            'user_id'    => $user->id,
            'role'       => $user->role,
            'created_by' => auth()->id(),
        ]);

        return back()->with('success', 'Utilisateur créé. Un email d\'invitation a été envoyé.');
    }

    public function destroy(User $user)
    {
        abort_if(
            ! in_array($user->role, [User::ROLE_ATTENDANT, User::ROLE_CAISSIER]),
            404
        );

        match ($user->role) {
            User::ROLE_ATTENDANT => $user->attendant()->delete(),
            User::ROLE_CAISSIER  => $user->caissier()->delete(),
        };

        Log::info('Utilisateur supprimé (unifié superviseur)', [
            'user_id'    => $user->id,
            'role'       => $user->role,
            'deleted_by' => auth()->id(),
        ]);

        $user->delete();

        return redirect()->route('supervisor.users.index')
            ->with('success', 'Utilisateur supprimé avec succès.');
    }

    /**
     * Parkings affectés au superviseur connecté.
     * Fallback : tous les parkings actifs si aucun n'est assigné.
     */
    private function supervisorParkings(): array
    {
        $supervisor = auth()->user();

        // Parkings assignés via user_parkings
        $assigned = $supervisor->parkings()->where('is_active', true)->get();

        $parkings = $assigned->isNotEmpty()
            ? $assigned
            : Parking::where('is_active', true)->get();

        return $parkings->map(fn ($p) => [
            'id'              => $p->id,
            'name'            => $p->name,
            'reference'       => $p->reference,
            'capacity'        => $p->capacity,
            'available_spots' => $p->available_spots,
        ])->toArray();
    }
}
