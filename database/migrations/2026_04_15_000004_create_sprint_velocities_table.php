<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sprint_velocities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sprint_id')->unique()->constrained()->cascadeOnDelete();
            $table->decimal('pert_expected_hours', 8, 2);
            $table->decimal('pert_std_dev', 8, 2);
            $table->decimal('available_hours', 8, 2);
            $table->decimal('actual_hours_logged', 8, 2);
            $table->unsignedSmallInteger('br_count_committed');
            $table->unsignedSmallInteger('br_count_completed');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sprint_velocities');
    }
};
