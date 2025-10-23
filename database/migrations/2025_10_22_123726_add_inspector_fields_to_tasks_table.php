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
        Schema::table('tasks', function (Blueprint $table) {
            $table->foreignId('inspected_by')->nullable()->after('inspection_notes')->constrained('users')->onDelete('set null');
            $table->timestamp('inspected_at')->nullable()->after('inspected_by');
            $table->text('inspector_feedback')->nullable()->after('inspected_at');
            $table->boolean('requires_re_inspection')->default(false)->after('inspector_feedback');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->dropForeign(['inspected_by']);
            $table->dropColumn(['inspected_by', 'inspected_at', 'inspector_feedback', 'requires_re_inspection']);
        });
    }
};
