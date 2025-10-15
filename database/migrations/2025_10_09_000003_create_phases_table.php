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
        Schema::create('phases', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->onDelete('cascade');
            $table->string('name'); // Foundation, Framing, Roofing, etc.
            $table->text('description')->nullable();
            $table->integer('order')->default(0); // Display order
            $table->decimal('weight', 5, 2)->default(0); // Percentage weight of total project (0-100)
            $table->decimal('progress', 5, 2)->default(0); // 0-100%
            $table->string('status')->default('pending'); // pending, in_progress, completed
            
            // Dates
            $table->date('planned_start_date')->nullable();
            $table->date('planned_end_date')->nullable();
            $table->date('actual_start_date')->nullable();
            $table->date('actual_end_date')->nullable();
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('phases');
    }
};
