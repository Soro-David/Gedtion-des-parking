<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\ParkingAttendant;
use App\Models\Caissier;

class SupervisorSeeder extends Seeder
{
    /**
     * Supervisors add agents (attendants) and caissiers.
     */
    public function run(): void
    {
        $supervisors = User::where('role', User::ROLE_SUPERVISOR)->get();

        foreach ($supervisors as $supervisor) {
            // create an attendant (agent) by supervisor
            $attendantUser = User::factory()->create(['role' => User::ROLE_ATTENDANT]);
            ParkingAttendant::firstOrCreate([
                'user_id' => $attendantUser->id,
                'created_by' => $supervisor->id,
            ]);

            // create a caissier by supervisor
            $caissierUser = User::factory()->create(['role' => User::ROLE_CAISSIER]);
            Caissier::firstOrCreate([
                'user_id' => $caissierUser->id,
                'created_by' => $supervisor->id,
            ]);
        }
    }
}
