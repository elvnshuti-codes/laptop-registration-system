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
        Schema::create('employees', function (Blueprint $table) {
            $table->id();
            $table->string('employee_id_number', 30)->unique();
            $table->string('name');
            $table->string('department');
            $table->string('position', 60);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index('name');
        });

        // Who may manage the staff list, staff devices and staff stickers. The Administrator always can.
        Permission::findOrCreate('manage staff devices', 'web');
        Role::where('name', 'Registration Officer')->where('guard_name', 'web')->first()?->givePermissionTo('manage staff devices');
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        Schema::dropIfExists('employees');
    }
};