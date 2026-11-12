<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    // The Supervisor role should not see the old Registrations page by default.
    // The administrator now grants it per person on the User access screen.
    public function up(): void
    {
        $role = Role::where('name', 'Supervisor')->where('guard_name', 'web')->first();

        foreach (['view registrations', 'manage registrations'] as $permission) {
            if ($role && $role->hasPermissionTo($permission)) {
                $role->revokePermissionTo($permission);
            }
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
    }
};