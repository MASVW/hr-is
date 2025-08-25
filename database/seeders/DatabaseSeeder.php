<?php

namespace Database\Seeders;

use App\Models\Approval;
use App\Models\Department;
use App\Models\RecruitmentPhase;
use App\Models\RecruitmentRequest;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            PositionSeeder::class,
            DepartmentSeeder::class,
            RoleSeeder::class,
            LocationSeeder::class,
        ]);

        // Pastikan dua department kunci tersedia
        $departmentIT = Department::firstOrCreate(['name' => 'IT']);
        $departmentHR = Department::firstOrCreate(['name' => 'HUMAN RESOURCE']);

        // Admin
        $admin = User::create([
            'id'       => (string) Str::uuid(),
            'name'     => 'Samuel Zakaria',
            'email'    => 'samuelzakaria28@gmail.com',
            'password' => Hash::make('password'),
        ]);
        // Attach ke IT (pivot)
        $admin->departments()->syncWithoutDetaching([$departmentIT->id]);
        $admin->assignRole('Asmen');
        $admin->assignRole('Director');

        // Pengguna lain
        $tes1 = User::create([
            'id'       => (string) Str::uuid(),
            'name'     => 'Tes Team Leader IT 2',
            'email'    => 'tes1@gmail.com',
            'password' => Hash::make('password'),
        ]);
        $tes1->departments()->syncWithoutDetaching([$departmentIT->id]);
        $tes1->assignRole('Team Leader');

        $tes2 = User::create([
            'id'       => (string) Str::uuid(),
            'name'     => 'Tes Asmen HR',
            'email'    => 'tes2@gmail.com',
            'password' => Hash::make('password'),
        ]);
        $tes2->departments()->syncWithoutDetaching([$departmentHR->id]);
        $tes2->assignRole('Asmen');

        $tes3 = User::create([
            'id'       => (string) Str::uuid(),
            'name'     => 'Tes SPV HR 2',
            'email'    => 'tes3@gmail.com',
            'password' => Hash::make('password'),
        ]);
        $tes3->departments()->syncWithoutDetaching([$departmentHR->id]);
        $tes3->assignRole('SPV');

        $tes4 = User::create([
            'id'       => (string) Str::uuid(),
            'name'     => 'Tes Manager HR 4',
            'email'    => 'samuelzakaria3103@outlook.com',
            'password' => Hash::make('password'),
        ]);
        $tes4->departments()->syncWithoutDetaching([$departmentHR->id]);
        $tes4->assignRole('Manager');

        $admin1 = User::create([
            'id'       => (string) Str::uuid(),
            'name'     => 'HR Staff',
            'email'    => 'hr@gmail.com',
            'password' => Hash::make('password'),
        ]);
        $admin1->departments()->syncWithoutDetaching([$departmentHR->id]);
        $admin1->assignRole('Staff');

        $admin2 = User::create([
            'id'       => (string) Str::uuid(),
            'name'     => 'HR Staff',
            'email'    => 'hr2@gmail.com',
            'password' => Hash::make('password'),
        ]);
        $admin2->departments()->syncWithoutDetaching([$departmentHR->id]);
        $admin2->assignRole('Staff');

        $admin3 = User::create([
            'id'       => (string) Str::uuid(),
            'name'     => 'HR Staff',
            'email'    => 'hr3@gmail.com',
            'password' => Hash::make('password'),
        ]);
        $admin3->departments()->syncWithoutDetaching([$departmentHR->id]);
        $admin3->assignRole('Staff');

        $mrMustika = User::create([
            'id'       => (string) Str::uuid(),
            'name'     => 'Mr. Mustika Lautan',
            'email'    => 'mustika@indraangkola.com',
            'password' => Hash::make('password'),
        ]);
        $mrMustika->departments()->syncWithoutDetaching([$departmentIT->id]);
        $mrMustika->assignRole('Director');

        $msMeriawati = User::create([
            'id'       => (string) Str::uuid(),
            'name'     => 'Ms. Meriawaty',
            'email'    => 'meri@indraangkola.com',
            'password' => Hash::make('password'),
        ]);
        $msMeriawati->departments()->syncWithoutDetaching([$departmentHR->id]);
        $msMeriawati->assignRole('Director');

        // User factory tambahan + role acak + attach 1–3 department acak (fallback jika factory belum meng-attach)
        $roles = Role::pluck('name')->toArray();
        User::factory(2)->create()->each(function (User $user) use ($roles) {
            $user->assignRole(fake()->randomElement($roles));

            $take = fake()->numberBetween(1, 3);
            $deptIds = Department::inRandomOrder()->limit($take)->pluck('id');
            if ($deptIds->isNotEmpty()) {
                $user->departments()->syncWithoutDetaching($deptIds->all());
            }
        });

        // Contoh data RecruitmentRequest: tetap pakai department_id milik request (ini bukan relasi user)
        for ($i = 0; $i < 3; $i++) {
            $approval = Approval::factory()->create();

            $request = RecruitmentRequest::factory()->create([
                'department_id' => $departmentIT->id,
                'requested_by'  => $admin->id,
                'approval_id'   => $approval->id,
            ]);

            $approval->update(['request_id' => $request->id]);

            $phase = RecruitmentPhase::factory()->create([
                'request_id' => $request->id,
            ]);

            $request->update(['phase_id' => $phase->id]);
        }
    }
}
