<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table): void {
            $table->foreignId('course_id')
                ->nullable()
                ->after('requested_by_user_id')
                ->constrained('courses')
                ->restrictOnDelete();

            $table->foreignId('scenario_id')
                ->nullable()
                ->after('course_id')
                ->constrained('scenarios')
                ->restrictOnDelete();

            $table->index(
                ['college_id', 'course_id', 'scenario_id'],
                'bookings_college_course_scenario_idx',
            );
        });

        Schema::table('booking_resource', function (Blueprint $table): void {
            $table->boolean('is_auto_recommended')
                ->default(false)
                ->after('quantity');
        });
    }

    public function down(): void
    {
        Schema::table('booking_resource', function (Blueprint $table): void {
            $table->dropColumn('is_auto_recommended');
        });

        Schema::table('bookings', function (Blueprint $table): void {
            $table->dropIndex('bookings_college_course_scenario_idx');
            $table->dropConstrainedForeignId('scenario_id');
            $table->dropConstrainedForeignId('course_id');
        });
    }
};
