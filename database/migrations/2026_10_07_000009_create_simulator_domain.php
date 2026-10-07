<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('simulator_types', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('college_id')->constrained()->restrictOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->unique(['college_id', 'name']);
            $table->unique(['id', 'college_id']);
        });

        Schema::create('simulator_assets', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('college_id')->constrained()->restrictOnDelete();
            $table->foreignId('simulator_type_id');
            $table->foreign(['simulator_type_id', 'college_id'])
                ->references(['id', 'college_id'])->on('simulator_types')->restrictOnDelete();
            $table->string('asset_name');
            $table->string('asset_code', 64)->nullable();
            $table->unsignedSmallInteger('purchase_year')->nullable();
            $table->decimal('purchase_price', 14, 2)->nullable();
            $table->unsignedSmallInteger('useful_life_years')->nullable();
            $table->string('status', 24)->default('active');
            $table->string('location')->nullable();
            $table->text('description')->nullable();
            $table->timestamps();
            $table->unique(['college_id', 'asset_code']);
            $table->index(['college_id', 'status']);
        });

        Schema::create('simulator_maintenance_records', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('simulator_asset_id')->constrained()->restrictOnDelete();
            $table->date('maintenance_date');
            $table->text('description');
            $table->decimal('cost', 14, 2)->nullable();
            $table->string('performed_by')->nullable();
            $table->text('note')->nullable();
            $table->foreignId('created_by_user_id')->constrained('users')->restrictOnDelete();
            $table->timestamps();
            $table->index(['simulator_asset_id', 'maintenance_date'], 'simulator_maintenance_date_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('simulator_maintenance_records');
        Schema::dropIfExists('simulator_assets');
        Schema::dropIfExists('simulator_types');
    }
};
