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
        // Add fields to regions table
        Schema::table('regions', function (Blueprint $table) {
            if (!Schema::hasColumn('regions', 'region_name')) {
                $table->string('region_name')->nullable()->after('name')->comment('Display name like Region I');
            }
            if (!Schema::hasColumn('regions', 'island_group_code')) {
                $table->string('island_group_code', 20)->nullable()->after('abbreviation')->comment('luzon, visayas, or mindanao');
            }
            if (!Schema::hasColumn('regions', 'old_name')) {
                $table->string('old_name')->nullable()->after('psa_name')->comment('Previous/historical name');
            }
        });

        // Add fields to provinces table
        Schema::table('provinces', function (Blueprint $table) {
            if (!Schema::hasColumn('provinces', 'island_group_code')) {
                $table->string('island_group_code', 20)->nullable()->after('abbreviation')->comment('luzon, visayas, or mindanao');
            }
            if (!Schema::hasColumn('provinces', 'old_name')) {
                $table->string('old_name')->nullable()->after('psa_name')->comment('Previous/historical name');
            }
            if (!Schema::hasColumn('provinces', 'district_code')) {
                $table->string('district_code', 10)->nullable()->comment('District code for NCR/special areas');
            }
        });

        // Add fields to cities table
        Schema::table('cities', function (Blueprint $table) {
            if (!Schema::hasColumn('cities', 'island_group_code')) {
                $table->string('island_group_code', 20)->nullable()->after('type')->comment('luzon, visayas, or mindanao');
            }
            if (!Schema::hasColumn('cities', 'old_name')) {
                $table->string('old_name')->nullable()->after('psa_name')->comment('Previous/historical name');
            }
            if (!Schema::hasColumn('cities', 'district_code')) {
                $table->string('district_code', 10)->nullable()->comment('District code for NCR/special areas');
            }
        });

        // Add fields to barangays table
        Schema::table('barangays', function (Blueprint $table) {
            if (!Schema::hasColumn('barangays', 'island_group_code')) {
                $table->string('island_group_code', 20)->nullable()->after('urban_rural')->comment('luzon, visayas, or mindanao');
            }
            if (!Schema::hasColumn('barangays', 'old_name')) {
                $table->string('old_name')->nullable()->after('psa_name')->comment('Previous/historical name');
            }
            if (!Schema::hasColumn('barangays', 'district_code')) {
                $table->string('district_code', 10)->nullable()->comment('District code for NCR/special areas');
            }
            if (!Schema::hasColumn('barangays', 'sub_municipality_code')) {
                $table->string('sub_municipality_code', 10)->nullable()->comment('Sub-municipality code if applicable');
            }
        });

        // Create districts table for NCR districts
        if (!Schema::hasTable('districts')) {
            Schema::create('districts', function (Blueprint $table) {
                $table->uuid('uuid')->unique()->index()->comment('Primary public identifier');
                $table->id();
                $table->string('code', 10)->unique()->comment('PSGC district code');
                $table->string('name')->comment('District name');
                $table->string('psa_code', 10)->nullable()->comment('PSA PSGC code');
                $table->string('psa_name')->nullable()->comment('PSA official name');
                $table->string('psa_slug')->nullable()->unique()->index()->comment('PSA URL slug');
                $table->foreignId('region_id')->nullable()->constrained()->onDelete('cascade');
                $table->string('island_group_code', 20)->nullable()->comment('luzon, visayas, or mindanao');
                $table->integer('sort_order')->default(0)->comment('Display sort order');
                $table->boolean('is_active')->default(true);
                $table->json('psa_data')->nullable()->comment('Additional PSA metadata');
                $table->timestamp('psa_synced_at')->nullable()->comment('Last sync with PSA data');
                $table->timestamps();
                $table->softDeletes();

                $table->index(['code']);
                $table->index(['is_active']);
            });
        }

        // Create sub_municipalities table for special sub-municipal areas
        if (!Schema::hasTable('sub_municipalities')) {
            Schema::create('sub_municipalities', function (Blueprint $table) {
                $table->uuid('uuid')->unique()->index()->comment('Primary public identifier');
                $table->id();
                $table->string('code', 10)->unique()->comment('PSGC sub-municipality code');
                $table->string('name')->comment('Sub-municipality name');
                $table->string('psa_code', 10)->nullable()->comment('PSA PSGC code');
                $table->string('psa_name')->nullable()->comment('PSA official name');
                $table->string('psa_slug')->nullable()->unique()->index()->comment('PSA URL slug');
                $table->string('old_name')->nullable()->comment('Previous/historical name');
                $table->foreignId('city_id')->nullable()->constrained()->onDelete('cascade');
                $table->string('district_code', 10)->nullable();
                $table->string('island_group_code', 20)->nullable()->comment('luzon, visayas, or mindanao');
                $table->integer('sort_order')->default(0)->comment('Display sort order');
                $table->boolean('is_active')->default(true);
                $table->json('psa_data')->nullable()->comment('Additional PSA metadata');
                $table->timestamp('psa_synced_at')->nullable()->comment('Last sync with PSA data');
                $table->timestamps();
                $table->softDeletes();

                $table->index(['code']);
                $table->index(['is_active']);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop new tables
        Schema::dropIfExists('sub_municipalities');
        Schema::dropIfExists('districts');

        // Remove fields from regions
        Schema::table('regions', function (Blueprint $table) {
            $columnsToRemove = ['region_name', 'island_group_code', 'old_name'];
            foreach ($columnsToRemove as $column) {
                if (Schema::hasColumn('regions', $column)) {
                    $table->dropColumn($column);
                }
            }
        });

        // Remove fields from provinces
        Schema::table('provinces', function (Blueprint $table) {
            $columnsToRemove = ['island_group_code', 'old_name', 'district_code'];
            foreach ($columnsToRemove as $column) {
                if (Schema::hasColumn('provinces', $column)) {
                    $table->dropColumn($column);
                }
            }
        });

        // Remove fields from cities
        Schema::table('cities', function (Blueprint $table) {
            $columnsToRemove = ['island_group_code', 'old_name', 'district_code'];
            foreach ($columnsToRemove as $column) {
                if (Schema::hasColumn('cities', $column)) {
                    $table->dropColumn($column);
                }
            }
        });

        // Remove fields from barangays
        Schema::table('barangays', function (Blueprint $table) {
            $columnsToRemove = ['island_group_code', 'old_name', 'district_code', 'sub_municipality_code'];
            foreach ($columnsToRemove as $column) {
                if (Schema::hasColumn('barangays', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};