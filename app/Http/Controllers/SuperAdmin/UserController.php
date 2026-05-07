<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Mail\InvitationMail;
use App\Models\Admin;
use App\Models\Parking;
use App\Models\Supervisor;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Inertia\Inertia;

class UserController extends Controller
{
    /**
     * Liste unifiée : Admins + Superviseurs.
     */
    public function index()
    {
        if (Schema::hasColumn('admins', 'blocked')) {
            $admins = User::where('role', User::ROLE_ADMIN)
                ->with('admin.creator')
                ->leftJoin('admins', 'admins.user_id', '=', 'users.id')
                ->select('users.*')
                ->orderByRaw('COALESCE(admins.blocked, 0) ASC')
                ->orderBy('users.created_at', 'desc')
                ->get()
                ->map(fn ($u) => array_merge($u->toArray(), [
                    'has_password' => ! is_null($u->password),
                ]));
        } else {
            $admins = User::where('role', User::ROLE_ADMIN)
                ->with('admin.creator')
                ->orderBy('created_at', 'desc')
                ->get()
                ->map(fn ($u) => array_merge($u->toArray(), [
                    'has_password' => ! is_null($u->password),
                ]));
        }

        $supervisors = User::where('role', User::ROLE_SUPERVISOR)
            ->with(['supervisor.creator', 'parkings'])
            ->latest()
            ->get()
            ->map(fn ($u) => array_merge($u->toArray(), [
                'has_password' => ! is_null($u->password),
                'parking_ids'  => $u->parkings->pluck('id')->toArray(),
            ]));

        $users = $admins->concat($supervisors)->sortByDesc('created_at')->values();

        return Inertia::render('SuperAdmin/Users/Index', [
            'users'    => $users,
            'parkings' => $this->allActiveParkings(),
        ]);
    }

    /**
     * Créer un Admin ou un Superviseur (avec parkings pour le superviseur).
     */
    public function store(Request $request)
    {
        $request->validate([
            'role'                   => 'required|in:admin,supervisor',
            'name'                   => 'required|string|max:255',
            'first_name'             => 'required|string|max:255',
            'email'                  => 'required|string|email|max:255|unique:users',
            'phone'                  => 'nullable|string|max:20',
            'address'                => 'nullable|string|max:255',
            'parking_ids'            => 'nullable|array',
            'parking_ids.*'          => 'exists:parkings,id',
        ]);

        $token = Str::random(64);

        $user = User::create([
            'name'                  => $request->name,
            'first_name'            => $request->first_name,
            'email'                 => $request->email,
            'password'              => null,
            'role'                  => $request->role,
            'phone'                 => $request->phone,
            'address'               => $request->address,
            'invitation_token'      => $token,
            'invitation_expires_at' => now()->addHours(48),
        ]);

        match ($request->role) {
            User::ROLE_ADMIN      => Admin::create(['user_id' => $user->id, 'created_by' => auth()->id(), 'blocked' => false]),
            User::ROLE_SUPERVISOR => Supervisor::create(['user_id' => $user->id, 'created_by' => auth()->id()]),
        };

        // Affecter les parkings au superviseur
        if ($request->role === User::ROLE_SUPERVISOR) {
            $user->parkings()->sync($request->parking_ids ?? []);
        }

        Mail::to($user->email)->send(new InvitationMail($user));

        Log::info('Utilisateur créé (super admin unifié)', [
            'user_id'    => $user->id,
            'role'       => $user->role,
            'created_by' => auth()->id(),
        ]);

        return back()->with('success', ucfirst($request->role === 'admin' ? 'Admin' : 'Superviseur') . ' créé. Un email d\'invitation a été envoyé.');
    }

    public function destroy(User $user)
    {
        abort_if(
            ! in_array($user->role, [User::ROLE_ADMIN, User::ROLE_SUPERVISOR]),
            404
        );

        match ($user->role) {
            User::ROLE_ADMIN      => Admin::where('user_id', $user->id)->delete(),
            User::ROLE_SUPERVISOR => $user->supervisor()->delete(),
        };

        Log::info('Utilisateur supprimé (unifié super admin)', [
            'user_id'    => $user->id,
            'role'       => $user->role,
            'deleted_by' => auth()->id(),
        ]);

        $user->delete();

        return redirect()->route('superadmin.users.index')
            ->with('success', 'Utilisateur supprimé.');
    }

    private function allActiveParkings(): array
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
