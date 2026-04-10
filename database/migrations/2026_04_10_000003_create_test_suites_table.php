<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('test_suites', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();
        });

        Schema::create('test_suite_business_requirements', function (Blueprint $table) {
            $table->foreignId('test_suite_id')->constrained()->cascadeOnDelete();
            $table->foreignId('business_requirement_id')->constrained()->cascadeOnDelete();
            $table->primary(['test_suite_id', 'business_requirement_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('test_suite_business_requirements');
        Schema::dropIfExists('test_suites');
    }
};
