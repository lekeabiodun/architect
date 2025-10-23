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
        Schema::create('materials', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code')->unique()->nullable(); // Material code/SKU
            $table->text('description')->nullable();
            $table->string('unit'); // units: kg, m3, pieces, liters, etc.
            $table->decimal('unit_cost', 15, 2)->default(0);
            $table->string('category')->nullable(); // cement, steel, lumber, electrical, etc.
            $table->integer('reorder_level')->default(0); // Minimum stock level
            $table->text('specifications')->nullable(); // Technical specs
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('materials');
    }
};
