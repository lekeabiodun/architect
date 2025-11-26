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
        Schema::create('material_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->onDelete('cascade');
            $table->foreignId('phase_id')->nullable()->constrained()->onDelete('cascade');
            $table->foreignId('task_id')->nullable()->constrained()->onDelete('cascade');
            $table->foreignId('bill_of_quantity_id')->nullable()->constrained()->onDelete('cascade');

            // Request details
            $table->decimal('requested_quantity', 15, 2);
            $table->decimal('approved_quantity', 15, 2)->nullable();
            $table->decimal('disbursed_quantity', 15, 2)->default(0);
            $table->date('required_date')->nullable();
            $table->text('purpose')->nullable();
            $table->text('justification')->nullable();

            // Workflow
            $table->foreignId('requested_by')->constrained('users')->onDelete('cascade');
            $table->foreignId('approved_by')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('disbursed_by')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('confirmed_by')->nullable()->constrained('users')->onDelete('set null'); // Inspector confirmation

            $table->string('status')->default('pending'); // pending, approved, rejected, disbursed, confirmed, cancelled
            $table->text('approval_notes')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->text('disbursement_notes')->nullable();
            $table->text('confirmation_notes')->nullable();

            $table->timestamp('approved_at')->nullable();
            $table->timestamp('disbursed_at')->nullable();
            $table->timestamp('confirmed_at')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('material_requests');
    }
};
