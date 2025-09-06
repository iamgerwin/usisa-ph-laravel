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
        Schema::create('cities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('province_id')->constrained()->onDelete('cascade');
            $table->string('code', 20)->unique()->comment('PSGC city/municipality code');
            $table->string('name')->comment('City/Municipality name');
            $table->string('type', 20)->default('municipality')->comment('city or municipality');
            $table->string('zip_code', 10)->nullable()->comment('Postal code');
            $table->integer('sort_order')->default(0)->comment('Display sort order');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
            
            $table->index(['province_id']);
            $table->index(['code']);
            $table->index(['name']);
            $table->index(['type']);
            $table->index(['zip_code']);
            $table->index(['is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cities');
    }
};
