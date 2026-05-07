<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Supervisor;
use App\Models\ParkingAttendant;
use App\Models\Caissier;

class AdminSeeder extends Seeder
{
    /**
     * For each admin user, create supervisors, attendants (agents) and caissiers.
     */
    public function run(): void
    {
        $admins = User::where('role', User::ROLE_ADMIN)->get();

        foreach ($admins as $admin) {
            // Create one supervisor per admin if none exists
            $supervisorUser = User::factory()->create(['role' => User::ROLE_SUPERVISOR]);
            Supervisor::firstOrCreate([
                'user_id' => $supervisorUser->id,
                'created_by' => $admin->id,
            ]);

            // Create one parking attendant (agent) created by this admin
            $attendantUser = User::factory()->create(['role' => User::ROLE_ATTENDANT]);
            ParkingAttendant::firstOrCreate([
                'user_id' => $attendantUser->id,
                'created_by' => $admin->id,
            ]);

            // Create one caissier (cashier) created by this admin
            $caissierUser = User::factory()->create(['role' => User::ROLE_CAISSIER]);
            Caissier::firstOrCreate([
                'user_id' => $caissierUser->id,
                'created_by' => $admin->id,
            ]);
        }
    }
}
