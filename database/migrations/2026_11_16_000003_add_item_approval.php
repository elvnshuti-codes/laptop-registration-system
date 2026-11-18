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
        foreach (['visit_left_items', 'visit_arms'] as $tableName) {
            Schema::table($tableName, function (Blueprint $table) {
                // Supervisor verification: who, when, and whether the visitor had already left.
                $table->timestamp('approved_at')->nullable();
                $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
                $table->boolean('approved_after_exit')->default(false);
            });
        }

        // Two new permissions. The Supervisor role gets both; admin can grant them to anyone else.
        $names = ['verify gate items', 'view gate items report'];
        foreach ($names as $name) {
            Permission::findOrCreate($name, 'web');
        }

        $supervisor = Role::where('name', 'Supervisor')->where('guard_name', 'web')->first();
        $supervisor?->givePermissionTo($names);

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        foreach (['visit_left_items', 'visit_arms'] as $tableName) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->dropConstrainedForeignId('approved_by');
                $table->dropColumn(['approved_at', 'approved_after_exit']);
            });
        }

        Permission::whereIn('name', ['verify gate items', 'view gate items report'])->delete();
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};