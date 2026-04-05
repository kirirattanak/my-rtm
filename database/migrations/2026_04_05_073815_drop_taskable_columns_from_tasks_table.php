<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // SQLite does not support DROP COLUMN — rebuild the table without taskable columns
        Schema::create('tasks_new', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->foreignId('sprint_id')->nullable()->constrained()->nullOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->decimal('effort_estimate', 8, 2)->nullable();
            $table->string('effort_unit')->default('points');
            $table->string('status')->default('todo');
            $table->date('due_date')->nullable();
            $table->foreignId('assignee_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('created_by')->constrained('users');
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
        });

        \DB::statement('INSERT INTO tasks_new SELECT id, project_id, sprint_id, title, description, effort_estimate, effort_unit, status, due_date, assignee_id, created_by, completed_at, created_at, updated_at FROM tasks');

        Schema::drop('tasks');
        Schema::rename('tasks_new', 'tasks');
    }

    public function down(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->nullableMorphs('taskable');
        });
    }
};
