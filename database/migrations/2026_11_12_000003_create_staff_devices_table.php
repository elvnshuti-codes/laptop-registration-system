<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('staff_devices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees')->restrictOnDelete();
            // What the barcode reads: the UNBS asset tag (organization) or PL-/PT- + serial (personal).
            $table->string('sticker_code', 120)->unique();
            $table->string('owner_type', 20);                    // organization | personal
            $table->string('device_type', 30);                   // Laptop | Tablet
            $table->string('make_model', 100)->nullable();       // optional, e.g. Lenovo ThinkPad
            $table->string('serial_number', 60)->unique();
            $table->string('asset_tag', 60)->nullable()->unique(); // organization devices only
            $table->boolean('is_active')->default(true);         // false = retired
            $table->timestamp('sticker_printed_at')->nullable();
            $table->foreignId('registered_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['employee_id', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('staff_devices');
    }
};