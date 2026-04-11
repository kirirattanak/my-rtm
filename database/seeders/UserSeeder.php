<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // Admin
        User::factory()->admin()->create([
            'name'  => 'Admin',
            'email' => 'admin@rtm.test',
        ]);

        // One of each role for development use
        User::factory()->projectManager()->create([
            'name'  => 'Project Manager',
            'email' => 'pm@rtm.test',
        ]);

        User::factory()->businessAnalyst()->create([
            'name'  => 'Business Analyst',
            'email' => 'ba@rtm.test',
        ]);

        User::factory()->developer()->create([
            'name'  => 'Developer',
            'email' => 'dev@rtm.test',
        ]);

        User::factory()->tester()->create([
            'name'  => 'Tester',
            'email' => 'tester@rtm.test',
        ]);

        User::factory()->create([
            'name'  => 'Viewer',
            'email' => 'viewer@rtm.test',
        ]);
    }
}
