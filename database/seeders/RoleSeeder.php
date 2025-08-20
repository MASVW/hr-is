<?php

namespace Database\Seeders;

use App\Models\Department;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use App\Models\Role;
use App\Models\Permission;
use Illuminate\Database\Seeder;
use Spatie\Permission\PermissionRegistrar;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        Permission::create(['name' => 'create']);
        Permission::create(['name' => 'edit']);
        Permission::create(['name' => 'read']);
        Permission::create(['name' => 'delete']);

        Permission::create(['name' => 'create form']);
        Permission::create(['name' => 'edit form']);
        Permission::create(['name' => 'read form']);
        Permission::create(['name' => 'delete form']);

        Permission::create(['name' => 'su']);


        $r1 = Role::create(['name' => 'Team Leader']);
        $r2 = Role::create(['name' => 'Staff']);
        $r3 = Role::create(['name' => 'Asmen']);
        $r4 = Role::create(['name' => 'Manager']);
        $r5 = Role::create(['name' => 'Director']);
        $r6 = Role::create(['name' => 'SPV']);


        $r1->givePermissionTo('read');
        $r1->givePermissionTo('create');
        $r1->givePermissionTo('edit');
        $r1->givePermissionTo('delete');

        $r2->givePermissionTo('read');
        $r2->givePermissionTo('edit');

        $r3->givePermissionTo('read');
        $r3->givePermissionTo('read');
        $r3->givePermissionTo('create');
        $r3->givePermissionTo('create');

        $r4->givePermissionTo('edit');
        $r4->givePermissionTo('edit');
        $r4->givePermissionTo('delete');
        $r4->givePermissionTo('delete');

        $r5->givePermissionTo('read');
        $r5->givePermissionTo('edit');

        $r6->givePermissionTo('read');

        $superAdmin = Role::create(['name' => 'Super-Admin']);
        $superAdmin->givePermissionTo('su');
    }
}
