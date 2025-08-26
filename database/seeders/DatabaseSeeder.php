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
        $departmentAcc = Department::firstOrCreate(['name' => 'ACCOUNTING']);
        $departmentProc = Department::firstOrCreate(['name' => 'PROCUREMENT']);

        // Admin
        $admin = User::create([
            'id'       => (string) Str::uuid(),
            'name'     => 'Samuel Zakaria',
            'email'    => 'samuelzakaria28@gmail.com',
            'password' => Hash::make('password'),
        ]);
        // Attach ke IT (pivot)
        $admin->departments()->syncWithoutDetaching([$departmentIT->id]);
        $admin->departments()->syncWithoutDetaching([$departmentHR->id]);
        $admin->departments()->syncWithoutDetaching([$departmentAcc->id]);
        $admin->departments()->syncWithoutDetaching([$departmentProc->id]);
        $admin->assignRole('Asmen');
        $admin->assignRole('Director');
        $admin->assignRole('SU');

        $itTL = User::create([
            'id'       => (string) Str::uuid(),
            'name'     => 'Team Leader IT',
            'email'    => 'it@gmail.com',
            'password' => Hash::make('password'),
        ]);
        $itTL->departments()->syncWithoutDetaching([$departmentIT->id]);
        $itTL->assignRole('Team Leader');

        $itTL2 = User::create([
            'id'       => (string) Str::uuid(),
            'name'     => 'Team Leader IT 2',
            'email'    => 'it2@gmail.com',
            'password' => Hash::make('password'),
        ]);
        $itTL2->departments()->syncWithoutDetaching([$departmentIT->id]);
        $itTL2->assignRole('Team Leader');

        $itMG2 = User::create([
            'id'       => (string) Str::uuid(),
            'name'     => 'Manager IT',
            'email'    => 'managerIt@gmail.com',
            'password' => Hash::make('password'),
        ]);
        $itMG2->departments()->syncWithoutDetaching([$departmentIT->id]);
        $itMG2->assignRole('Manager');

        $ITDirector = User::create([
            'id'       => (string) Str::uuid(),
            'name'     => 'Direksi IT',
            'email'    => 'direksiit@gmail.com',
            'password' => Hash::make('password'),
        ]);
        $itMG2->departments()->syncWithoutDetaching([$departmentIT->id]);
        $itMG2->assignRole('Director');

        $hrSt = User::create([
            'id'       => (string) Str::uuid(),
            'name'     => 'Staff HR',
            'email'    => 'hr@gmail.com',
            'password' => Hash::make('password'),
        ]);
        $hrSt->departments()->syncWithoutDetaching([$departmentHR->id]);
        $hrSt->assignRole('Staff');

        $hrSt2 = User::create([
            'id'       => (string) Str::uuid(),
            'name'     => 'Staff HR 2',
            'email'    => 'hr2@gmail.com',
            'password' => Hash::make('password'),
        ]);
        $hrSt2->departments()->syncWithoutDetaching([$departmentHR->id]);
        $hrSt2->assignRole('Staff');

        $hrTL = User::create([
            'id'       => (string) Str::uuid(),
            'name'     => 'Team Leader HR',
            'email'    => 'teamleaderHr@gmail.com',
            'password' => Hash::make('password'),
        ]);
        $hrTL->departments()->syncWithoutDetaching([$departmentHR->id]);
        $hrTL->assignRole('Team Leader');

        $hrMG = User::create([
            'id'       => (string) Str::uuid(),
            'name'     => 'Manager HR',
            'email'    => 'managerHr@gmail.com',
            'password' => Hash::make('password'),
        ]);
        $hrMG->departments()->syncWithoutDetaching([$departmentHR->id]);
        $hrMG->assignRole('Manager');

        $HRDirector = User::create([
            'id'       => (string) Str::uuid(),
            'name'     => 'Direksi HR',
            'email'    => 'direksiHr@gmail.com',
            'password' => Hash::make('password'),
        ]);
        $HRDirector->departments()->syncWithoutDetaching([$departmentHR->id]);
        $HRDirector->assignRole('Director');

        $AccSt = User::create([
            'id'       => (string) Str::uuid(),
            'name'     => 'Staff Acc',
            'email'    => 'acc@gmail.com',
            'password' => Hash::make('password'),
        ]);
        $AccSt->departments()->syncWithoutDetaching([$departmentAcc->id]);
        $AccSt->assignRole('Staff');

        $AccSt2 = User::create([
            'id'       => (string) Str::uuid(),
            'name'     => 'Staff Acc 2',
            'email'    => 'acc2@gmail.com',
            'password' => Hash::make('password'),
        ]);
        $AccSt2->departments()->syncWithoutDetaching([$departmentAcc->id]);
        $AccSt2->assignRole('Staff');

        $AccTL = User::create([
            'id'       => (string) Str::uuid(),
            'name'     => 'Team Leader Acc',
            'email'    => 'teamleaderacc@gmail.com',
            'password' => Hash::make('password'),
        ]);
        $AccTL->departments()->syncWithoutDetaching([$departmentAcc->id]);
        $AccTL->assignRole('Team Leader');

        $AccMG = User::create([
            'id'       => (string) Str::uuid(),
            'name'     => 'Manager Acc',
            'email'    => 'manageracc@gmail.com',
            'password' => Hash::make('password'),
        ]);
        $AccMG->departments()->syncWithoutDetaching([$departmentAcc->id]);
        $AccMG->assignRole('Manager');

        $AccDirector = User::create([
            'id'       => (string) Str::uuid(),
            'name'     => 'Direksi Acc',
            'email'    => 'direksiacc@gmail.com',
            'password' => Hash::make('password'),
        ]);
        $AccDirector->departments()->syncWithoutDetaching([$departmentAcc->id]);
        $AccDirector->assignRole('Director');

        $ProcSt = User::create([
            'id'       => (string) Str::uuid(),
            'name'     => 'Staff Proc',
            'email'    => 'proc@gmail.com',
            'password' => Hash::make('password'),
        ]);
        $ProcSt->departments()->syncWithoutDetaching([$departmentProc->id]);
        $ProcSt->assignRole('Staff');

        $ProcSt2 = User::create([
            'id'       => (string) Str::uuid(),
            'name'     => 'Staff Proc 2',
            'email'    => 'proc2@gmail.com',
            'password' => Hash::make('password'),
        ]);
        $ProcSt2->departments()->syncWithoutDetaching([$departmentProc->id]);
        $ProcSt2->assignRole('Staff');

        $ProcTL = User::create([
            'id'       => (string) Str::uuid(),
            'name'     => 'Team Leader Proc',
            'email'    => 'teamleaderproc@gmail.com',
            'password' => Hash::make('password'),
        ]);
        $ProcTL->departments()->syncWithoutDetaching([$departmentProc->id]);
        $ProcTL->assignRole('Team Leader');

        $ProcMG = User::create([
            'id'       => (string) Str::uuid(),
            'name'     => 'Manager Proc',
            'email'    => 'managerproc@gmail.com',
            'password' => Hash::make('password'),
        ]);
        $ProcMG->departments()->syncWithoutDetaching([$departmentProc->id]);
        $ProcMG->assignRole('Manager');

        $ProcDirector = User::create([
            'id'       => (string) Str::uuid(),
            'name'     => 'Direksi Proc',
            'email'    => 'direksiproc@gmail.com',
            'password' => Hash::make('password'),
        ]);
        $ProcDirector->departments()->syncWithoutDetaching([$departmentProc->id]);
        $ProcDirector->assignRole('Director');



        $tesHRSu = User::create([
            'id'       => (string) Str::uuid(),
            'name'     => 'Tes Manager HR 4',
            'email'    => 'samuelzakaria3103@outlook.com',
            'password' => Hash::make('password'),
        ]);
        $tesHRSu->departments()->syncWithoutDetaching([$departmentHR->id]);
        $tesHRSu->assignRole('Manager');
        $tesHRSu->assignRole('Director');

