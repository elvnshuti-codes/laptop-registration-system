<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('staff_entries', function (Blueprint $table) {
            $table->id();
            $table->string('token', 20)->unique();                 // ST-XXXXXXXXXX, shown only as a barcode
            $table->foreignId('employee_id')->constrained('employees')->restrictOnDelete();
            // Holds the employee id while the entry is open, NULL once closed: the unique index allows only one open entry per employee.
            $table->unsignedBigInteger('open_employee_id')->nullable()->unique();
            $table->string('location', 20)->default('entry');      // entry or rig
            $table->foreignId('checked_in_by')->constrained('users')->restrictOnDelete();
            $table->timestamp('checked_in_at');
            $table->foreignId('checked_out_by')->nullable()->constrained('users')->restrictOnDelete();
            $table->timestamp('checked_out_at')->nullable();
            $table->timestamps();
        });

        Schema::create('staff_entry_devices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('staff_entry_id')->constrained('staff_entries')->cascadeOnDelete();
            $table->foreignId('staff_device_id')->constrained('staff_devices')->restrictOnDelete();
            // Same trick: a device can be on only one open entry at a time.
            $table->unsignedBigInteger('open_device_id')->nullable()->unique();
            $table->timestamp('checked_out_at')->nullable();
            $table->timestamps();

            $table->unique(['staff_entry_id', 'staff_device_id']);
        });

        $permission = Permission::findOrCreate('manage staff entry', 'web');
        Role::where('name', 'Entry Officer')->first()?->givePermissionTo($permission);

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        Schema::dropIfExists('staff_entry_devices');
        Schema::dropIfExists('staff_entries');
        Permission::where('name', 'manage staff entry')->delete();
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};