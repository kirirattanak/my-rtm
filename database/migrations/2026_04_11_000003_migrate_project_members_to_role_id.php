<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('project_members', function (Blueprint $table) {
            $table->foreignId('role_id')->nullable()->after('role')->constrained('roles')->nullOnDelete();
        });

        DB::statement('UPDATE project_members SET role_id = (SELECT id FROM roles WHERE slug = project_members.role)');

        Schema::table('project_members', function (Blueprint $table) {
            $table->dropColumn('role');
        });
    }

    public function down(): void
    {
        Schema::table('project_members', function (Blueprint $table) {
            $table->string('role')->default('viewer')->after('role_id');
        });

        DB::statement('UPDATE project_members SET role = (SELECT slug FROM roles WHERE id = project_members.role_id)');

        Schema::table('project_members', function (Blueprint $table) {
            $table->dropForeign(['role_id']);
            $table->dropColumn('role_id');
        });
    }
};
