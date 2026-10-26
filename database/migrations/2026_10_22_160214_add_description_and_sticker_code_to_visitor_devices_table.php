<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('visitor_devices', function (Blueprint $table) {
            $table->string('description')->nullable()->after('asset_tag');
            // Unique: a single-use sticker can only ever be bound to one device.
            $table->string('sticker_code')->nullable()->unique()->after('description');
        });
    }

    public function down(): void
    {
        Schema::table('visitor_devices', function (Blueprint $table) {
            $table->dropUnique(['sticker_code']);
            $table->dropColumn(['description', 'sticker_code']);
        });
    }
};