<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class VisitorRolesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
         // firstOrCreate (not create) so this seeder can be run more than once safely --
    // re-running create() a second time would crash on the unique "name" constraint
    // Spatie's permissions table enforces. firstOrCreate finds-or-makes, never duplicates.
    $gateVisitors = Permission::firstOrCreate(['name' => 'manage gate visitors']);
    $visitorDevices = Permission::firstOrCreate(['name' => 'manage visitor devices']);
    $visitorReception = Permission::firstOrCreate(['name' => 'manage visitor reception']);

    $gateOfficer = Role::firstOrCreate(['name' => 'Gate Officer']);
    $entryOfficer = Role::firstOrCreate(['name' => 'Entry Officer']);
    $receptionist = Role::firstOrCreate(['name' => 'Receptionist']);

    // Each role gets exactly the one permission matching its checkpoint --
    // fully separate responsibilities, per the design decision.
    $gateOfficer->givePermissionTo($gateVisitors);
    $entryOfficer->givePermissionTo($visitorDevices);
    $receptionist->givePermissionTo($visitorReception);
    }
}
