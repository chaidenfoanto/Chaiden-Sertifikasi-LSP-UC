<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Create the vehicles table. A bicycle has no plate number, so the
     * column is nullable (NULL values do not clash in the unique index).
     */
    public function up(): void
    {
        Schema::create('vehicles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('type', 20);
            $table->string('plate_number', 15)->nullable();
            $table->string('brand', 50);
            $table->string('model_name', 50);
            $table->decimal('battery_capacity_kwh', 6, 2)->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'plate_number']);
        });
    }

    /**
     * Drop the vehicles table.
     */
    public function down(): void
    {
        Schema::dropIfExists('vehicles');
    }
};