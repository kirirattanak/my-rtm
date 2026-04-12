<?php

namespace Database\Seeders;

use App\Enums\ProjectStatus;
use App\Models\Organization;
use App\Models\Project;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;

class ProjectSeeder extends Seeder
{
    public function run(): void
    {
        $acme  = Organization::where('slug', 'acme')->first();
        $pm    = User::where('email', 'pm@rtm.test')->first();
        $ba    = User::where('email', 'ba@rtm.test')->first();
        $dev   = User::where('email', 'dev@rtm.test')->first();
        $tester = User::where('email', 'tester@rtm.test')->first();
        $viewer = User::where('email', 'viewer@rtm.test')->first();

        $roles = Role::pluck('id', 'slug');

        // Project 1 — Active
        $ecommerce = Project::create([
            'name'            => 'E-Commerce Platform',
            'description'     => 'Full redesign of the customer-facing shop including checkout, product catalog, and order management.',
            'status'          => ProjectStatus::Active,
            'owner_id'        => $pm->id,
            'organization_id' => $acme?->id,
            'start_date'      => '2026-03-01',
            'target_date'     => '2026-09-30',
        ]);

        $ecommerce->projectMembers()->createMany([
            ['user_id' => $pm->id,     'role_id' => $roles['project_manager']],
            ['user_id' => $ba->id,     'role_id' => $roles['business_analyst']],
            ['user_id' => $dev->id,    'role_id' => $roles['developer']],
            ['user_id' => $tester->id, 'role_id' => $roles['tester']],
            ['user_id' => $viewer->id, 'role_id' => $roles['viewer']],
        ]);

        // Project 2 — On Hold
        $banking = Project::create([
            'name'            => 'Mobile Banking App',
            'description'     => 'Native mobile application for retail banking customers with account management and transfers.',
            'status'          => ProjectStatus::OnHold,
            'owner_id'        => $pm->id,
            'organization_id' => $acme?->id,
            'start_date'      => '2026-01-15',
            'target_date'     => '2026-12-31',
        ]);

        $banking->projectMembers()->createMany([
            ['user_id' => $pm->id,     'role_id' => $roles['project_manager']],
            ['user_id' => $dev->id,    'role_id' => $roles['developer']],
            ['user_id' => $tester->id, 'role_id' => $roles['tester']],
        ]);

        // Project 3 — Active
        $hr = Project::create([
            'name'            => 'HR Portal',
            'description'     => 'Internal HR management system for onboarding, leave management, and performance reviews.',
            'status'          => ProjectStatus::Active,
            'owner_id'        => $pm->id,
            'organization_id' => $acme?->id,
            'start_date'      => '2026-02-01',
            'target_date'     => '2026-07-31',
        ]);

        $hr->projectMembers()->createMany([
            ['user_id' => $pm->id,  'role_id' => $roles['project_manager']],
            ['user_id' => $ba->id,  'role_id' => $roles['business_analyst']],
            ['user_id' => $dev->id, 'role_id' => $roles['developer']],
        ]);
    }
}
