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
        Schema::create('project_implementing_offices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->onDelete('cascade');
            $table->foreignId('implementing_office_id')->constrained()->onDelete('cascade');
            $table->string('role', 50)->nullable()->comment('Role of office in project');
            $table->boolean('is_primary')->default(false)->comment('Is this the primary implementing office');
            $table->timestamps();
            
            $table->unique(['project_id', 'implementing_office_id'], 'proj_impl_office_unique');
            $table->index(['project_id']);
            $table->index(['implementing_office_id']);
            $table->index(['is_primary']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('project_implementing_offices');
    }
};
