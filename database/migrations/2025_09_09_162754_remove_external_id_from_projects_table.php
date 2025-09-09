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
            // Drop the composite unique constraint first
            $table->dropUnique('unique_external_source');
            
            // Drop the external_id column as it's redundant
            // We can identify the source through external_source which references scraper_sources.code
            $table->dropColumn('external_id');
            
            // Add index back on external_source for performance
            // (The foreign key already creates an index, but being explicit)
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            // Restore the external_id column
            $table->string('external_id')->nullable()->after('uuid')
                ->comment('Unique identifier from external data source');
            
            // Restore the composite unique index
            $table->unique(['external_id', 'external_source'], 'unique_external_source');
        });
    }
};