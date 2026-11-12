<?php

use App\Support\ReportAccess;
use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    public function up(): void
    {
        foreach (ReportAccess::all() as $name) {
            Permission::findOrCreate($name, 'web');
        }

        // What receptionists could see before, now given to each person (not the role).
        $defaults = [
            'Receptionist' => ['view reports', 'view visitor records', 'export reports', 'view standard house data'],
            'Sample Reception Officer' => ['view reports', 'view visitor records', 'export reports', 'view sample house data'],
        ];

        foreach ($defaults as $roleName => $permissions) {
            $role = Role::where('name', $roleName)->where('guard_name', 'web')->first();

            if (! $role) {
                continue;
            }

            foreach ($role->users as $user) {
                $user->givePermissionTo($permissions);
            }

            $role->revokePermissionTo('view reports');
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        // Nothing to undo: the permissions are harmless if left in place.
    }
};