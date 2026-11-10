<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('visitor_visits', function (Blueprint $table) {
            // Set when Entry has released the visitor for exit at the Gate.
            $table->timestamp('entry_cleared_at')->nullable();
            $table->foreignId('entry_cleared_by')->nullable()->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('visitor_visits', function (Blueprint $table) {
            $table->dropConstrainedForeignId('entry_cleared_by');
            $table->dropColumn('entry_cleared_at');
        });
    }
};