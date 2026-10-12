<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sim_resources', function (Blueprint $table): void {
            $table->decimal('room_width_m', 8, 2)->nullable();
            $table->decimal('room_length_m', 8, 2)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('sim_resources', function (Blueprint $table): void {
            $table->dropColumn(['room_width_m', 'room_length_m']);
        });
    }
};
