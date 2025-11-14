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
        Schema::create('time_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->dateTime('clock_in');
            $table->dateTime('clock_out')->nullable();
            $table->integer('break_duration')->default(0)->comment('Break duration in minutes');
            $table->text('notes')->nullable();
            $table->foreignId('project_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('task_id')->nullable()->constrained()->onDelete('set null');
            $table->string('location')->nullable();
            $table->decimal('overtime_hours', 8, 2)->default(0);
            $table->foreignId('edited_by')->nullable()->constrained('users')->onDelete('set null');
            $table->text('edit_reason')->nullable();
            $table->timestamps();

            // Indexes for performance
            $table->index(['user_id', 'clock_in']);
            $table->index(['clock_in', 'clock_out']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('time_entries');
    }
};
