<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sprint_business_requirements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sprint_id')->constrained()->cascadeOnDelete();
            $table->foreignId('business_requirement_id')->constrained()->cascadeOnDelete();
            $table->foreignId('added_by')->constrained('users');
            $table->timestamps();

            $table->unique(['sprint_id', 'business_requirement_id']);
            $table->index('business_requirement_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sprint_business_requirements');
    }
};
