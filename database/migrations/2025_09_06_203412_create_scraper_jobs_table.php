<?php

use App\Enums\ScraperJobStatus;
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
        Schema::create('scraper_jobs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('source_id')->constrained('scraper_sources')->onDelete('cascade');
            $table->integer('start_id')->comment('Starting ID for the scraping range');
            $table->integer('end_id')->comment('Ending ID for the scraping range');
            $table->integer('current_id')->nullable()->comment('Current ID being processed');
            $table->integer('chunk_size')->default(100)->comment('Number of records to process in each batch');
            $table->enum('status', array_column(ScraperJobStatus::cases(), 'value'))
                ->default(ScraperJobStatus::PENDING->value)
                ->comment('Current status of the job');
            $table->timestamp('started_at')->nullable()->comment('When the job started');
            $table->timestamp('completed_at')->nullable()->comment('When the job completed');
            $table->integer('success_count')->default(0)->comment('Number of successfully scraped records');
            $table->integer('error_count')->default(0)->comment('Number of failed records');
            $table->integer('skip_count')->default(0)->comment('Number of skipped records');
            $table->integer('update_count')->default(0)->comment('Number of updated existing records');
            $table->integer('create_count')->default(0)->comment('Number of newly created records');
            $table->json('stats')->nullable()->comment('Additional statistics and metrics');
            $table->json('errors')->nullable()->comment('Error log for failed items');
            $table->text('notes')->nullable()->comment('Human-readable notes about the job');
            $table->string('triggered_by')->nullable()->comment('User or system that triggered the job');
            $table->timestamps();
            
            // Indexes for performance
            $table->index(['source_id', 'status']);
            $table->index('status');
            $table->index('started_at');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('scraper_jobs');
    }
};