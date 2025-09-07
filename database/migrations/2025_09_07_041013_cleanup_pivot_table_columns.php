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
        // Remove old uuid and id columns from pivot tables since we're using UUID relationships
        Schema::table('project_implementing_offices', function (Blueprint $table) {
            $table->dropColumn(['uuid', 'id']);
        });

        Schema::table('project_contractors', function (Blueprint $table) {
            $table->dropColumn(['uuid', 'id']);
        });

        Schema::table('project_source_of_funds', function (Blueprint $table) {
            $table->dropColumn(['uuid', 'id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Re-add uuid and id columns
        Schema::table('project_implementing_offices', function (Blueprint $table) {
            $table->uuid('uuid')->first();
            $table->bigIncrements('id')->after('uuid');
        });

        Schema::table('project_contractors', function (Blueprint $table) {
            $table->uuid('uuid')->first();
            $table->bigIncrements('id')->after('uuid');
        });

        Schema::table('project_source_of_funds', function (Blueprint $table) {
            $table->uuid('uuid')->first();
            $table->bigIncrements('id')->after('uuid');
        });
    }
};