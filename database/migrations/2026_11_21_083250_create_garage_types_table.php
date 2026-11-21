<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Create the garage_types table (bicycle, car, electric_car).
     */
    public function up(): void
    {
        Schema::create('garage_types', function (Blueprint $table) {
            $table->id();
            $table->string('name', 20)->unique();
            $table->char('code', 1)->unique();
            $table->unsignedInteger('daily_price')->nullable();
            $table->unsignedInteger('electricity_price_per_kwh')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Drop the garage_types table.
     */
    public function down(): void
    {
        Schema::dropIfExists('garage_types');
    }
};