<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('visitor_visits', function (Blueprint $table) {
            $table->string('sample_owner_email')->nullable();
            $table->string('sample_owner_tin', 30)->nullable();
            $table->string('sample_prn', 50)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('visitor_visits', function (Blueprint $table) {
            $table->dropColumn(['sample_owner_email', 'sample_owner_tin', 'sample_prn']);
        });
    }
};