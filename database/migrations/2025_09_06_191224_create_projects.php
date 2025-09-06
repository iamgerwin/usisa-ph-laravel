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
        Schema::create('projects', function (Blueprint $table) {
            $table->id();
            $table->string('project_name')->comment('Official project name');
            $table->string('project_code', 50)->unique()->comment('Unique project code');
            $table->text('description')->nullable()->comment('Project description');
            $table->string('project_image_url')->nullable()->comment('Project banner image URL');
            $table->string('slug')->unique()->comment('URL slug');
            
            // Program relationship
            $table->foreignId('program_id')->nullable()->constrained()->onDelete('set null');
            
            // Location details
            $table->foreignId('region_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('province_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('city_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('barangay_id')->nullable()->constrained()->onDelete('set null');
            $table->text('street_address')->nullable()->comment('Specific street address');
            $table->string('zip_code', 10)->nullable()->comment('Postal code');
            
            // Geographic coordinates
            $table->decimal('latitude', 10, 8)->nullable()->comment('Latitude coordinate');
            $table->decimal('longitude', 11, 8)->nullable()->comment('Longitude coordinate');
            
            // Project status and publication
            $table->string('status', 50)->default('Not Yet Started')->comment('Current project status');
            $table->string('publication_status', 20)->default('Draft')->comment('Publication status');
            
            // Financial information
            $table->decimal('cost', 15, 2)->default(0)->comment('Total project cost');
            $table->decimal('utilized_amount', 15, 2)->default(0)->comment('Amount utilized so far');
            $table->date('last_updated_project_cost')->nullable()->comment('Last cost update date');
            
            // Timeline information
            $table->date('date_started')->nullable()->comment('Planned start date');
            $table->date('actual_date_started')->nullable()->comment('Actual start date');
            $table->date('contract_completion_date')->nullable()->comment('Contract completion date');
            $table->date('actual_contract_completion_date')->nullable()->comment('Actual completion date');
            $table->date('as_of_date')->nullable()->comment('Data as of date');
            
            // Counters and metadata
            $table->integer('updates_count')->default(0)->comment('Number of project updates');
            
            // Admin fields
            $table->boolean('is_active')->default(true);
            $table->boolean('is_featured')->default(false)->comment('Featured project flag');
            $table->json('metadata')->nullable()->comment('Additional project metadata');
            
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes for better performance
            $table->index(['project_code']);
            $table->index(['program_id']);
            $table->index(['region_id', 'province_id', 'city_id']);
            $table->index(['status']);
            $table->index(['publication_status']);
            $table->index(['cost']);
            $table->index(['date_started']);
            $table->index(['is_active']);
            $table->index(['is_featured']);
            $table->index(['slug']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('projects');
    }
};
