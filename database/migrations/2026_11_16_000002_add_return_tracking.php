<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        foreach (['visit_left_items', 'visit_arms'] as $tableName) {
            Schema::table($tableName, function (Blueprint $table) {
                // Set when the item is handed back at check-out.
                $table->timestamp('returned_at')->nullable();
                // The Gate officer who handed it back, or who recorded why it was not returned.
                $table->foreignId('returned_by')->nullable()->constrained('users')->nullOnDelete();
                $table->string('not_returned_reason', 255)->nullable();
            });
        }
    }

    public function down(): void
    {
        foreach (['visit_left_items', 'visit_arms'] as $tableName) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->dropConstrainedForeignId('returned_by');
                $table->dropColumn(['returned_at', 'not_returned_reason']);
            });
        }
    }
};