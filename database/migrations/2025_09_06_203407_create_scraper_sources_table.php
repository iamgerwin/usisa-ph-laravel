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
        Schema::create('scraper_sources', function (Blueprint $table) {
            $table->uuid('uuid')->unique()->index()->comment('Primary public identifier');
            $table->id();
            $table->string('code', 50)->unique()->comment('Unique identifier for the scraper source');
            $table->string('name')->comment('Human-readable name of the source');
            $table->text('base_url')->comment('Base URL of the API or website');
            $table->text('endpoint_pattern')->nullable()->comment('Pattern for constructing endpoints, e.g., /project/{id}.json');
            $table->boolean('is_active')->default(true)->comment('Whether this source is currently active');
            $table->integer('rate_limit')->default(60)->comment('Maximum requests per minute');
            $table->integer('timeout')->default(30)->comment('Request timeout in seconds');
            $table->integer('retry_attempts')->default(3)->comment('Number of retry attempts on failure');
            $table->json('headers')->nullable()->comment('Default headers for requests');
            $table->json('field_mapping')->nullable()->comment('Mapping of source fields to our database fields');
            $table->json('metadata')->nullable()->comment('Additional configuration and settings');
            $table->string('scraper_class')->nullable()->comment('Strategy class name for this source');
            $table->string('version')->nullable()->comment('API version tracking');
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes for performance
            $table->index('code');
            $table->index('is_active');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('scraper_sources');
    }
};