<?php

namespace Database\Seeders;

use App\Models\Approval;
use App\Models\Department;
use App\Models\RecruitmentPhase;
use App\Models\RecruitmentRequest;
use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            DepartmentSeeder::class,
            RoleSeeder::class,
            LocationSeeder::class,
        ]);

        $department = Department::first();

        $admin = User::create([
            'id' => (string) Str::uuid(),
            'name' => 'Human Resource Manager',
            'email' => 'hrmanager@gmail.com',
            'password' => Hash::make('password'),
            'department_id' => $department->id,
        ]);
        $admin->assignRole('HR Manager');

        $admin = User::create([
            'id' => (string) Str::uuid(),
            'name' => 'Human Resource Management HR Staff',
            'email' => 'hr@gmail.com',
            'password' => Hash::make('password'),
            'department_id' => $department->id,
        ]);
        $admin->assignRole('HR Staff');

        $roles = Role::pluck('name')->toArray();
        User::factory(10)->create()->each(function ($user) use ($roles) {
            $user->assignRole(fake()->randomElement($roles));
        });




        for ($i = 0; $i < 100; $i++){
            //DONE
            $approval = Approval::factory()->create();
            //DONE
            $request = RecruitmentRequest::factory()->create([
                'department_id' => $department->id,
                'requested_by' => $admin->id,
                'approval_id' => $approval->id,
            ]);
            //DONE
            $approval->update([
                'request_id' => $request->id,
            ]);
            //progress
            $phase = RecruitmentPhase::factory()->create([
                'request_id' => $request->id,
            ]);

            $request->update([
                'phase_id' => $phase->id,
            ]);
        }
    }
}
