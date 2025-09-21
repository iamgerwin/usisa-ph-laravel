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
        // Add PSA tracking fields to regions
        Schema::table('regions', function (Blueprint $table) {
            $table->string('psa_code', 10)->nullable()->after('code')->comment('PSA PSGC code');
            $table->string('psa_name')->nullable()->after('name')->comment('PSA official name');
            $table->json('psa_data')->nullable()->comment('Additional PSA metadata');
            $table->timestamp('psa_synced_at')->nullable()->comment('Last sync with PSA data');
            $table->index('psa_code');
        });

        // Add PSA tracking fields to provinces
        Schema::table('provinces', function (Blueprint $table) {
            $table->string('psa_code', 10)->nullable()->after('code')->comment('PSA PSGC code');
            $table->string('psa_name')->nullable()->after('name')->comment('PSA official name');
            $table->string('income_class', 20)->nullable()->comment('Income classification');
            $table->json('psa_data')->nullable()->comment('Additional PSA metadata');
            $table->timestamp('psa_synced_at')->nullable()->comment('Last sync with PSA data');
            $table->index('psa_code');
        });

        // Add PSA tracking fields to cities
        Schema::table('cities', function (Blueprint $table) {
            $table->string('psa_code', 10)->nullable()->after('code')->comment('PSA PSGC code');
            $table->string('psa_name')->nullable()->after('name')->comment('PSA official name');
            $table->string('city_class', 20)->nullable()->comment('City classification');
            $table->string('income_class', 20)->nullable()->comment('Income classification');
            $table->boolean('is_capital')->default(false)->comment('Provincial capital flag');
            $table->json('psa_data')->nullable()->comment('Additional PSA metadata');
            $table->timestamp('psa_synced_at')->nullable()->comment('Last sync with PSA data');
            $table->index('psa_code');
        });

        // Add PSA tracking fields to barangays
        Schema::table('barangays', function (Blueprint $table) {
            $table->string('psa_code', 10)->nullable()->after('code')->comment('PSA PSGC code');
            $table->string('psa_name')->nullable()->after('name')->comment('PSA official name');
            $table->string('urban_rural', 10)->nullable()->comment('Urban/Rural classification');
            $table->json('psa_data')->nullable()->comment('Additional PSA metadata');
            $table->timestamp('psa_synced_at')->nullable()->comment('Last sync with PSA data');
            $table->index('psa_code');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('regions', function (Blueprint $table) {
            $table->dropIndex(['psa_code']);
            $table->dropColumn(['psa_code', 'psa_name', 'psa_data', 'psa_synced_at']);
        });

        Schema::table('provinces', function (Blueprint $table) {
            $table->dropIndex(['psa_code']);
            $table->dropColumn(['psa_code', 'psa_name', 'income_class', 'psa_data', 'psa_synced_at']);
        });

        Schema::table('cities', function (Blueprint $table) {
            $table->dropIndex(['psa_code']);
            $table->dropColumn(['psa_code', 'psa_name', 'city_class', 'income_class', 'is_capital', 'psa_data', 'psa_synced_at']);
        });

        Schema::table('barangays', function (Blueprint $table) {
            $table->dropIndex(['psa_code']);
            $table->dropColumn(['psa_code', 'psa_name', 'urban_rural', 'psa_data', 'psa_synced_at']);
        });
    }
};