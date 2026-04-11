<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            UserSeeder::class,
            ProjectSeeder::class,
            RequirementSeeder::class,
            BrDependencySeeder::class,
            TestCaseSeeder::class,
            CapacitySeeder::class,
        ]);
    }
}
