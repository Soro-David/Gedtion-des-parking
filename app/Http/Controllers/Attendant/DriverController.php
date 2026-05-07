<?php

namespace App\Http\Controllers\Attendant;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Driver;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;

class DriverController extends Controller
{
    public function index()
    {
        $attendantId = auth()->user()?->id;

        $drivers = User::where('role', User::ROLE_DRIVER)
            ->whereHas('driver', fn ($q) => $q->where('created_by', $attendantId))
            ->with('driver.creator')
            ->latest()
            ->get();

        return Inertia::render('Attendant/Drivers/Index', [
            'drivers' => $drivers,
        ]);
    }

    public function create()
    {
        return Inertia::render('Attendant/Drivers/Create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name'          => 'required|string|max:255',
            'email'         => 'required|string|email|max:255|unique:users',
            'password'      => 'required|string|min:8|confirmed',
            'license_plate' => 'required|string|max:20|unique:drivers',
        ]);

        $user = User::create([
            'name'     => $request->name,
            'email'    => $request->email,
            'password' => Hash::make($request->password),
            'role'     => User::ROLE_DRIVER,
        ]);

        Driver::create([
            'user_id'       => $user->id,
            'created_by'    => auth()->user()?->id,
            'license_plate' => strtoupper($request->license_plate),
        ]);

        return redirect()->route('attendant.drivers.index')
            ->with('success', 'Conducteur créé avec succès.');
    }
}
