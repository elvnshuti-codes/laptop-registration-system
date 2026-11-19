<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // The Gate keeps one short optional note; the receptions record the full details.
        Schema::table('visitor_visits', function (Blueprint $table) {
            $table->string('gate_note', 500)->nullable();
            $table->string('delivery_to', 12)->nullable();          // individual | stores
        });

        Schema::table('visit_details', function (Blueprint $table) {
            $table->string('delivery_company')->nullable();          // who is making the delivery
        });

        // Which purpose is a delivery. Admin can still rename it; the flag stays.
        Schema::table('visit_purposes', function (Blueprint $table) {
            $table->boolean('is_delivery')->default(false);
        });

        DB::table('visit_purposes')->where('name', 'Delivery')->update(['is_delivery' => true]);
    }

    public function down(): void
    {
        Schema::table('visit_purposes', fn (Blueprint $table) => $table->dropColumn('is_delivery'));
        Schema::table('visit_details', fn (Blueprint $table) => $table->dropColumn('delivery_company'));
        Schema::table('visitor_visits', fn (Blueprint $table) => $table->dropColumn(['gate_note', 'delivery_to']));
    }
};