<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Create the garages table. Availability is derived from rentals,
     * so there is intentionally no status column here.
     */
    public function up(): void
    {
        Schema::create('garages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('garage_type_id')->constrained()->restrictOnDelete();
            $table->unsignedSmallInteger('sequence_number');
            $table->char('code', 4)->unique();
            $table->timestamps();

            $table->unique(['garage_type_id', 'sequence_number']);
        });
    }

    /**
     * Drop the garages table.
     */
    public function down(): void
    {
        Schema::dropIfExists('garages');
    }
};