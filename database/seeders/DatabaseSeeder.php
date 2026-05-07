<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
    // Order matters: SuperAdmin creates Admins; AdminSeeder creates supervisors/agents/caissiers;
    // SupervisorSeeder then creates additional agents/caissiers for supervisors.
        $this->call([
            SuperAdminSeeder::class,
            // AdminSeeder::class,
            // SupervisorSeeder::class,
        ]);

        echo "Database seeding terminé (SuperAdmin -> Admins -> Supervisors/Agents/Caissiers).\n";
    }
}
