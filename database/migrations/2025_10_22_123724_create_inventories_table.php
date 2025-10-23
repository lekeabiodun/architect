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
        Schema::create('inventories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('material_id')->constrained()->onDelete('cascade');
            $table->foreignId('project_id')->constrained()->onDelete('cascade');
            $table->foreignId('phase_id')->nullable()->constrained()->onDelete('cascade');
            $table->foreignId('task_id')->nullable()->constrained()->onDelete('cascade');
            
            $table->decimal('quantity', 15, 2)->default(0);
            $table->decimal('allocated_quantity', 15, 2)->default(0); // Reserved/allocated
            $table->decimal('used_quantity', 15, 2)->default(0); // Actually used
            
            $table->string('location')->nullable(); // Storage location
            $table->string('status')->default('available'); // available, allocated, depleted
            $table->text('notes')->nullable();
            
            $table->timestamps();
            
            // Ensure unique material per project/phase/task combination
            $table->unique(['material_id', 'project_id', 'phase_id', 'task_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inventories');
    }
};
