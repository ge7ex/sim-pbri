<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bookings', function (Blueprint $table): void {
            $table->id();

            $table->foreignId('college_id')
                ->constrained()
                ->restrictOnDelete();

            $table->foreignId('requested_by_user_id')
                ->constrained('users')
                ->restrictOnDelete();

            $table->string('requester_name');
            $table->string('requester_phone', 32)->nullable();

            $table->dateTime('starts_at');
            $table->dateTime('ends_at');
            $table->unsignedInteger('participant_count')->nullable();

            $table->text('note')->nullable();

            $table->string('status', 32)->default('pending');

            $table->foreignId('reviewed_by_user_id')
                ->nullable()
                ->constrained('users')
                ->restrictOnDelete();

            $table->dateTime('reviewed_at')->nullable();
            $table->text('review_reason')->nullable();

            $table->timestamps();

            $table->index(
                ['college_id', 'status', 'starts_at'],
                'bookings_college_status_starts_idx',
            );
            $table->index(
                ['status', 'starts_at', 'ends_at'],
                'bookings_blocking_window_idx',
            );
        });

        Schema::create('booking_status_transitions', function (Blueprint $table): void {
            $table->id();

            $table->foreignId('booking_id')
                ->constrained('bookings')
                ->restrictOnDelete();

            $table->string('from_status', 32)->nullable();
            $table->string('to_status', 32);

            $table->foreignId('actor_user_id')
                ->constrained('users')
                ->restrictOnDelete();

            $table->text('reason')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(
                ['booking_id', 'created_at'],
                'booking_transitions_booking_created_idx',
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('booking_status_transitions');
        Schema::dropIfExists('bookings');
    }
};
