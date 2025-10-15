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
        Schema::create('projects', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('client_name');
            $table->string('location')->nullable();
            $table->string('status')->default('active'); // active, on_hold, completed, cancelled
            
            // Dates
            $table->date('planned_start_date')->nullable();
            $table->date('planned_end_date')->nullable();
            $table->date('actual_start_date')->nullable();
            $table->date('actual_end_date')->nullable();
            
            // Budget
            $table->decimal('estimated_budget', 15, 2)->nullable();
            $table->decimal('actual_cost', 15, 2)->default(0);
            
            // Progress
            $table->decimal('overall_progress', 5, 2)->default(0); // 0-100%
            
            // Owner/Manager
            $table->foreignId('manager_id')->nullable()->constrained('users')->onDelete('set null');
            
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('projects');
    }
};
