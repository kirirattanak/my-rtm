<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('member_monthly_capacity', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('year');
            $table->unsignedTinyInteger('month');
            $table->decimal('available_hours', 8, 2);
            $table->decimal('focus_factor', 3, 2)->default(0.80);
            $table->string('notes')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'project_id', 'year', 'month']);
            $table->index(['project_id', 'year', 'month']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('member_monthly_capacity');
    }
};
