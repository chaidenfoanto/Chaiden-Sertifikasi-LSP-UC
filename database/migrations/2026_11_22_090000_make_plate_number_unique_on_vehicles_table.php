<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Make the plate number unique across all renters (a real plate belongs
     * to one vehicle). NULL plates (bicycles) still do not clash.
     *
     * MySQL uses the old (user_id, plate_number) index to back the foreign
     * key on user_id, so a plain index on user_id is added before it is dropped.
     */
    public function up(): void
    {
        Schema::table('vehicles', function (Blueprint $table) {
            $table->index('user_id');
        });

        Schema::table('vehicles', function (Blueprint $table) {
            $table->dropUnique(['user_id', 'plate_number']);
            $table->unique('plate_number');
        });
    }

    /**
     * Restore the per-renter unique index.
     */
    public function down(): void
    {
        Schema::table('vehicles', function (Blueprint $table) {
            $table->unique(['user_id', 'plate_number']);
        });

        Schema::table('vehicles', function (Blueprint $table) {
            $table->dropUnique(['plate_number']);
            $table->dropIndex(['user_id']);
        });
    }
};