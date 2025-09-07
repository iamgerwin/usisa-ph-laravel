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
        Schema::table('source_of_funds', function (Blueprint $table) {
            $table->string('dime_id')->unique()->nullable()->after('id')->comment('DIME.gov.ph unique identifier');
        });
        
        Schema::table('contractors', function (Blueprint $table) {
            $table->string('dime_id')->unique()->nullable()->after('id')->comment('DIME.gov.ph unique identifier');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('source_of_funds', function (Blueprint $table) {
            $table->dropColumn('dime_id');
        });
        
        Schema::table('contractors', function (Blueprint $table) {
            $table->dropColumn('dime_id');
        });
    }
};