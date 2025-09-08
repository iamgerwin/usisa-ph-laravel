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
        Schema::create('provinces', function (Blueprint $table) {
            $table->uuid('uuid')->unique()->index()->comment('Primary public identifier');
            $table->id();
            $table->foreignId('region_id')->constrained()->onDelete('cascade');
            $table->string('code', 20)->unique()->comment('PSGC province code');
            $table->string('name')->comment('Province name');
            $table->string('abbreviation', 10)->nullable()->comment('Province abbreviation');
            $table->integer('sort_order')->default(0)->comment('Display sort order');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
            
            $table->index(['region_id']);
            $table->index(['code']);
            $table->index(['name']);
            $table->index(['is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('provinces');
    }
};
