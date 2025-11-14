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
        Schema::table('bill_of_quantities', function (Blueprint $table) {
            $table->decimal('requestable_quantity', 12, 2)->default(0)->after('total_amount');
            $table->decimal('consumed_quantity', 12, 2)->default(0)->after('requestable_quantity');
            $table->decimal('remaining_quantity', 12, 2)->virtualAs('requestable_quantity - consumed_quantity')->after('consumed_quantity');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bill_of_quantities', function (Blueprint $table) {
            $table->dropColumn(['requestable_quantity', 'consumed_quantity', 'remaining_quantity']);
        });
    }
};
