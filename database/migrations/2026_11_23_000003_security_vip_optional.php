<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('visit_security_details', function (Blueprint $table) {
            $table->string('vip_name')->nullable()->change();
            $table->string('vip_title')->nullable()->change();
        });
    }

    public function down(): void
    {
    }
};