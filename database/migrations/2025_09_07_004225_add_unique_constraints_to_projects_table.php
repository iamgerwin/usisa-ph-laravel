<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            // Add unique constraints to prevent duplicates
            // dime_id should be unique if not null
            $table->unique('dime_id', 'projects_dime_id_unique');
            
            // project_code already has unique constraint, skip it
            
            // Add index on last_synced_at for efficient queries
            $table->index('last_synced_at', 'projects_last_synced_at_index');
            
            // Add composite index for efficient duplicate checking
            $table->index(['data_source', 'dime_id'], 'projects_data_source_dime_id_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->dropUnique('projects_dime_id_unique');
            // project_code unique constraint was not added by this migration
            $table->dropIndex('projects_last_synced_at_index');
            $table->dropIndex('projects_data_source_dime_id_index');
        });
    }
};