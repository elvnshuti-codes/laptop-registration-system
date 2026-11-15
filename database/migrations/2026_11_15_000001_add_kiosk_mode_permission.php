<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    public function up(): void
    {
        $permission = Permission::findOrCreate('use kiosk mode', 'web');

        foreach (['Gate Officer', 'Entry Officer'] as $name) {
            Role::where('name', $name)->first()?->givePermissionTo($permission);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        Permission::where('name', 'use kiosk mode')->delete();
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};