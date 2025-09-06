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
            // Add DIME-specific tracking fields
            $table->integer('dime_id')->nullable()->after('id')->comment('DIME platform project ID');
            $table->string('city_code', 20)->nullable()->after('city_id')->comment('Philippine Standard Geographic Code for city');
            $table->string('barangay_code', 20)->nullable()->after('barangay_id')->comment('Philippine Standard Geographic Code for barangay');
            $table->string('province_code', 20)->nullable()->after('province_id')->comment('Philippine Standard Geographic Code for province');
            $table->string('region_code', 20)->nullable()->after('region_id')->comment('Philippine Standard Geographic Code for region');
            $table->string('country', 100)->nullable()->after('zip_code')->comment('Country name');
            $table->string('state', 100)->nullable()->after('country')->comment('State/territory name');
            
            // Add name fields for location (for direct storage when IDs not available)
            $table->string('region_name', 100)->nullable()->after('region_code')->comment('Region name from DIME');
            $table->string('province_name', 100)->nullable()->after('province_code')->comment('Province name from DIME');
            $table->string('city_name', 100)->nullable()->after('city_code')->comment('City/Municipality name from DIME');
            $table->string('barangay_name', 100)->nullable()->after('barangay_code')->comment('Barangay name from DIME');
            
            // Track data source
            $table->string('data_source', 50)->nullable()->default('manual')->after('metadata')->comment('Data source: manual, dime, etc.');
            $table->timestamp('last_synced_at')->nullable()->after('data_source')->comment('Last sync timestamp from external source');
            
            // Add indexes for better performance
            $table->index('dime_id');
            $table->index('city_code');
            $table->index('barangay_code');
            $table->index('province_code');
            $table->index('region_code');
            $table->index('data_source');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            // Drop indexes first
            $table->dropIndex(['dime_id']);
            $table->dropIndex(['city_code']);
            $table->dropIndex(['barangay_code']);
            $table->dropIndex(['province_code']);
            $table->dropIndex(['region_code']);
            $table->dropIndex(['data_source']);
            
            // Drop columns
            $table->dropColumn([
                'dime_id',
                'city_code',
                'barangay_code',
                'province_code',
                'region_code',
                'country',
                'state',
                'region_name',
                'province_name',
                'city_name',
                'barangay_name',
                'data_source',
                'last_synced_at',
            ]);
        });
    }
};