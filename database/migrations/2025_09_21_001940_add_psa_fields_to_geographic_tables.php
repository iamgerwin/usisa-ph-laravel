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
        // Add PSA tracking fields to regions table if they don't exist
        Schema::table('regions', function (Blueprint $table) {
            if (!Schema::hasColumn('regions', 'psa_slug')) {
                $table->string('psa_slug')->nullable()->after('uuid')->unique()->index()->comment('PSA URL slug');
            }
            if (!Schema::hasColumn('regions', 'psa_code')) {
                $table->string('psa_code', 10)->nullable()->after('code')->comment('PSA PSGC code');
            }
            if (!Schema::hasColumn('regions', 'psa_name')) {
                $table->string('psa_name')->nullable()->after('name')->comment('PSA official name');
            }
            if (!Schema::hasColumn('regions', 'psa_data')) {
                $table->json('psa_data')->nullable()->comment('Additional PSA metadata');
            }
            if (!Schema::hasColumn('regions', 'psa_synced_at')) {
                $table->timestamp('psa_synced_at')->nullable()->comment('Last sync with PSA data');
            }
            // Add index if not exists
            if (!Schema::hasColumn('regions', 'psa_code')) {
                $table->index('psa_code');
            }
        });

        // Add PSA tracking fields to provinces table if they don't exist
        Schema::table('provinces', function (Blueprint $table) {
            if (!Schema::hasColumn('provinces', 'psa_slug')) {
                $table->string('psa_slug')->nullable()->after('uuid')->unique()->index()->comment('PSA URL slug');
            }
            if (!Schema::hasColumn('provinces', 'psa_code')) {
                $table->string('psa_code', 10)->nullable()->after('code')->comment('PSA PSGC code');
            }
            if (!Schema::hasColumn('provinces', 'psa_name')) {
                $table->string('psa_name')->nullable()->after('name')->comment('PSA official name');
            }
            if (!Schema::hasColumn('provinces', 'income_class')) {
                $table->string('income_class', 20)->nullable()->comment('Income classification');
            }
            if (!Schema::hasColumn('provinces', 'psa_data')) {
                $table->json('psa_data')->nullable()->comment('Additional PSA metadata');
            }
            if (!Schema::hasColumn('provinces', 'psa_synced_at')) {
                $table->timestamp('psa_synced_at')->nullable()->comment('Last sync with PSA data');
            }
            // Add index if not exists
            if (!Schema::hasColumn('provinces', 'psa_code')) {
                $table->index('psa_code');
            }
        });

        // Add PSA tracking fields to cities table if they don't exist
        Schema::table('cities', function (Blueprint $table) {
            if (!Schema::hasColumn('cities', 'psa_slug')) {
                $table->string('psa_slug')->nullable()->after('uuid')->unique()->index()->comment('PSA URL slug');
            }
            if (!Schema::hasColumn('cities', 'psa_code')) {
                $table->string('psa_code', 10)->nullable()->after('code')->comment('PSA PSGC code');
            }
            if (!Schema::hasColumn('cities', 'psa_name')) {
                $table->string('psa_name')->nullable()->after('name')->comment('PSA official name');
            }
            if (!Schema::hasColumn('cities', 'city_class')) {
                $table->string('city_class', 20)->nullable()->comment('City classification');
            }
            if (!Schema::hasColumn('cities', 'income_class')) {
                $table->string('income_class', 20)->nullable()->comment('Income classification');
            }
            if (!Schema::hasColumn('cities', 'is_capital')) {
                $table->boolean('is_capital')->default(false)->comment('Provincial capital flag');
            }
            if (!Schema::hasColumn('cities', 'psa_data')) {
                $table->json('psa_data')->nullable()->comment('Additional PSA metadata');
            }
            if (!Schema::hasColumn('cities', 'psa_synced_at')) {
                $table->timestamp('psa_synced_at')->nullable()->comment('Last sync with PSA data');
            }
            // Add index if not exists
            if (!Schema::hasColumn('cities', 'psa_code')) {
                $table->index('psa_code');
            }
        });

        // Add PSA tracking fields to barangays table if they don't exist
        Schema::table('barangays', function (Blueprint $table) {
            if (!Schema::hasColumn('barangays', 'psa_slug')) {
                $table->string('psa_slug')->nullable()->after('uuid')->unique()->index()->comment('PSA URL slug');
            }
            if (!Schema::hasColumn('barangays', 'psa_code')) {
                $table->string('psa_code', 10)->nullable()->after('code')->comment('PSA PSGC code');
            }
            if (!Schema::hasColumn('barangays', 'psa_name')) {
                $table->string('psa_name')->nullable()->after('name')->comment('PSA official name');
            }
            if (!Schema::hasColumn('barangays', 'urban_rural')) {
                $table->string('urban_rural', 10)->nullable()->comment('Urban/Rural classification');
            }
            if (!Schema::hasColumn('barangays', 'psa_data')) {
                $table->json('psa_data')->nullable()->comment('Additional PSA metadata');
            }
            if (!Schema::hasColumn('barangays', 'psa_synced_at')) {
                $table->timestamp('psa_synced_at')->nullable()->comment('Last sync with PSA data');
            }
            // Add index if not exists
            if (!Schema::hasColumn('barangays', 'psa_code')) {
                $table->index('psa_code');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop columns from regions table
        Schema::table('regions', function (Blueprint $table) {
            $columnsToRemove = ['psa_slug', 'psa_code', 'psa_name', 'psa_data', 'psa_synced_at'];
            foreach ($columnsToRemove as $column) {
                if (Schema::hasColumn('regions', $column)) {
                    $table->dropColumn($column);
                }
            }
        });

        // Drop columns from provinces table
        Schema::table('provinces', function (Blueprint $table) {
            $columnsToRemove = ['psa_slug', 'psa_code', 'psa_name', 'income_class', 'psa_data', 'psa_synced_at'];
            foreach ($columnsToRemove as $column) {
                if (Schema::hasColumn('provinces', $column)) {
                    $table->dropColumn($column);
                }
            }
        });

        // Drop columns from cities table
        Schema::table('cities', function (Blueprint $table) {
            $columnsToRemove = ['psa_slug', 'psa_code', 'psa_name', 'city_class', 'income_class', 'is_capital', 'psa_data', 'psa_synced_at'];
            foreach ($columnsToRemove as $column) {
                if (Schema::hasColumn('cities', $column)) {
                    $table->dropColumn($column);
                }
            }
        });

        // Drop columns from barangays table
        Schema::table('barangays', function (Blueprint $table) {
            $columnsToRemove = ['psa_slug', 'psa_code', 'psa_name', 'urban_rural', 'psa_data', 'psa_synced_at'];
            foreach ($columnsToRemove as $column) {
                if (Schema::hasColumn('barangays', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};