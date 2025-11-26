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
        Schema::create('bill_of_quantities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->onDelete('cascade');
            $table->string('item_code')->nullable();
            $table->string('description');
            $table->string('unit');
            $table->decimal('quantity', 12, 2);
            $table->decimal('unit_rate', 12, 2);
            $table->decimal('total_amount', 12, 2);
            $table->decimal('requestable_quantity', 12, 2)->default(0);
            $table->decimal('consumed_quantity', 12, 2)->default(0);
            $table->decimal('remaining_quantity', 12, 2)->virtualAs('requestable_quantity - consumed_quantity');
            $table->string('category')->nullable();
            $table->text('notes')->nullable();
            $table->integer('order')->default(0);
            $table->timestamps();

            $table->index(['project_id', 'category']);
            $table->index(['project_id', 'order']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bill_of_quantities');
    }
};
