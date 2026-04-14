<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('subscription_tier_options', function (Blueprint $table) {
            $table->id();
            $table->enum('tier', ['basic', 'starter', 'standard', 'pro']);
            $table->unsignedSmallInteger('seats');
            $table->string('label');
            $table->unsignedSmallInteger('sort_order')->default(0);
        });

        DB::table('subscription_tier_options')->insert([
            // Basic
            ['tier' => 'basic',    'seats' => 10,  'label' => 'Basic · 10 seats',      'sort_order' => 10],
            ['tier' => 'basic',    'seats' => 25,  'label' => 'Basic · 25 seats',      'sort_order' => 11],
            // Starter
            ['tier' => 'starter',  'seats' => 50,  'label' => 'Starter · 50 seats',    'sort_order' => 20],
            ['tier' => 'starter',  'seats' => 100, 'label' => 'Starter · 100 seats',   'sort_order' => 21],
            // Standard
            ['tier' => 'standard', 'seats' => 50,  'label' => 'Standard · 50 seats',   'sort_order' => 30],
            ['tier' => 'standard', 'seats' => 100, 'label' => 'Standard · 100 seats',  'sort_order' => 31],
            ['tier' => 'standard', 'seats' => 150, 'label' => 'Standard · 150 seats',  'sort_order' => 32],
            // Pro
            ['tier' => 'pro',      'seats' => 50,  'label' => 'Pro · 50 seats',        'sort_order' => 40],
            ['tier' => 'pro',      'seats' => 100, 'label' => 'Pro · 100 seats',       'sort_order' => 41],
            ['tier' => 'pro',      'seats' => 150, 'label' => 'Pro · 150 seats',       'sort_order' => 42],
            ['tier' => 'pro',      'seats' => 200, 'label' => 'Pro · 200 seats',       'sort_order' => 43],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('subscription_tier_options');
    }
};
