<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Every visit belongs to one area. Existing visits are filled in from the old flag.
        Schema::table('visitor_visits', function (Blueprint $table) {
            $table->string('site', 20)->default('standard_house')->index();
        });

        DB::table('visitor_visits')->where('for_sample_reception', true)->update(['site' => 'sample_house']);

        // 2. Where each purpose is offered. Everything stays offered at the main site, as today.
        Schema::table('visit_purposes', function (Blueprint $table) {
            $table->boolean('at_main')->default(true);
            $table->boolean('at_rig')->default(false);
        });

        // Offered at the Rig as well.
        DB::table('visit_purposes')->whereIn('name', ['Delivery', 'Meeting', 'Security'])->update(['at_rig' => true]);

        // Rig-only purposes.
        foreach (['Calibration', 'Gym'] as $name) {
            DB::table('visit_purposes')->updateOrInsert(
                ['name' => $name],
                ['is_system' => false, 'is_testing' => false, 'at_main' => false, 'at_rig' => true,
                 'created_at' => now(), 'updated_at' => now()]
            );
        }

        // 3. Permissions and roles for the Rig.
        $permissions = [
            'manage rig checkpoint', 'manage rig reception', 'view rig data',
            'view all areas', 'serve rig visitors', 'view vip visits',
        ];
        foreach ($permissions as $name) {
            Permission::findOrCreate($name, 'web');
        }

        Role::findOrCreate('Rig Officer', 'web')->givePermissionTo(['manage rig checkpoint', 'use kiosk mode']);
        Role::findOrCreate('Rig Receptionist', 'web')->givePermissionTo(['manage rig reception', 'view rig data']);

        // The Supervisor sees every area.
        if ($supervisor = Role::where('name', 'Supervisor')->first()) {
            $supervisor->givePermissionTo('view all areas');
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        Role::whereIn('name', ['Rig Officer', 'Rig Receptionist'])->delete();
        Permission::whereIn('name', [
            'manage rig checkpoint', 'manage rig reception', 'view rig data',
            'view all areas', 'serve rig visitors', 'view vip visits',
        ])->delete();

        Schema::table('visit_purposes', fn (Blueprint $table) => $table->dropColumn(['at_main', 'at_rig']));
        Schema::table('visitor_visits', fn (Blueprint $table) => $table->dropColumn('site'));

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};