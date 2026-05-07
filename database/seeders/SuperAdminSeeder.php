<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Admin;
use Illuminate\Support\Facades\Hash;

class SuperAdminSeeder extends Seeder
{
    /**
     * Create a Super Admin and a couple of Admins (as requested: Super Admin adds Admins).
     */
    public function run(): void
    {
        // Create or get the super admin
        $super = User::firstOrCreate(
            ['email' => 'superadmin@parking.com'],
            [
                'name' => 'Super Admin',
                'password' => Hash::make('password'),
                'role' => User::ROLE_SUPERADMIN,
            ]
        );

        // Ensure admin profile exists for the super (keeps parity with Admin model)
        Admin::firstOrCreate(['user_id' => $super->id]);

        // Create two admins that the super admin 'adds'
        // $admins = [
        //     ['name' => 'Admin One', 'email' => 'admin1@parking.com'],
        //     ['name' => 'Admin Two', 'email' => 'admin2@parking.com'],
        // ];

        // foreach ($admins as $a) {
        //     $user = User::firstOrCreate(
        //         ['email' => $a['email']],
        //         [
        //             'name' => $a['name'],
        //             'password' => Hash::make('password'),
        //             'role' => User::ROLE_ADMIN,
        //         ]
        //     );

        //     Admin::firstOrCreate(['user_id' => $user->id]);
        // }
    }
}
