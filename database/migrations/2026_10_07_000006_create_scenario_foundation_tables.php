<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('courses', function (Blueprint $table): void {
            $table->id();

            $table->foreignId('college_id')
                ->constrained()
                ->restrictOnDelete();

            $table->string('code', 64)->nullable();
            $table->string('name');
            $table->timestamps();

            $table->index(
                ['college_id', 'name'],
                'courses_college_name_idx',
            );

            $table->unique(
                ['college_id', 'code'],
                'courses_college_code_unique',
            );
        });

        Schema::create('scenarios', function (Blueprint $table): void {
            $table->id();

            $table->foreignId('course_id')
                ->constrained('courses')
                ->restrictOnDelete();

            $table->string('name');
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(
                ['course_id', 'name'],
                'scenarios_course_name_unique',
            );
        });

        Schema::create('scenario_resource_templates', function (Blueprint $table): void {
            $table->id();

            $table->foreignId('scenario_id')
                ->constrained('scenarios')
                ->restrictOnDelete();

            $table->foreignId('sim_resource_id')
                ->constrained('sim_resources')
                ->restrictOnDelete();

            $table->unsignedInteger('quantity');
            $table->timestamps();

            $table->unique(
                ['scenario_id', 'sim_resource_id'],
                'scenario_resource_template_unique',
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('scenario_resource_templates');
        Schema::dropIfExists('scenarios');
        Schema::dropIfExists('courses');
    }
};
