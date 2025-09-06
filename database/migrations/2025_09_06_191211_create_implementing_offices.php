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
        Schema::create('implementing_offices', function (Blueprint $table) {
            $table->id();
            $table->string('name')->comment('Office name');
            $table->string('name_abbreviation', 20)->nullable()->comment('Office abbreviation');
            $table->string('logo_url')->nullable()->comment('Office logo URL');
            $table->text('description')->nullable()->comment('Office description');
            $table->string('website')->nullable()->comment('Official website');
            $table->string('email')->nullable()->comment('Contact email');
            $table->string('phone', 20)->nullable()->comment('Contact phone');
            $table->text('address')->nullable()->comment('Office address');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
            
            $table->index(['name']);
            $table->index(['name_abbreviation']);
            $table->index(['is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('implementing_offices');
    }
};
