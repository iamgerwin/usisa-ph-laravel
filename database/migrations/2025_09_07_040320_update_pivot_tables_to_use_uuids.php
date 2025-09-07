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
        // Update project_implementing_offices table to use UUIDs
        Schema::table('project_implementing_offices', function (Blueprint $table) {
            $table->uuid('project_uuid')->nullable()->after('uuid');
            $table->uuid('implementing_office_uuid')->nullable()->after('project_uuid');
        });

        // Populate UUID columns with actual UUIDs
        DB::statement("
            UPDATE project_implementing_offices 
            SET project_uuid = (SELECT uuid FROM projects WHERE projects.id = project_implementing_offices.project_id)
        ");
        
        DB::statement("
            UPDATE project_implementing_offices 
            SET implementing_office_uuid = (SELECT uuid FROM implementing_offices WHERE implementing_offices.id = project_implementing_offices.implementing_office_id)
        ");

        // Make UUID columns non-nullable
        Schema::table('project_implementing_offices', function (Blueprint $table) {
            $table->uuid('project_uuid')->nullable(false)->change();
            $table->uuid('implementing_office_uuid')->nullable(false)->change();
            
            // Add foreign key constraints
            $table->foreign('project_uuid')->references('uuid')->on('projects')->onDelete('cascade');
            $table->foreign('implementing_office_uuid')->references('uuid')->on('implementing_offices')->onDelete('cascade');
            
            // Drop old integer foreign keys
            $table->dropForeign(['project_id']);
            $table->dropForeign(['implementing_office_id']);
            $table->dropColumn(['project_id', 'implementing_office_id']);
        });

        // Update project_contractors table to use UUIDs
        Schema::table('project_contractors', function (Blueprint $table) {
            $table->uuid('project_uuid')->nullable()->after('uuid');
            $table->uuid('contractor_uuid')->nullable()->after('project_uuid');
        });

        // Populate UUID columns with actual UUIDs
        DB::statement("
            UPDATE project_contractors 
            SET project_uuid = (SELECT uuid FROM projects WHERE projects.id = project_contractors.project_id)
        ");
        
        DB::statement("
            UPDATE project_contractors 
            SET contractor_uuid = (SELECT uuid FROM contractors WHERE contractors.id = project_contractors.contractor_id)
        ");

        // Make UUID columns non-nullable and add constraints
        Schema::table('project_contractors', function (Blueprint $table) {
            $table->uuid('project_uuid')->nullable(false)->change();
            $table->uuid('contractor_uuid')->nullable(false)->change();
            
            // Add foreign key constraints
            $table->foreign('project_uuid')->references('uuid')->on('projects')->onDelete('cascade');
            $table->foreign('contractor_uuid')->references('uuid')->on('contractors')->onDelete('cascade');
            
            // Drop old integer foreign keys
            $table->dropForeign(['project_id']);
            $table->dropForeign(['contractor_id']);
            $table->dropColumn(['project_id', 'contractor_id']);
        });

        // Update project_source_of_funds table to use UUIDs
        Schema::table('project_source_of_funds', function (Blueprint $table) {
            $table->uuid('project_uuid')->nullable()->after('uuid');
            $table->uuid('source_of_fund_uuid')->nullable()->after('project_uuid');
        });

        // Populate UUID columns with actual UUIDs
        DB::statement("
            UPDATE project_source_of_funds 
            SET project_uuid = (SELECT uuid FROM projects WHERE projects.id = project_source_of_funds.project_id)
        ");
        
        DB::statement("
            UPDATE project_source_of_funds 
            SET source_of_fund_uuid = (SELECT uuid FROM source_of_funds WHERE source_of_funds.id = project_source_of_funds.source_of_fund_id)
        ");

        // Make UUID columns non-nullable and add constraints
        Schema::table('project_source_of_funds', function (Blueprint $table) {
            $table->uuid('project_uuid')->nullable(false)->change();
            $table->uuid('source_of_fund_uuid')->nullable(false)->change();
            
            // Add foreign key constraints
            $table->foreign('project_uuid')->references('uuid')->on('projects')->onDelete('cascade');
            $table->foreign('source_of_fund_uuid')->references('uuid')->on('source_of_funds')->onDelete('cascade');
            
            // Drop old integer foreign keys
            $table->dropForeign(['project_id']);
            $table->dropForeign(['source_of_fund_id']);
            $table->dropColumn(['project_id', 'source_of_fund_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // project_implementing_offices - restore integer columns
        Schema::table('project_implementing_offices', function (Blueprint $table) {
            $table->unsignedBigInteger('project_id')->nullable()->after('uuid');
            $table->unsignedBigInteger('implementing_office_id')->nullable()->after('project_id');
        });

        DB::statement("
            UPDATE project_implementing_offices 
            SET project_id = (SELECT id FROM projects WHERE projects.uuid = project_implementing_offices.project_uuid)
        ");
        
        DB::statement("
            UPDATE project_implementing_offices 
            SET implementing_office_id = (SELECT id FROM implementing_offices WHERE implementing_offices.uuid = project_implementing_offices.implementing_office_uuid)
        ");

        Schema::table('project_implementing_offices', function (Blueprint $table) {
            $table->unsignedBigInteger('project_id')->nullable(false)->change();
            $table->unsignedBigInteger('implementing_office_id')->nullable(false)->change();
            
            $table->foreign('project_id')->references('id')->on('projects')->onDelete('cascade');
            $table->foreign('implementing_office_id')->references('id')->on('implementing_offices')->onDelete('cascade');
            
            $table->dropForeign(['project_uuid']);
            $table->dropForeign(['implementing_office_uuid']);
            $table->dropColumn(['project_uuid', 'implementing_office_uuid']);
        });

        // project_contractors - restore integer columns
        Schema::table('project_contractors', function (Blueprint $table) {
            $table->unsignedBigInteger('project_id')->nullable()->after('uuid');
            $table->unsignedBigInteger('contractor_id')->nullable()->after('project_id');
        });

        DB::statement("
            UPDATE project_contractors 
            SET project_id = (SELECT id FROM projects WHERE projects.uuid = project_contractors.project_uuid)
        ");
        
        DB::statement("
            UPDATE project_contractors 
            SET contractor_id = (SELECT id FROM contractors WHERE contractors.uuid = project_contractors.contractor_uuid)
        ");

        Schema::table('project_contractors', function (Blueprint $table) {
            $table->unsignedBigInteger('project_id')->nullable(false)->change();
            $table->unsignedBigInteger('contractor_id')->nullable(false)->change();
            
            $table->foreign('project_id')->references('id')->on('projects')->onDelete('cascade');
            $table->foreign('contractor_id')->references('id')->on('contractors')->onDelete('cascade');
            
            $table->dropForeign(['project_uuid']);
            $table->dropForeign(['contractor_uuid']);
            $table->dropColumn(['project_uuid', 'contractor_uuid']);
        });

        // project_source_of_funds - restore integer columns
        Schema::table('project_source_of_funds', function (Blueprint $table) {
            $table->unsignedBigInteger('project_id')->nullable()->after('uuid');
            $table->unsignedBigInteger('source_of_fund_id')->nullable()->after('project_id');
        });

        DB::statement("
            UPDATE project_source_of_funds 
            SET project_id = (SELECT id FROM projects WHERE projects.uuid = project_source_of_funds.project_uuid)
        ");
        
        DB::statement("
            UPDATE project_source_of_funds 
            SET source_of_fund_id = (SELECT id FROM source_of_funds WHERE source_of_funds.uuid = project_source_of_funds.source_of_fund_uuid)
        ");

        Schema::table('project_source_of_funds', function (Blueprint $table) {
            $table->unsignedBigInteger('project_id')->nullable(false)->change();
            $table->unsignedBigInteger('source_of_fund_id')->nullable(false)->change();
            
            $table->foreign('project_id')->references('id')->on('projects')->onDelete('cascade');
            $table->foreign('source_of_fund_id')->references('id')->on('source_of_funds')->onDelete('cascade');
            
            $table->dropForeign(['project_uuid']);
            $table->dropForeign(['source_of_fund_uuid']);
            $table->dropColumn(['project_uuid', 'source_of_fund_uuid']);
        });
    }
};
