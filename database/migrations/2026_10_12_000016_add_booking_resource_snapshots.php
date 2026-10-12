<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('booking_resource', function (Blueprint $table): void {
            $table->string('resource_name_snapshot')->nullable();
            $table->string('resource_location_snapshot')->nullable();
            $table->string('room_building_snapshot', 120)->nullable();
            $table->string('room_floor_snapshot', 64)->nullable();
            $table->unsignedInteger('room_capacity_snapshot')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('booking_resource', function (Blueprint $table): void {
            $table->dropColumn([
                'resource_name_snapshot',
                'resource_location_snapshot',
                'room_building_snapshot',
                'room_floor_snapshot',
                'room_capacity_snapshot',
            ]);
        });
    }
};
