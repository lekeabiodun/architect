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
        Schema::create('leave_balances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->enum('leave_type', ['sick', 'vacation', 'personal', 'bereavement', 'maternity', 'paternity']);
            $table->decimal('balance_days', 8, 2)->default(0);
            $table->integer('year');
            $table->decimal('accrual_rate', 8, 2)->default(0)->comment('Days accrued per month');
            $table->decimal('used_days', 8, 2)->default(0);
            $table->timestamps();

            // Unique constraint to prevent duplicate balances
            $table->unique(['user_id', 'leave_type', 'year']);

            // Indexes for performance
            $table->index(['user_id', 'year']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('leave_balances');
    }
};
