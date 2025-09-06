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
        Schema::create('contractors', function (Blueprint $table) {
            $table->id();
            $table->string('name')->comment('Contractor name');
            $table->string('name_abbreviation', 20)->nullable()->comment('Contractor abbreviation');
            $table->string('logo_url')->nullable()->comment('Contractor logo URL');
            $table->text('description')->nullable()->comment('Contractor description');
            $table->string('business_type', 50)->nullable()->comment('Type of business');
            $table->string('license_no', 50)->nullable()->comment('License number');
            $table->string('tin', 20)->nullable()->comment('Tax Identification Number');
            $table->string('website')->nullable()->comment('Company website');
            $table->string('email')->nullable()->comment('Contact email');
            $table->string('phone', 20)->nullable()->comment('Contact phone');
            $table->text('address')->nullable()->comment('Business address');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
            
            $table->index(['name']);
            $table->index(['name_abbreviation']);
            $table->index(['business_type']);
            $table->index(['license_no']);
            $table->index(['tin']);
            $table->index(['is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('contractors');
    }
};
