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
        Schema::create('source_of_funds', function (Blueprint $table) {
            $table->id();
            $table->string('name')->comment('Source of funds name');
            $table->string('name_abbreviation', 20)->nullable()->comment('Source abbreviation');
            $table->string('logo_url')->nullable()->comment('Source logo URL');
            $table->text('description')->nullable()->comment('Source description');
            $table->string('type', 50)->nullable()->comment('Fund type (e.g., GAA, Loan, Grant)');
            $table->string('fiscal_year', 10)->nullable()->comment('Associated fiscal year');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
            
            $table->index(['name']);
            $table->index(['name_abbreviation']);
            $table->index(['type']);
            $table->index(['fiscal_year']);
            $table->index(['is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('source_of_funds');
    }
};
