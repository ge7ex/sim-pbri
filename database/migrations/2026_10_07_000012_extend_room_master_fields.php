<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sim_resources', function (Blueprint $table): void {
            $table->string('building', 120)->nullable();
            $table->string('floor', 64)->nullable();
            $table->unsignedInteger('capacity')->nullable();
            $table->foreignId('responsible_staff_user_id')->nullable()->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('sim_resources', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('responsible_staff_user_id');
            $table->dropColumn(['building', 'floor', 'capacity']);
        });
    }
};
