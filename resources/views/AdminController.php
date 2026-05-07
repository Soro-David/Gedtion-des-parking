<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Admin;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Inertia\Inertia;

class AdminController extends Controller
{
    /**
     * List admins for SuperAdmin UI
     */
    public function index()
    {
        // If the admins.blocked column exists, keep the ordering that places blocked admins last.
        // If the column is missing (migration not run yet), fall back to a safe query that
        // only orders by creation date so the app doesn't crash with SQL errors.
        if (Schema::hasColumn('admins', 'blocked')) {
            $admins = User::where('role', User::ROLE_ADMIN)
                ->with('admin')
                ->leftJoin('admins', 'admins.user_id', '=', 'users.id')
                ->select('users.*')
                ->orderByRaw('COALESCE(admins.blocked, 0) ASC')
                ->orderBy('users.created_at', 'desc')
                ->get();
        } else {
            $admins = User::where('role', User::ROLE_ADMIN)
                ->with('admin.creator')
                ->orderBy('created_at', 'desc')
                ->get();
        }

        return Inertia::render('SuperAdmin/Admins/Index', [
            'admins' => $admins,
        ]);
    }

    /**
     * Store a new admin created by SuperAdmin
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => User::ROLE_ADMIN,
        ]);

        Admin::create([
            'user_id' => $user->id,
            'created_by' => auth()->id(),
            'blocked' => false,
        ]);

        return redirect()->route('superadmin.index')
            ->with('success', 'Admin créé avec succès.');
    }

    /**
     * Show the form for editing the specified admin.
     */
    public function edit(User $admin)
    {
        abort_if($admin->role !== User::ROLE_ADMIN, 404);

        // load admin relation
        $admin->load('admin.creator');

        return Inertia::render('SuperAdmin/Admins/Edit', [
            'admin' => $admin,
        ]);
    }

    /**
     * Update the specified admin (update details or block/unblock).
     */
    public function update(Request $request, User $admin)
    {
        abort_if($admin->role !== User::ROLE_ADMIN, 404);

        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email,' . $admin->id,
            'blocked' => 'nullable|boolean',
        ]);

        $admin->name = $request->name;
        $admin->email = $request->email;
        $admin->save();

        // Update admin profile blocked flag
        $adminProfile = Admin::firstOrNew(['user_id' => $admin->id]);
        $adminProfile->created_by = $adminProfile->created_by ?? auth()->id();
        $adminProfile->blocked = $request->boolean('blocked');
        $adminProfile->save();

        return redirect()->route('superadmin.admins.index')
            ->with('success', 'Admin mis à jour.');
    }

    /**
     * Remove the specified admin.
     */
    public function destroy(User $admin)
    {
        abort_if($admin->role !== User::ROLE_ADMIN, 404);

        // Delete admin profile first
        Admin::where('user_id', $admin->id)->delete();
        $admin->delete();

        return redirect()->route('superadmin.admins.index')
            ->with('success', 'Admin supprimé.');
    }
}
