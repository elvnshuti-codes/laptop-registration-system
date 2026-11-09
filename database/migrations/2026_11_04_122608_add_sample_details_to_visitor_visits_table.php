<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('visitor_visits', function (Blueprint $table) {
            $table->boolean('for_sample_reception')->default(false)->index();
            $table->string('sample_type', 100)->nullable();
            $table->unsignedSmallInteger('sample_count')->nullable();
            $table->string('sample_owner_name')->nullable();
            $table->string('sample_owner_company')->nullable();
            $table->string('sample_owner_phone', 20)->nullable();
            $table->string('delivered_by_type', 30)->nullable();
            $table->string('delivered_by_company')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('visitor_visits', function (Blueprint $table) {
            $table->dropColumn([
                'for_sample_reception', 'sample_type', 'sample_count',
                'sample_owner_name', 'sample_owner_company', 'sample_owner_phone',
                'delivered_by_type', 'delivered_by_company',
            ]);
        });
    }
};