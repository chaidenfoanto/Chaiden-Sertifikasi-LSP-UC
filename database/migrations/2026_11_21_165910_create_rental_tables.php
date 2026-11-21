<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Create rentals, rental_items, and rental_extensions.
     */
    public function up(): void
    {
        Schema::create('rentals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->foreignId('vehicle_id')->constrained()->restrictOnDelete();
            $table->foreignId('garage_id')->constrained()->restrictOnDelete();
            $table->date('start_date');
            $table->date('end_date');
            $table->decimal('remaining_battery_kwh', 6, 2)->nullable();
            $table->unsignedBigInteger('charging_cost')->default(0);
            $table->unsignedBigInteger('total_cost');
            $table->string('payment_proof_path');
            $table->timestamps();

            $table->index(['garage_id', 'start_date', 'end_date']);
        });

        Schema::create('rental_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rental_id')->constrained()->cascadeOnDelete();
            $table->string('rental_type', 10);
            $table->date('start_date');
            $table->date('end_date');
            $table->unsignedSmallInteger('days');
            $table->unsignedBigInteger('unit_price');
            $table->unsignedBigInteger('subtotal');
            $table->timestamps();
        });

        Schema::create('rental_extensions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rental_id')->constrained()->cascadeOnDelete();
            $table->date('previous_end_date');
            $table->date('new_end_date');
            $table->unsignedBigInteger('additional_cost');
            $table->string('payment_proof_path');
            $table->timestamps();
        });
    }

    /**
     * Drop the rental tables in dependency order.
     */
    public function down(): void
    {
        Schema::dropIfExists('rental_extensions');
        Schema::dropIfExists('rental_items');
        Schema::dropIfExists('rentals');
    }
};