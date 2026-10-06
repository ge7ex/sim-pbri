<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sim_resources', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('kind', 32)->index();
            $table->string('status', 32)->default('pending')->index();
            $table->unsignedInteger('quantity_total')->default(1);
            $table->boolean('is_exclusive')->default(true);
            $table->string('location')->nullable();
            $table->text('description')->nullable();
            $table->timestamps();

            $table->index(
                ['kind', 'status'],
                'sim_resources_kind_status_idx',
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sim_resources');
    }
};
