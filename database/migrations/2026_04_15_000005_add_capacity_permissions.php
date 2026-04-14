<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Insert the two new permission keys
        DB::table('permissions')->insert([
            ['key' => 'capacity.view',   'label' => 'View PERT Estimates & Sprint Capacity', 'group' => 'Capacity Planning', 'sort_order' => 130],
            ['key' => 'capacity.manage', 'label' => 'Manage PERT Estimates & Team Capacity', 'group' => 'Capacity Planning', 'sort_order' => 131],
        ]);

        $perm = DB::table('permissions')->pluck('id', 'key');

        // capacity.view → PM(2), BA(3), Developer(4), Tester(5)
        // capacity.manage → PM(2), BA(3)
        $inserts = [
            ['role_id' => 2, 'permission_id' => $perm['capacity.view']],
            ['role_id' => 2, 'permission_id' => $perm['capacity.manage']],
            ['role_id' => 3, 'permission_id' => $perm['capacity.view']],
            ['role_id' => 3, 'permission_id' => $perm['capacity.manage']],
            ['role_id' => 4, 'permission_id' => $perm['capacity.view']],
            ['role_id' => 5, 'permission_id' => $perm['capacity.view']],
        ];

        DB::table('role_permissions')->insert($inserts);
    }

    public function down(): void
    {
        $perm = DB::table('permissions')->pluck('id', 'key');

        foreach (['capacity.view', 'capacity.manage'] as $key) {
            if ($perm->has($key)) {
                DB::table('role_permissions')->where('permission_id', $perm[$key])->delete();
                DB::table('permissions')->where('key', $key)->delete();
            }
        }
    }
};
