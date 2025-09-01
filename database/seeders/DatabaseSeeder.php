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

        $guard = config('auth.defaults.guard', 'web');
        foreach (['Staff', 'Team Leader', 'Manager', 'Director', 'Asmen', 'SU'] as $r) {
            Role::findOrCreate($r, $guard);
        }

        // Daftar departemen
        $departmentData = [
            "ACCOUNTING",
            "AUDIT",
            "FINANCE",
            "GENERAL AFFAIR",
            "HRD",
            "HRGA",
            "HUMAS & LEGAL",
            "IT",
            "LUBRICANT",
            "MARKETING",
            "PLANTATION",
            "PROCUREMENT",
            "RETAIL",
            "SHIPPING",
            "SPBD",
            "TOP",
            "TRANSPORT",
        ];

        // Buat semua department, simpan referensi
        $departments = [];
        foreach ($departmentData as $name) {
            $departments[$name] = Department::firstOrCreate(['name' => $name]);
        }

        // Shorthand variabel agar kompatibel dengan kode di bawah
        $departmentAcc   = $departments['ACCOUNTING'];
        $departmentAudit = $departments['AUDIT'];
        $departmentFin   = $departments['FINANCE'];
        $departmentGA    = $departments['GENERAL AFFAIR'];
        $departmentHR    = $departments['HRD'];
        $departmentHRGA  = $departments['HRGA'];
        $departmentHL    = $departments['HUMAS & LEGAL'];
        $departmentIT    = $departments['IT'];
        $departmentLub   = $departments['LUBRICANT'];
        $departmentMkt   = $departments['MARKETING'];
        $departmentPlant = $departments['PLANTATION'];
        $departmentProc  = $departments['PROCUREMENT'];
        $departmentRetail= $departments['RETAIL'];
        $departmentShip  = $departments['SHIPPING'];
        $departmentSpbd  = $departments['SPBD'];
        $departmentTop   = $departments['TOP'];
        $departmentTrans = $departments['TRANSPORT'];

        // Helper membuat/attach user + role (idempotent by email)
        $makeUser = function (Department $dept, string $name, string $email, string|array $roles) {
            $user = User::firstOrCreate(
                ['email' => strtolower($email)],
                [
                    'id'       => (string) Str::uuid(),
                    'name'     => $name,
                    'password' => Hash::make('password'),
                ]
            );

            $user->departments()->syncWithoutDetaching([$dept->id]);

            foreach ((array) $roles as $role) {
                if (! $user->hasRole($role)) {
                    $user->assignRole($role);
                }
            }
            return $user;
        };

        // Admin utama — TIDAK DIHAPUS, tidak dioverwrite
        $admin = User::firstOrCreate(
            ['email' => 'samuelzakaria28@gmail.com'],
            [
                'id'       => (string) Str::uuid(),
                'name'     => 'Samuel Zakaria',
                'password' => Hash::make('password'),
            ]
        );
        $admin->departments()->syncWithoutDetaching([
            $departmentIT->id, $departmentHR->id, $departmentAcc->id, $departmentProc->id,
        ]);
        foreach (['Asmen', 'Director', 'SU'] as $r) {
            if (! $admin->hasRole($r)) $admin->assignRole($r);
        }

        // IT
        $itTL = $makeUser($departmentIT, 'Team Leader IT',   'it@gmail.com',           'Team Leader');
        $itTL2= $makeUser($departmentIT, 'Team Leader IT 2', 'it2@gmail.com',          'Team Leader');
        $itMG = $makeUser($departmentIT, 'Manager IT',       'managerIt@gmail.com',    'Manager');
        $itDirector = $makeUser($departmentIT, 'Direksi IT', 'direksiit@gmail.com',    'Director'); // <- bug diperbaiki

        // HRD
        $hrSt  = $makeUser($departmentHR, 'Staff HR',   'hr@gmail.com',    'Staff');
        $hrSt2 = $makeUser($departmentHR, 'Staff HR 2', 'hr2@gmail.com',   'Staff');
        $hrTL  = $makeUser($departmentHR, 'Team Leader HR', 'teamleaderHr@gmail.com', 'Team Leader');
        $hrMG  = $makeUser($departmentHR, 'Manager HR',     'managerHr@gmail.com',    'Manager');
        $hrDirector = $makeUser($departmentHR, 'Direksi HR', 'direksiHr@gmail.com',   'Director');

        // ACCOUNTING
        $accSt  = $makeUser($departmentAcc, 'Staff Acc',   'acc@gmail.com',    'Staff');
        $accSt2 = $makeUser($departmentAcc, 'Staff Acc 2', 'acc2@gmail.com',   'Staff');
        $accTL  = $makeUser($departmentAcc, 'Team Leader Acc', 'teamleaderacc@gmail.com', 'Team Leader');
        $accMG  = $makeUser($departmentAcc, 'Manager Acc',     'manageracc@gmail.com',    'Manager');
        $accDirector = $makeUser($departmentAcc, 'Direksi Acc', 'direksiacc@gmail.com',   'Director');

        // PROCUREMENT
        $procSt  = $makeUser($departmentProc, 'Staff Proc',   'proc@gmail.com',    'Staff');
        $procSt2 = $makeUser($departmentProc, 'Staff Proc 2', 'proc2@gmail.com',   'Staff');
        $procTL  = $makeUser($departmentProc, 'Team Leader Proc', 'teamleaderproc@gmail.com', 'Team Leader');
        $procMG  = $makeUser($departmentProc, 'Manager Proc',     'managerproc@gmail.com',    'Manager');
        $procDirector = $makeUser($departmentProc, 'Direksi Proc', 'direksiproc@gmail.com',   'Director');

        // Akun tes — TIDAK DIHAPUS, tidak dioverwrite
        $tesHRSu = User::firstOrCreate(
            ['email' => 'samuelzakaria3103@outlook.com'],
            [
                'id'       => (string) Str::uuid(),
                'name'     => 'Tes Manager HR 4',
                'password' => Hash::make('password'),
            ]
        );
        $tesHRSu->departments()->syncWithoutDetaching([$departmentHR->id]);
        foreach (['Manager', 'Director'] as $r) {
            if (! $tesHRSu->hasRole($r)) $tesHRSu->assignRole($r);
        }

        // Buat akun dasar untuk SEMUA departemen lain yang belum dibuat di atas (2 Staff + 1 TL + 1 Manager + 1 Director)
        $alreadySeeded = [
            'IT','HRD','ACCOUNTING','PROCUREMENT',
        ];
        foreach ($departments as $name => $dept) {
            if (in_array($name, $alreadySeeded, true)) continue;

            $slug = Str::slug($name, '-'); // contoh: "HUMAS & LEGAL" => "humas-legal"
            $makeUser($dept, "Staff {$name}",      "staff1-{$slug}@gmail.com", 'Staff');
            $makeUser($dept, "Staff {$name} 2",    "staff2-{$slug}@gmail.com", 'Staff');
            $makeUser($dept, "Team Leader {$name}","teamleader-{$slug}@gmail.com", 'Team Leader');
            $makeUser($dept, "Manager {$name}",    "manager-{$slug}@gmail.com",    'Manager');
            $makeUser($dept, "Direksi {$name}",    "director-{$slug}@gmail.com",   'Director');
        }

        // Helper bikin sampel RecruitmentRequest + Phase + Approval
        $makeSamples = function (Department $dept, string $requesterId, int $count = 10) {
            for ($i = 0; $i < $count; $i++) {
                $approval = Approval::factory()->create();

                $request = RecruitmentRequest::factory()->create([
                    'department_id' => $dept->id,
                    'requested_by'  => $requesterId,
                    'approval_id'   => $approval->id,
                ]);

                $approval->update(['request_id' => $request->id]);

                $phase = RecruitmentPhase::factory()->create([
                    'request_id' => $request->id,
                ]);

                $request->update(['phase_id' => $phase->id]);
            }
        };

        // Data dummy untuk beberapa departemen
