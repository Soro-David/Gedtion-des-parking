<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Admin;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;

class AdminController extends Controller
{
    /**
     * Display a listing of the admins.
     */
    public function index()
    {
        $admins = User::where('role', User::ROLE_ADMIN)
            ->with('admin')
            ->get();

        return Inertia::render('Admin/Admins/Index', [
            'admins' => $admins,
        ]);
    }

    /**
     * Show the form for creating a new admin.
     */
    public function create()
    {
        return Inertia::render('Admin/Admins/Create');
    }

    /**
     * Store a newly created admin in storage.
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
            // The superadmin who creates this admin
            'created_by' => auth()->id(),
        ]);

        return redirect()->route('admin.admins.index')
            ->with('success', 'Admin créé avec succès.');
    }

    /**
     * Show the form for editing the specified admin.
     */
    public function edit(User $admin)
    {
        // Ensure the user is an admin
        abort_if($admin->role !== User::ROLE_ADMIN, 404);

        return Inertia::render('Admin/Admins/Edit', [
            'admin' => $admin,
        ]);
    }

    /**
     * Update the specified admin in storage.
     */
    public function update(Request $request, User $admin)
    {
        abort_if($admin->role !== User::ROLE_ADMIN, 404);

        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email,' . $admin->id,
            'password' => 'nullable|string|min:8|confirmed',
        ]);

        $admin->name = $request->name;
        $admin->email = $request->email;
        if ($request->filled('password')) {
            $admin->password = Hash::make($request->password);
        }
        $admin->save();

        return redirect()->route('admin.admins.index')
            ->with('success', 'Admin mis à jour avec succès.');
    }

    /**
     * Remove the specified admin from storage.
     */
    public function destroy(User $admin)
    {
        abort_if($admin->role !== User::ROLE_ADMIN, 404);
        $admin->delete();

        return redirect()->route('admin.admins.index')
            ->with('success', 'Admin supprimé avec succès.');
    }
}
