<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Get the DIME scraper source
        $dimeSource = DB::table('scraper_sources')
            ->where('code', 'dime')
            ->first();
        
        if (!$dimeSource) {
            throw new Exception('DIME scraper source not found. Please run ScraperSourceSeeder first.');
        }
        
        // Update all existing projects to use DIME as their source
        // Since all existing data came from DIME, we'll:
        // 1. Set external_source to 'dime' (the code, not UUID)
        // 2. Set external_id to the project's UUID (as a unique identifier from DIME)
        
        DB::table('projects')->update([
            'external_source' => 'dime',
            'external_id' => DB::raw('uuid'), // Use each project's UUID as its external_id
        ]);
        
        // Log the update
        $count = DB::table('projects')->count();
        echo "Updated {$count} projects to use DIME as their external source.\n";
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Reset to default 'manual' source
        DB::table('projects')->update([
            'external_source' => 'manual',
            'external_id' => null,
        ]);
    }
};