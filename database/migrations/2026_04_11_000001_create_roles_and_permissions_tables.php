<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->boolean('is_system')->default(false);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('permissions', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->string('label');
            $table->string('group');
            $table->unsignedSmallInteger('sort_order')->default(0);
        });

        Schema::create('role_permissions', function (Blueprint $table) {
            $table->foreignId('role_id')->constrained()->cascadeOnDelete();
            $table->foreignId('permission_id')->constrained()->cascadeOnDelete();
            $table->primary(['role_id', 'permission_id']);
        });

        // ── Seed built-in roles ──────────────────────────────────────────────
        DB::table('roles')->insert([
            ['id' => 1, 'name' => 'Admin',            'slug' => 'admin',            'is_system' => true,  'created_by' => null, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 2, 'name' => 'Project Manager',  'slug' => 'project_manager',  'is_system' => true,  'created_by' => null, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 3, 'name' => 'Business Analyst', 'slug' => 'business_analyst', 'is_system' => true,  'created_by' => null, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 4, 'name' => 'Developer',        'slug' => 'developer',        'is_system' => true,  'created_by' => null, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 5, 'name' => 'Tester',           'slug' => 'tester',           'is_system' => true,  'created_by' => null, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 6, 'name' => 'Viewer',           'slug' => 'viewer',           'is_system' => true,  'created_by' => null, 'created_at' => now(), 'updated_at' => now()],
        ]);

        // ── Seed permissions ─────────────────────────────────────────────────
        $permissions = [
            // Business Requirements
            ['key' => 'br.view',          'label' => 'View Business Requirements',         'group' => 'Business Requirements', 'sort_order' => 10],
            ['key' => 'br.create',        'label' => 'Create Business Requirements',       'group' => 'Business Requirements', 'sort_order' => 11],
            ['key' => 'br.edit',          'label' => 'Edit Business Requirements',         'group' => 'Business Requirements', 'sort_order' => 12],
            ['key' => 'br.delete',        'label' => 'Delete Business Requirements',       'group' => 'Business Requirements', 'sort_order' => 13],
            ['key' => 'br.change_status', 'label' => 'Change BR Status',                   'group' => 'Business Requirements', 'sort_order' => 14],
            ['key' => 'br.import',        'label' => 'Import Business Requirements (CSV)', 'group' => 'Business Requirements', 'sort_order' => 15],
            ['key' => 'br.export',        'label' => 'Export Business Requirements',       'group' => 'Business Requirements', 'sort_order' => 16],
            // Technical Requirements
            ['key' => 'tr.view',          'label' => 'View Technical Requirements',         'group' => 'Technical Requirements', 'sort_order' => 20],
            ['key' => 'tr.create',        'label' => 'Create Technical Requirements',       'group' => 'Technical Requirements', 'sort_order' => 21],
            ['key' => 'tr.edit',          'label' => 'Edit Technical Requirements',         'group' => 'Technical Requirements', 'sort_order' => 22],
            ['key' => 'tr.delete',        'label' => 'Delete Technical Requirements',       'group' => 'Technical Requirements', 'sort_order' => 23],
            ['key' => 'tr.change_status', 'label' => 'Change TR Status',                   'group' => 'Technical Requirements', 'sort_order' => 24],
            ['key' => 'tr.import',        'label' => 'Import Technical Requirements (CSV)', 'group' => 'Technical Requirements', 'sort_order' => 25],
            ['key' => 'tr.export',        'label' => 'Export Technical Requirements',       'group' => 'Technical Requirements', 'sort_order' => 26],
            // Test Cases
            ['key' => 'tc.view',   'label' => 'View Test Cases',           'group' => 'Test Cases', 'sort_order' => 30],
            ['key' => 'tc.create', 'label' => 'Create Test Cases',         'group' => 'Test Cases', 'sort_order' => 31],
            ['key' => 'tc.edit',   'label' => 'Edit Test Cases',           'group' => 'Test Cases', 'sort_order' => 32],
            ['key' => 'tc.delete', 'label' => 'Delete Test Cases',         'group' => 'Test Cases', 'sort_order' => 33],
            ['key' => 'tc.import', 'label' => 'Import Test Cases (CSV)',   'group' => 'Test Cases', 'sort_order' => 34],
            ['key' => 'tc.export', 'label' => 'Export Test Cases',         'group' => 'Test Cases', 'sort_order' => 35],
            // Test Runs
            ['key' => 'test_runs.view',   'label' => 'View Test Runs', 'group' => 'Test Runs', 'sort_order' => 40],
            ['key' => 'test_runs.create', 'label' => 'Log Test Runs',  'group' => 'Test Runs', 'sort_order' => 41],
            // Test Suites
            ['key' => 'test_suites.view',   'label' => 'View Test Suites',   'group' => 'Test Suites', 'sort_order' => 50],
            ['key' => 'test_suites.create', 'label' => 'Create Test Suites', 'group' => 'Test Suites', 'sort_order' => 51],
            ['key' => 'test_suites.delete', 'label' => 'Delete Test Suites', 'group' => 'Test Suites', 'sort_order' => 52],
            // Tasks
            ['key' => 'tasks.view',          'label' => 'View Tasks',          'group' => 'Tasks', 'sort_order' => 60],
            ['key' => 'tasks.create',        'label' => 'Create Tasks',        'group' => 'Tasks', 'sort_order' => 61],
            ['key' => 'tasks.edit',          'label' => 'Edit Tasks',          'group' => 'Tasks', 'sort_order' => 62],
            ['key' => 'tasks.delete',        'label' => 'Delete Tasks',        'group' => 'Tasks', 'sort_order' => 63],
            ['key' => 'tasks.change_status', 'label' => 'Change Task Status',  'group' => 'Tasks', 'sort_order' => 64],
            // Sprints
            ['key' => 'sprints.view',   'label' => 'View Sprints',   'group' => 'Sprints', 'sort_order' => 70],
            ['key' => 'sprints.create', 'label' => 'Create Sprints', 'group' => 'Sprints', 'sort_order' => 71],
            ['key' => 'sprints.edit',   'label' => 'Edit Sprints',   'group' => 'Sprints', 'sort_order' => 72],
            ['key' => 'sprints.delete', 'label' => 'Delete Sprints', 'group' => 'Sprints', 'sort_order' => 73],
            // RTM
            ['key' => 'rtm.view',   'label' => 'View RTM',   'group' => 'RTM', 'sort_order' => 80],
            ['key' => 'rtm.export', 'label' => 'Export RTM', 'group' => 'RTM', 'sort_order' => 81],
            // Reports
            ['key' => 'reports.view',   'label' => 'View Reports',   'group' => 'Reports', 'sort_order' => 90],
            ['key' => 'reports.export', 'label' => 'Export Reports', 'group' => 'Reports', 'sort_order' => 91],
            // Coverage
            ['key' => 'coverage.view', 'label' => 'View Coverage', 'group' => 'Coverage', 'sort_order' => 100],
            // Members
            ['key' => 'members.view',   'label' => 'View Project Members',   'group' => 'Members', 'sort_order' => 110],
            ['key' => 'members.manage', 'label' => 'Manage Project Members', 'group' => 'Members', 'sort_order' => 111],
            // Projects
            ['key' => 'projects.create',  'label' => 'Create Projects',  'group' => 'Projects', 'sort_order' => 120],
            ['key' => 'projects.edit',    'label' => 'Edit Projects',    'group' => 'Projects', 'sort_order' => 121],
            ['key' => 'projects.archive', 'label' => 'Archive Projects', 'group' => 'Projects', 'sort_order' => 122],
        ];
        DB::table('permissions')->insert($permissions);

        // ── Helper: resolve permission IDs by key ────────────────────────────
        $perm = DB::table('permissions')->pluck('id', 'key');

        $all = $perm->keys()->all();

        $viewOnly = [
            'br.view', 'tr.view', 'tc.view', 'test_runs.view',
            'test_suites.view', 'tasks.view', 'sprints.view',
            'rtm.view', 'coverage.view', 'members.view',
        ];

        $pmKeys = $all; // PM gets everything

        $baKeys = [
            'br.view', 'br.create', 'br.edit', 'br.change_status', 'br.import', 'br.export',
            'tr.view',
            'tc.view',
            'test_runs.view',
            'test_suites.view',
            'tasks.view',
            'sprints.view',
            'rtm.view', 'rtm.export',
            'reports.view', 'reports.export',
            'coverage.view',
            'members.view',
        ];

        $devKeys = [
            'br.view',
            'tr.view', 'tr.create', 'tr.edit', 'tr.change_status', 'tr.import', 'tr.export',
            'tc.view',
            'test_runs.view',
            'test_suites.view',
            'tasks.view', 'tasks.create', 'tasks.edit', 'tasks.change_status',
            'sprints.view',
            'rtm.view',
            'coverage.view',
            'members.view',
        ];

        $testerKeys = [
            'br.view',
            'tr.view',
            'tc.view', 'tc.create', 'tc.edit', 'tc.import', 'tc.export',
            'test_runs.view', 'test_runs.create',
            'test_suites.view', 'test_suites.create',
            'tasks.view', 'tasks.create', 'tasks.edit', 'tasks.change_status',
            'sprints.view',
            'rtm.view',
            'reports.view',
            'coverage.view',
            'members.view',
        ];

        $rolePermissions = [];

        // Admin (id=1): superuser — no role_permissions rows needed (bypass in code)
        // PM (id=2)
        foreach ($pmKeys as $key) {
            $rolePermissions[] = ['role_id' => 2, 'permission_id' => $perm[$key]];
        }
        // BA (id=3)
        foreach ($baKeys as $key) {
            $rolePermissions[] = ['role_id' => 3, 'permission_id' => $perm[$key]];
        }
        // Developer (id=4)
        foreach ($devKeys as $key) {
            $rolePermissions[] = ['role_id' => 4, 'permission_id' => $perm[$key]];
        }
        // Tester (id=5)
        foreach ($testerKeys as $key) {
            $rolePermissions[] = ['role_id' => 5, 'permission_id' => $perm[$key]];
        }
        // Viewer (id=6)
        foreach ($viewOnly as $key) {
            $rolePermissions[] = ['role_id' => 6, 'permission_id' => $perm[$key]];
        }

        DB::table('role_permissions')->insert($rolePermissions);
    }

    public function down(): void
    {
        Schema::dropIfExists('role_permissions');
        Schema::dropIfExists('permissions');
        Schema::dropIfExists('roles');
    }
};
