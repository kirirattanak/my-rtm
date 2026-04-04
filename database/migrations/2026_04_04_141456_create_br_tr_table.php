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
        Schema::create('br_tr', function (Blueprint $table) {
            $table->foreignId('business_requirement_id')->constrained()->cascadeOnDelete();
            $table->foreignId('technical_requirement_id')->constrained()->cascadeOnDelete();

            $table->primary(['business_requirement_id', 'technical_requirement_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('br_tr');
    }
};
