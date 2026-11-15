<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('visit_arms', function (Blueprint $table) {
            // Knives, tear gas and similar items have no serial number or bullets.
            $table->string('serial_number', 60)->nullable()->change();
            $table->unsignedSmallInteger('rounds')->nullable()->change();
            $table->unsignedSmallInteger('quantity')->default(1)->after('rounds');
        });
    }

    public function down(): void
    {
        Schema::table('visit_arms', function (Blueprint $table) {
            $table->dropColumn('quantity');
        });
    }
};