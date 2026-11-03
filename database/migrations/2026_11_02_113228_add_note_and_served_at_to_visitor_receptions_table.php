<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('visitor_receptions', function (Blueprint $table) {
            $table->text('note')->nullable()->after('served');
            $table->timestamp('served_at')->nullable()->after('note');
        });
    }

    public function down(): void
    {
        Schema::table('visitor_receptions', function (Blueprint $table) {
            $table->dropColumn(['note', 'served_at']);
        });
    }
};