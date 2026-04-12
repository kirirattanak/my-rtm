<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class OrganizationSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::where('email', 'admin@rtm.test')->first();
        $pmRole = Role::where('slug', 'project_manager')->first();

        $proHundred    = DB::table('subscription_tier_options')->where('tier', 'pro')->where('seats', 100)->value('id');
        $starterFifty  = DB::table('subscription_tier_options')->where('tier', 'starter')->where('seats', 50)->value('id');

        // ── Acme Corp — Pro · 100 seats (default dev org) ───────────────────
        // The seeded admin user is the Acme owner; no extra owner account needed.
        $acmeId = DB::table('organizations')->insertGetId([
            'name'       => 'Acme Corp',
            'slug'       => 'acme',
            'owner_id'   => $admin?->id,
            'is_active'  => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('organization_subscriptions')->insert([
            'organization_id' => $acmeId,
            'tier_option_id'  => $proHundred,
            'status'          => 'active',
            'trial_ends_at'   => null,
            'starts_at'       => now(),
            'ends_at'         => null,
            'created_at'      => now(),
            'updated_at'      => now(),
        ]);

        // ── Beta Inc — Starter · 50 seats (trial) ───────────────────────────
        // Create a dedicated owner account for Beta Inc.
        $betaOwner = User::create([
            'name'            => 'Beta Inc Owner',
            'email'           => 'owner@beta.example.com',
            'password'        => Hash::make('password'),
            'role_id'         => $pmRole?->id,
            'is_active'       => true,
            'email_verified_at' => now(),
        ]);

        $betaId = DB::table('organizations')->insertGetId([
            'name'       => 'Beta Inc',
            'slug'       => 'beta',
            'owner_id'   => $betaOwner->id,
            'is_active'  => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $betaOwner->update(['organization_id' => $betaId]);

        DB::table('organization_subscriptions')->insert([
            'organization_id' => $betaId,
            'tier_option_id'  => $starterFifty,
            'status'          => 'trial',
            'trial_ends_at'   => now()->addDays(14),
            'starts_at'       => now(),
            'ends_at'         => null,
            'created_at'      => now(),
            'updated_at'      => now(),
        ]);

        // ── Backfill: assign remaining users + projects to Acme Corp ─────────
        DB::table('users')->whereNull('organization_id')->update(['organization_id' => $acmeId]);
        DB::table('projects')->whereNull('organization_id')->update(['organization_id' => $acmeId]);
    }
}
