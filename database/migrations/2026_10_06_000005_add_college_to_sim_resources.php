<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sim_resources', function (Blueprint $table): void {
            $table->foreignId('college_id')
                ->after('id')
                ->constrained()
                ->restrictOnDelete();

            $table->index(
                ['college_id', 'kind', 'status'],
                'sim_resources_college_kind_status_idx',
            );
        });
    }

    public function down(): void
    {
        Schema::table('sim_resources', function (Blueprint $table): void {
            $table->dropIndex('sim_resources_college_kind_status_idx');
            $table->dropForeign(['college_id']);
            $table->dropColumn('college_id');
        });
    }
};