//        $makeSamples($departmentIT,  $admin->id, 10);
        $makeSamples($departmentHR,  $admin->id, 10);
        $makeSamples($departmentAcc, $admin->id, 10);
        $makeSamples($departmentProc,$admin->id, 10);


        $mrMustika = User::create([
            'id'       => (string) Str::uuid(),
//            'name'     => 'Mr. Mustika Lautan',
            'name'     => 'Testing Dirut',
            'email'    => 'samuelzakaria0000@gmail.com',
            'password' => Hash::make('password'),
        ]);
        $mrMustika->departments()->syncWithoutDetaching([$departmentIT->id]);
        $mrMustika->departments()->syncWithoutDetaching([$departmentTop->id]);
        $mrMustika->assignRole('Dirut');

//        $msMeriawati = User::create([
//            'id'       => (string) Str::uuid(),
//            'name'     => 'Ms. Meriawaty',
//            'email'    => 'meri@indraangkola.com',
//            'password' => Hash::make('password'),
//        ]);
//        $msMeriawati->departments()->syncWithoutDetaching([$departmentHR->id]);
//        $msMeriawati->assignRole('Director');

//        for ($i = 0; $i < 10; $i++) {
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
//
//        for ($i = 0; $i < 10; $i++) {
//            $approval = Approval::factory()->create();
//
//            $request = RecruitmentRequest::factory()->create([
//                'department_id' => $departmentHR->id,
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
//
//        for ($i = 0; $i < 10; $i++) {
//            $approval = Approval::factory()->create();
//
//            $request = RecruitmentRequest::factory()->create([
//                'department_id' => $departmentAcc->id,
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
//
//        for ($i = 0; $i < 10; $i++) {
//            $approval = Approval::factory()->create();
//
//            $request = RecruitmentRequest::factory()->create([
//                'department_id' => $departmentProc->id,
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
