<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Pre-printed stickers for personal gadgets that are not in the system yet.
        Schema::create('gadget_stickers', function (Blueprint $table) {
            $table->id();
            $table->string('sticker_code', 60)->unique();                 // UNBS-WG-0001
            $table->string('status', 10)->default('unused');              // unused | used | void
            $table->foreignId('staff_device_id')->nullable()->constrained('staff_devices')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('used_at')->nullable();
            $table->timestamps();
        });

        // Extra facts recorded for a walk-in gadget.
        Schema::table('staff_devices', function (Blueprint $table) {
            $table->string('colour', 40)->nullable();
            $table->string('seal_number', 60)->nullable();
            $table->boolean('is_walk_in')->default(false);
        });
    }

    public function down(): void
    {
        Schema::table('staff_devices', function (Blueprint $table) {
            $table->dropColumn(['colour', 'seal_number', 'is_walk_in']);
        });
        Schema::dropIfExists('gadget_stickers');
    }
};