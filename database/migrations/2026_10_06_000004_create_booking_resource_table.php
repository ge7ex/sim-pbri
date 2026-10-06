<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('booking_resource', function (Blueprint $table): void {
            $table->id();

            $table->foreignId('booking_id')
                ->constrained('bookings')
                ->restrictOnDelete();

            $table->foreignId('sim_resource_id')
                ->constrained('sim_resources')
                ->restrictOnDelete();

            $table->unsignedInteger('quantity')->default(1);
            $table->timestamps();

            $table->unique(
                ['booking_id', 'sim_resource_id'],
                'booking_resource_unique',
            );

            $table->index(
                ['sim_resource_id', 'booking_id'],
                'booking_resource_lookup_idx',
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('booking_resource');
    }
};
