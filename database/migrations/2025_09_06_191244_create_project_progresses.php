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
        Schema::create('project_progresses', function (Blueprint $table) {
            $table->uuid('uuid')->unique()->index()->comment('Primary public identifier');
            $table->id();
            $table->foreignId('project_id')->constrained()->onDelete('cascade');
            $table->string('title')->comment('Progress update title');
            $table->text('description')->nullable()->comment('Progress description');
            $table->decimal('physical_progress', 5, 2)->nullable()->comment('Physical progress percentage');
            $table->decimal('financial_progress', 5, 2)->nullable()->comment('Financial progress percentage');
            $table->decimal('amount_utilized', 15, 2)->nullable()->comment('Amount utilized at this point');
            $table->date('progress_date')->comment('Date of this progress update');
            $table->string('status', 30)->nullable()->comment('Status at time of update');
            $table->json('attachments')->nullable()->comment('Progress photos/documents');
            $table->json('metadata')->nullable()->comment('Additional progress data');
            $table->boolean('is_published')->default(true);
            $table->timestamps();
            $table->softDeletes();
            
            $table->index(['project_id']);
            $table->index(['progress_date']);
            $table->index(['physical_progress']);
            $table->index(['financial_progress']);
            $table->index(['is_published']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('project_progresses');
    }
};
