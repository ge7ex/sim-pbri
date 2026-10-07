<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table): void {
            $table->foreignId('simulator_asset_id')->nullable()->constrained('simulator_assets')->restrictOnDelete();
            $table->index(['simulator_asset_id', 'status', 'starts_at', 'ends_at'], 'booking_simulator_availability_idx');
        });
        Schema::create('scenario_simulator_type', function (Blueprint $table): void {
            $table->foreignId('scenario_id')->constrained()->restrictOnDelete();
            $table->foreignId('simulator_type_id')->constrained()->restrictOnDelete();
            $table->timestamps();
            $table->primary(['scenario_id', 'simulator_type_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('scenario_simulator_type');
        Schema::table('bookings', function (Blueprint $table): void {
            $table->dropIndex('booking_simulator_availability_idx');
            $table->dropConstrainedForeignId('simulator_asset_id');
        });
    }
};
