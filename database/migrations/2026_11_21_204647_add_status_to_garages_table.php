<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add the status column required by the assignment (available or used).
     * Availability for a date range is still decided by overlapping rentals.
     */
    public function up(): void
    {
        Schema::table('garages', function (Blueprint $table) {
            $table->string('status', 10)->default('available')->after('code');
        });
    }

    /**
     * Remove the status column.
     */
    public function down(): void
    {
        Schema::table('garages', function (Blueprint $table) {
            $table->dropColumn('status');
        });
    }
};