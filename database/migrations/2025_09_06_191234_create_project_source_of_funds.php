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
        Schema::create('project_source_of_funds', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->onDelete('cascade');
            $table->foreignId('source_of_fund_id')->constrained('source_of_funds')->onDelete('cascade');
            $table->decimal('allocated_amount', 15, 2)->nullable()->comment('Amount allocated from this source');
            $table->decimal('utilized_amount', 15, 2)->default(0)->comment('Amount utilized from this source');
            $table->string('allocation_type', 50)->nullable()->comment('Type of allocation');
            $table->boolean('is_primary')->default(false)->comment('Is this the primary funding source');
            $table->timestamps();
            
            $table->unique(['project_id', 'source_of_fund_id'], 'proj_fund_source_unique');
            $table->index(['project_id']);
            $table->index(['source_of_fund_id']);
            $table->index(['is_primary']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('project_source_of_funds');
    }
};
