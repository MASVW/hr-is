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

        $department = Department::where('name', 'IT')->first();
        $departmentHR = Department::where('name', 'HUMAN RESOURCE')->first();
        $admin = User::create([
            'id' => (string) Str::uuid(),
            'name' => 'Samuel Zakaria',
            'email' => 'samuelzakaria28@gmail.com',
            'password' => Hash::make('password'),
            'department_id' => $department->id,
        ]);
        $admin->assignRole('Asmen');
//        $admin->assignRole('Manager');
//        $admin->assignRole('Director');

        $tes1 = User::create([
            'id' => (string) Str::uuid(),
            'name' => 'Tes Team Leader IT 2',
            'email' => 'tes1@gmail.com',
            'password' => Hash::make('password'),
            'department_id' => $department->id,
        ]);

        $tes1->assignRole('Team Leader');

        $tes2 = User::create([
            'id' => (string) Str::uuid(),
            'name' => 'Tes Asmen HR',
            'email' => 'tes2@gmail.com',
            'password' => Hash::make('password'),
            'department_id' => $departmentHR->id,
        ]);
        $tes2->assignRole('Asmen');

        $tes3 = User::create([
            'id' => (string) Str::uuid(),
            'name' => 'Tes SPV HR 2',
            'email' => 'tes3@gmail.com',
            'password' => Hash::make('password'),
            'department_id' => $departmentHR->id,
        ]);
        $tes3->assignRole('SPV');

        $tes4 = User::create([
            'id' => (string) Str::uuid(),
            'name' => 'Tes Manager HR 4',
            'email' => 'samuelzakaria3103@outlook.com',
            'password' => Hash::make('password'),
            'department_id' => $departmentHR->id,
        ]);
        $tes4->assignRole('Manager');

        $admin = User::create([
            'id' => (string) Str::uuid(),
            'name' => 'HR Staff',
            'email' => 'hr@gmail.com',
            'password' => Hash::make('password'),
            'department_id' => $departmentHR->id,
        ]);
        $admin->assignRole('Staff');

        $roles = Role::pluck('name')->toArray();
        User::factory(2)->create()->each(function ($user) use ($roles) {
            $user->assignRole(fake()->randomElement($roles));
        });

        for ($i = 0; $i < 3; $i++){
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
