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
        // Fix island_group_code field length in all tables
        Schema::table('regions', function (Blueprint $table) {
            $table->string('island_group_code', 50)->nullable()->change();
        });

        Schema::table('provinces', function (Blueprint $table) {
            $table->string('island_group_code', 50)->nullable()->change();
        });

        Schema::table('cities', function (Blueprint $table) {
            $table->string('island_group_code', 50)->nullable()->change();
        });

        Schema::table('barangays', function (Blueprint $table) {
            $table->string('island_group_code', 50)->nullable()->change();
        });

        // Also fix for the new tables
        if (Schema::hasTable('districts')) {
            Schema::table('districts', function (Blueprint $table) {
                $table->string('island_group_code', 50)->nullable()->change();
            });
        }

        if (Schema::hasTable('sub_municipalities')) {
            Schema::table('sub_municipalities', function (Blueprint $table) {
                $table->string('island_group_code', 50)->nullable()->change();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert back to original length
        Schema::table('regions', function (Blueprint $table) {
            $table->string('island_group_code', 20)->nullable()->change();
        });

        Schema::table('provinces', function (Blueprint $table) {
            $table->string('island_group_code', 20)->nullable()->change();
        });

        Schema::table('cities', function (Blueprint $table) {
            $table->string('island_group_code', 20)->nullable()->change();
        });

        Schema::table('barangays', function (Blueprint $table) {
            $table->string('island_group_code', 20)->nullable()->change();
        });

        if (Schema::hasTable('districts')) {
            Schema::table('districts', function (Blueprint $table) {
                $table->string('island_group_code', 20)->nullable()->change();
            });
        }

        if (Schema::hasTable('sub_municipalities')) {
            Schema::table('sub_municipalities', function (Blueprint $table) {
                $table->string('island_group_code', 20)->nullable()->change();
            });
        }
    }
};