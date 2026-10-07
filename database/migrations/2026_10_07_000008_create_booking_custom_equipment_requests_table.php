<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('booking_custom_equipment_requests', function (Blueprint $table): void {
            $table->id();

            $table->foreignId('booking_id')
                ->constrained('bookings')
                ->restrictOnDelete();

            $table->string('name');
            $table->unsignedInteger('quantity')->default(1);
            $table->text('note')->nullable();
            $table->timestamps();

            $table->index(
                ['booking_id', 'created_at'],
                'booking_custom_equipment_booking_created_idx',
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('booking_custom_equipment_requests');
    }
};
