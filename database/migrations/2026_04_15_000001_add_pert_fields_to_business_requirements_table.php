<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('business_requirements', function (Blueprint $table) {
            $table->decimal('optimistic_hours', 8, 2)->nullable()->after('priority');
            $table->decimal('most_likely_hours', 8, 2)->nullable()->after('optimistic_hours');
            $table->decimal('pessimistic_hours', 8, 2)->nullable()->after('most_likely_hours');
        });
    }

    public function down(): void
    {
        Schema::table('business_requirements', function (Blueprint $table) {
            $table->dropColumn(['optimistic_hours', 'most_likely_hours', 'pessimistic_hours']);
        });
    }
};