//        $mrMustika = User::create([
//            'id'       => (string) Str::uuid(),
//            'name'     => 'Mr. Mustika Lautan',
//            'email'    => 'mustika@indraangkola.com',
//            'password' => Hash::make('password'),
//        ]);
//        $mrMustika->departments()->syncWithoutDetaching([$departmentIT->id]);
//        $mrMustika->assignRole('Director');

//        $msMeriawati = User::create([
//            'id'       => (string) Str::uuid(),
//            'name'     => 'Ms. Meriawaty',
//            'email'    => 'meri@indraangkola.com',
//            'password' => Hash::make('password'),
//        ]);
//        $msMeriawati->departments()->syncWithoutDetaching([$departmentHR->id]);
//        $msMeriawati->assignRole('Director');

        // Contoh data RecruitmentRequest: tetap pakai department_id milik request (ini bukan relasi user)
//        for ($i = 0; $i < 3; $i++) {
//            $approval = Approval::factory()->create();
//
//            $request = RecruitmentRequest::factory()->create([
//                'department_id' => $departmentIT->id,
//                'requested_by'  => $admin->id,
//                'approval_id'   => $approval->id,
//            ]);
//
//            $approval->update(['request_id' => $request->id]);
//
//            $phase = RecruitmentPhase::factory()->create([
//                'request_id' => $request->id,
//            ]);
//
//            $request->update(['phase_id' => $phase->id]);
//        }
    }
}
