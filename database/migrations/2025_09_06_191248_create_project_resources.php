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
        Schema::create('project_resources', function (Blueprint $table) {
            $table->uuid('uuid')->unique()->index()->comment('Primary public identifier');
            $table->id();
            $table->foreignId('project_id')->constrained()->onDelete('cascade');
            $table->string('title')->comment('Resource title');
            $table->text('description')->nullable()->comment('Resource description');
            $table->string('type', 50)->comment('Resource type (document, image, video, etc.)');
            $table->string('file_url')->nullable()->comment('File URL');
            $table->string('file_name')->nullable()->comment('Original file name');
            $table->string('file_mime_type', 100)->nullable()->comment('File MIME type');
            $table->bigInteger('file_size')->nullable()->comment('File size in bytes');
            $table->string('external_url')->nullable()->comment('External resource URL');
            $table->json('metadata')->nullable()->comment('Additional resource metadata');
            $table->boolean('is_public')->default(true)->comment('Is resource publicly accessible');
            $table->integer('download_count')->default(0)->comment('Number of downloads');
            $table->timestamps();
            $table->softDeletes();
            
            $table->index(['project_id']);
            $table->index(['type']);
            $table->index(['is_public']);
            $table->index(['file_mime_type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('project_resources');
    }
};
