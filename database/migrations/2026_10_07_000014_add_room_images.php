<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sim_resources', fn (Blueprint $table) => $table->string('image_path')->nullable());
        Schema::create('sim_resource_image_changes', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('sim_resource_id')->constrained()->restrictOnDelete();
            $table->foreignId('actor_user_id')->constrained('users')->restrictOnDelete();
            $table->string('action', 16);
            $table->timestamp('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sim_resource_image_changes');
        Schema::table('sim_resources', fn (Blueprint $table) => $table->dropColumn('image_path'));
    }
};
