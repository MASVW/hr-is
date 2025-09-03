<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Department;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Seeder fundamental (tetap sama urutannya)
        $this->call([
            PositionSeeder::class,
            DepartmentSeeder::class,
            RoleSeeder::class,
            LocationSeeder::class,
        ]);

        /**
         * Ambil/konfirmasi semua department yang tersedia.
         * (DepartmentSeeder sudah membuatnya; di sini hanya memastikan map-nya siap.)
         */
        $departmentNames = [
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

        $departments = [];
        foreach ($departmentNames as $name) {
            $departments[$name] = Department::firstOrCreate(['name' => $name]);
        }

        /**
         * ====== PEMETAAN ======
         * 1) Jabatan → Role (harus cocok dengan RoleSeeder yang "mutlak")
         * 2) Kata kunci jabatan → Department (fuzzy/approximate)
         */

        $guessRole = function (string $jabatan): string {
            $t = mb_strtolower($jabatan);

            // Urutan penting: yang paling spesifik dulu
            if (str_contains($t, 'direktur utama') || str_contains($t, 'dirut')) {
                return 'Dirut';
            }
            if (str_contains($t, 'direktur')) {
                return 'Director';
            }
            if (str_contains($t, 'manager')) {
                return 'Manager';
            }
            if (str_contains($t, 'asmen')) {
                return 'Asmen';
            }

            // Default aman
            return 'Staff';
        };

        // Synonym/keyword → Department name
        // Tambah/ubah di sini jika kamu ingin penyetaraan baru.
        $deptHints = [
            'accounting' => 'ACCOUNTING',
            'audit' => 'AUDIT',
            'finance' => 'FINANCE',
            'administrasi' => 'GENERAL AFFAIR',
            'umum' => 'GENERAL AFFAIR',
            'ga ' => 'GENERAL AFFAIR',
            ' hse' => 'HRGA',             // "HSE" disetarakan ke HRGA
            'hrga' => 'HRGA',
            'hrd' => 'HRD',
            'humas' => 'HUMAS & LEGAL',
            'legal' => 'HUMAS & LEGAL',
            'it' => 'IT',
            'lubricant' => 'LUBRICANT',
            'marketing' => 'MARKETING',
            'komersial' => 'MARKETING',        // "Direktur Komersial" → MARKETING
            'plantation' => 'PLANTATION',
            'procurement' => 'PROCUREMENT',
            'retail' => 'RETAIL',
            'shipping' => 'SHIPPING',
            'spbu' => 'SPBD',             // Retail & SPBU → RETAIL + SPBD
            'spbd' => 'SPBD',
            'top' => 'TOP',
            'transport' => 'TRANSPORT',
            'vhs' => 'TRANSPORT',        // VHS disetarakan terdekat ke TRANSPORT
        ];

        $guessDepartments = function (string $jabatan) use ($deptHints, $departments): array {
            $t = ' ' . mb_strtolower($jabatan) . ' '; // padding spasi untuk pencarian yang aman
            $found = [];

            // Multi match: bisa lebih dari satu dept (contoh: "Retail & SPBU")
            foreach ($deptHints as $needle => $deptName) {
                if (str_contains($t, $needle)) {
                    if (isset($departments[$deptName])) {
                        $found[$deptName] = $departments[$deptName]->id;
                    }
                }
            }

            // Jika tak ada yang cocok, fallback ke TOP (jika ada)
            if (empty($found) && isset($departments['TOP'])) {
                $found['TOP'] = $departments['TOP']->id;
            }

            return array_values($found); // kembalikan array of IDs
        };

        /**
         * ====== DATA AKUN (SESUIAI PERMINTAAN) ======
         * name, jabatan, email, password (plain → akan di-hash).
         */
        $accounts = [
            ['name' => 'Mustika', 'jabatan' => 'Direktur Utama', 'email' => 'mustika@indraangkola.com', 'password' => 'Mustika123'],
            ['name' => 'Meriawati', 'jabatan' => 'Direktur Administrasi & Umum', 'email' => 'meri@indraangkola.com', 'password' => 'Meri123'],
            ['name' => 'Hartono', 'jabatan' => 'Direktur Komersial', 'email' => 'hartono@indraangkola.com', 'password' => 'Hartono123'],
            ['name' => 'Handry Yunus', 'jabatan' => 'Direktur Accounting', 'email' => 'handry@indraangkola.com', 'password' => 'Handry123'],
            ['name' => 'Hamdani', 'jabatan' => 'Manager Lubricant', 'email' => 'hamdani@indraangkola.com', 'password' => 'Hamdani123'],
            ['name' => 'Yonathan', 'jabatan' => 'Manager Marketing wil. I', 'email' => 'yonathan@indraangkola.com', 'password' => 'Yonathan123'],
            ['name' => 'Hadi', 'jabatan' => 'Manager Marketing wil. I', 'email' => 'hadi@indraangkola.com', 'password' => 'Hadi123'],
            ['name' => 'Ekadian', 'jabatan' => 'Manager Marketing wil. I', 'email' => 'ekadian@indraangkola.com', 'password' => 'Eka123'],
            ['name' => 'Tegar', 'jabatan' => 'Manager Shipping', 'email' => 'tegar@indraangkola.com', 'password' => 'Tegar123'],
            ['name' => 'Wisnu', 'jabatan' => 'Manager VHS', 'email' => 'wisnu@indraangkola.com', 'password' => 'Wisnu123'],
            ['name' => 'Kuncoro', 'jabatan' => 'Manager  Audit', 'email' => 'wahyu@indraangkola.com', 'password' => 'Kuncoro123'],
            ['name' => 'Erwin', 'jabatan' => 'Manager Retail & SPBU', 'email' => 'erwin@indraangkola.com', 'password' => 'Erwin123'],
            ['name' => 'Hara', 'jabatan' => 'Asmen HSE', 'email' => 'hara@indraangkola.com', 'password' => 'Hara123'],
            ['name' => 'Wildya', 'jabatan' => 'Asmen Finance', 'email' => 'wildya@indraangkola.com', 'password' => 'Wildya123'],
            ['name' => 'Widdy', 'jabatan' => 'Asmen HRD', 'email' => 'widdy@indraangkola.com', 'password' => 'Widdy123'],
            ['name' => 'Erlis', 'jabatan' => 'Asmen Admin Marketing', 'email' => 'erlis@indraangkola.com', 'password' => 'Erlis123'],
            ['name' => 'Olib', 'jabatan' => 'Asmen IT', 'email' => 'it@indraangkola.com', 'password' => 'Luciefer210'],
        ];

        /**
         * ====== PROSES SEED USER ======
         * - updateOrCreate by email (idempotent).
         * - assignRole sesuai guessRole (tanpa menghapus role yang sudah ada).
         * - attach ke 1..n department berdasarkan guessDepartments.
         */
        foreach ($accounts as $row) {
            $email = mb_strtolower(trim($row['email']));
            $name = trim($row['name']);
            $jabatan = trim($row['jabatan']);
            $pwd = (string)$row['password'];

            $user = User::updateOrCreate(
                ['email' => $email],
                [
                    // UUID baru hanya dipakai saat create
                    'id' => (string)Str::uuid(),
                    'name' => $name,
                    'password' => Hash::make($pwd),
                ]
            );

            // Role
            $roleName = $guessRole($jabatan);
            if (!$user->hasRole($roleName)) {
                $user->assignRole($roleName);
            }

            // Departments
            $deptIds = $guessDepartments($jabatan);
            if (!empty($deptIds)) {
                $user->departments()->syncWithoutDetaching($deptIds);
            }
        }

        // ====== Tambah akun SU: Samuel Zakaria ======
        $suEmail = 'samuelzakaria28@gmail.com';
        $su = \App\Models\User::firstOrNew(['email' => $suEmail]);

        if (!$su->exists) {
            $su->id = (string)\Illuminate\Support\Str::uuid();
            $su->name = 'Samuel Zakaria';
            $su->password = \Illuminate\Support\Facades\Hash::make('password');
            $su->save();
        }

        $attachDeptIds = [];
        if (isset($departments['IT'])) {
            $attachDeptIds[] = $departments['IT']->id;
        } elseif (isset($departments['TOP'])) {
            $attachDeptIds[] = $departments['TOP']->id;
        }

        if (!empty($attachDeptIds)) {
            $su->departments()->syncWithoutDetaching($attachDeptIds);
        }

        foreach (['SU', 'Super-Admin'] as $r) {
            if (!$su->hasRole($r)) {
                $su->assignRole($r);
            }
        }

    }
}
