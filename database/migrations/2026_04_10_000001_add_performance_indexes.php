<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Tasks: status and assignee_id are used in index filtering and sprint workload grouping;
        // due_date and completed_at are used in ordering and burndown chart filtering.
        Schema::table('tasks', function (Blueprint $table) {
            $table->index('status');
            $table->index('assignee_id');
            $table->index('due_date');
            $table->index('completed_at');
        });

        // Test runs: status is checked in coverage calculations;
        // created_at is used for latest() ordering to find the most recent run.
        Schema::table('test_runs', function (Blueprint $table) {
            $table->index('status');
            $table->index('created_at');
        });

        // Requirements: status is used in list filtering across all requirement views.
        Schema::table('business_requirements', function (Blueprint $table) {
            $table->index('status');
        });

        Schema::table('technical_requirements', function (Blueprint $table) {
            $table->index('status');
        });

        // Test cases: status and assignee_id are used in list filtering.
        Schema::table('test_cases', function (Blueprint $table) {
            $table->index('status');
            $table->index('assignee_id');
        });

        // Activity logs: created_at is used for latest() ordering on the project dashboard.
        Schema::table('activity_logs', function (Blueprint $table) {
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->dropIndex(['status']);
            $table->dropIndex(['assignee_id']);
            $table->dropIndex(['due_date']);
            $table->dropIndex(['completed_at']);
        });

        Schema::table('test_runs', function (Blueprint $table) {
            $table->dropIndex(['status']);
            $table->dropIndex(['created_at']);
        });

        Schema::table('business_requirements', function (Blueprint $table) {
            $table->dropIndex(['status']);
        });

        Schema::table('technical_requirements', function (Blueprint $table) {
            $table->dropIndex(['status']);
        });

        Schema::table('test_cases', function (Blueprint $table) {
            $table->dropIndex(['status']);
            $table->dropIndex(['assignee_id']);
        });

        Schema::table('activity_logs', function (Blueprint $table) {
            $table->dropIndex(['created_at']);
        });
    }
};
