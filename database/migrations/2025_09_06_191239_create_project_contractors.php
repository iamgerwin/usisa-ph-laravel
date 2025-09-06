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
        Schema::create('project_contractors', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->onDelete('cascade');
            $table->foreignId('contractor_id')->constrained()->onDelete('cascade');
            $table->string('contractor_type', 50)->default('Primary')->comment('Type of contractor role');
            $table->decimal('contract_amount', 15, 2)->nullable()->comment('Contract amount');
            $table->date('contract_start_date')->nullable()->comment('Contract start date');
            $table->date('contract_end_date')->nullable()->comment('Contract end date');
            $table->string('contract_number', 100)->nullable()->comment('Contract reference number');
            $table->string('status', 30)->default('Active')->comment('Contract status');
            $table->timestamps();
            
            $table->unique(['project_id', 'contractor_id'], 'proj_contractor_unique');
            $table->index(['project_id']);
            $table->index(['contractor_id']);
            $table->index(['contractor_type']);
            $table->index(['status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('project_contractors');
    }
};
