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
        Schema::table('laptop_registrations', function (Blueprint $table) {
            $table->foreignId('laptop_id')->nullable()->constrained('laptops')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('laptop_registrations', function (Blueprint $table) {
            $table->dropForeign(['laptop_id']);
            $table->dropColumn('laptop_id');
        });
    }
};
