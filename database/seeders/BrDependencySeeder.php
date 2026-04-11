<?php

namespace Database\Seeders;

use App\Models\BusinessRequirement;
use App\Models\Project;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class BrDependencySeeder extends Seeder
{
    public function run(): void
    {
        $admin     = User::where('email', 'admin@rtm.test')->first();
        $ecommerce = Project::where('name', 'E-Commerce Platform')->first();

        $brs = BusinessRequirement::where('project_id', $ecommerce->id)
            ->orderBy('number')
            ->pluck('id', 'number');

        // BR-001 (Auth) blocks BR-003 (Checkout) — must log in before checking out
        // BR-002 (Catalog) blocks BR-003 (Checkout) — must browse products before checking out
        // This creates a diamond: BR1 ──┐
        //                               ├──▶ BR3 (Blocked)
        //                        BR2 ──┘
        // BR-004 (Performance) is intentionally left unlinked to populate the "Unlinked" list
        $links = [
            ['blocking' => 1, 'blocked' => 3],
            ['blocking' => 2, 'blocked' => 3],
        ];

        foreach ($links as $link) {
            DB::table('br_dependencies')->insertOrIgnore([
                'blocking_br_id' => $brs[$link['blocking']],
                'blocked_br_id'  => $brs[$link['blocked']],
                'created_by'     => $admin->id,
                'created_at'     => now(),
                'updated_at'     => now(),
            ]);
        }
    }
}
