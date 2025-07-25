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
        $department = Department::all()->pluck('name')->toArray();

        Permission::create(['name' => 'create']);
        Permission::create(['name' => 'edit']);
        Permission::create(['name' => 'read']);
        Permission::create(['name' => 'delete']);

        Permission::create(['name' => 'create form']);
        Permission::create(['name' => 'edit form']);
        Permission::create(['name' => 'read form']);
        Permission::create(['name' => 'delete form']);

        for ($i = 0; $i < count($department); $i++){
            $role = Role::create([
                'name' => $department[$i]
            ]);
            $role->givePermissionTo('edit');
            $role->givePermissionTo('read');
            $role->givePermissionTo('delete');
            $role->givePermissionTo('create');

            if ($role == "Human Resource"){
                $role->givePermissionTo('create form');
                $role->givePermissionTo('edit form');
                $role->givePermissionTo('read form');
                $role->givePermissionTo('delete form');
            }
        };
        Role::create(['name' => 'Super-Admin']);
    }
}
