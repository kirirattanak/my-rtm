<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('br_dependencies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('blocking_br_id')->constrained('business_requirements')->cascadeOnDelete();
            $table->foreignId('blocked_br_id')->constrained('business_requirements')->cascadeOnDelete();
            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();

            $table->unique(['blocking_br_id', 'blocked_br_id']);
            $table->index('blocked_br_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('br_dependencies');
    }
};
