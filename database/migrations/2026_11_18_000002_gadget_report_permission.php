<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    public function up(): void
    {
        // Admin always sees the report; anyone else only if Admin grants this permission.
        Permission::findOrCreate('view gadgets report', 'web');
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        Permission::where('name', 'view gadgets report')->delete();
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};