<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class OrganizationSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::where('email', 'admin@rtm.test')->first();

        $proOption = DB::table('subscription_tier_options')
            ->where('tier', 'pro')->where('seats', 200)->first();

        $starterOption = DB::table('subscription_tier_options')
            ->where('tier', 'starter')->where('seats', 50)->first();

        // ── Acme Corp — Pro · 100 seats (default dev org) ───────────────────
        $acmeId = DB::table('organizations')->insertGetId([
            'name'       => 'Acme Corp',
            'slug'       => 'acme',
            'owner_id'   => $admin?->id,
            'is_active'  => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $proHundred = DB::table('subscription_tier_options')
            ->where('tier', 'pro')->where('seats', 100)->value('id');

        DB::table('organization_subscriptions')->insert([
            'organization_id' => $acmeId,
            'tier_option_id'  => $proHundred ?? $proOption->id,
            'status'          => 'active',
            'trial_ends_at'   => null,
            'starts_at'       => now(),
            'ends_at'         => null,
            'created_at'      => now(),
            'updated_at'      => now(),
        ]);

        // ── Beta Inc — Starter · 50 seats (trial) ───────────────────────────
        $betaId = DB::table('organizations')->insertGetId([
            'name'       => 'Beta Inc',
            'slug'       => 'beta',
            'owner_id'   => null,
            'is_active'  => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('organization_subscriptions')->insert([
            'organization_id' => $betaId,
            'tier_option_id'  => $starterOption->id,
            'status'          => 'trial',
            'trial_ends_at'   => now()->addDays(14),
            'starts_at'       => now(),
            'ends_at'         => null,
            'created_at'      => now(),
            'updated_at'      => now(),
        ]);

        // ── Backfill: assign all existing users + projects to Acme Corp ──────
        DB::table('users')->whereNull('organization_id')->update(['organization_id' => $acmeId]);
        DB::table('projects')->whereNull('organization_id')->update(['organization_id' => $acmeId]);
    }
}
