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
            // Remove hard-coded source-specific IDs if they exist
            if (Schema::hasColumn('projects', 'dime_id')) {
                $table->dropIndex(['dime_id']);
                $table->dropColumn('dime_id');
            }
            
            if (Schema::hasColumn('projects', 'sumbong_id')) {
                $table->dropIndex(['sumbong_id']);
                $table->dropColumn('sumbong_id');
            }
            
            // Add generic external tracking fields
            $table->string('external_id')->nullable()->after('uuid')
                ->comment('Unique identifier from external data source');
            $table->string('external_source')->default('manual')->after('external_id')
                ->comment('Source system identifier referencing scraper_sources.code');
            
            // Create composite unique index for external_id + external_source
            $table->unique(['external_id', 'external_source'], 'unique_external_source');
            
            // Add index for querying by source
            $table->index('external_source');
            
            // Add foreign key constraint to scraper_sources table
            $table->foreign('external_source')
                ->references('code')
                ->on('scraper_sources')
                ->onDelete('restrict')
                ->onUpdate('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->dropForeign(['external_source']);
            $table->dropUnique('unique_external_source');
            $table->dropIndex(['external_source']);
            $table->dropColumn(['external_id', 'external_source']);
            
            // Restore old columns if reverting
            $table->string('dime_id')->nullable()->after('uuid');
            $table->index('dime_id');
        });
    }
};