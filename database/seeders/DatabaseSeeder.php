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
        // Seeder fundamental
        $this->call([
            PositionSeeder::class,
            DepartmentSeeder::class,
            RoleSeeder::class,
            LocationSeeder::class,
        ]);

        /**
         * Ambil semua department yang sudah dibuat oleh DepartmentSeeder
         * dan buat index case-insensitive (trim + uppercase).
         */
        $deptIndex = [];
        foreach (Department::all() as $d) {
            $deptIndex[mb_strtoupper(trim($d->name))] = $d;
        }

        $getDeptId = function (string $canon) use ($deptIndex): ?string {
            $key = mb_strtoupper(trim($canon));
            if (isset($deptIndex[$key])) return (string) $deptIndex[$key]->id;
            $alt = $key . ' ';
            return isset($deptIndex[$alt]) ? (string) $deptIndex[$alt]->id : null;
        };

        /** ===== Helpers ===== */
        $guessRole = function (string $jabatan): string {
            $t = mb_strtolower($jabatan);
            if (str_contains($t, 'direktur utama') || str_contains($t, 'dirut')) return 'Dirut';
            if (str_contains($t, 'direktur')) return 'Director';
            if (str_contains($t, 'manager')) return 'Manager';
            if (str_contains($t, 'asmen')) return 'Asmen';
            return 'Staff';
        };

        // token → nama kanonik sesuai DepartmentSeeder BARU
        $tokenToCanon = [
            'accounting'             => 'ACCOUNTING',
            'audit'                  => 'AUDIT',
            'finance'                => 'FINANCE',
            'general affair'         => 'GENERAL AFFAIR',
            'ga'                     => 'GENERAL AFFAIR',

            'hrd'                    => 'HRD',
            'hrga'                   => 'HRGA',
            'hse'                    => 'HSE',                // HSE ada sebagai dept sendiri
            'legal'                  => 'HUMAS & LEGAL',
            'hukum'                  => 'HUMAS & LEGAL',
            'humas'                  => 'HUMAS & LEGAL',

            'it'                     => 'IT',
            'bbm'                    => 'BBM',
            // tidak ada "MARKETING LUBRICANT" → pakai LUBRICANT saja
            'lubricant'              => 'LUBRICANT',

            'marketing wil. 1'       => 'MARKETING WILAYAH 1',
            'marketing wil. 2'       => 'MARKETING WILAYAH 2',
            'marketing wil. 3'       => 'MARKETING WILAYAH 3',
            'marketing'              => 'MARKETING ',         // di DB: "MARKETING " (ada spasi)

            'plantation'             => 'PLANTATION',
            'kebun'                  => 'PLANTATION',

            'procurement'            => 'PROCUREMENT',
            'retail & spbu'          => 'RETAIL & SPBU',
            'retail'                 => 'RETAIL & SPBU',      // didekatkan
            'spbu'                   => 'RETAIL & SPBU',
            'spbd'                   => 'SPBD',

            'shipping'               => 'SHIPPING',
            'shippping'              => 'SHIPPING',           // typo umum

            'transport'              => 'TRANSPORT',
            'vhs'                    => 'VHS',

            'admin operasional'      => 'ADMIN OPERASIONAL',
            'operasional'            => 'OPERASIONAL',
            'admin marketing'        => 'ADMIN MARKETING',

            'komersial'              => 'KOMERSIAL',
            'top'                    => 'TOP',
        ];

        // parse CSV departemen: "A, B & C" → [id, id, id]
        $parseDepartments = function (?string $raw) use ($tokenToCanon, $getDeptId): array {
            $raw = trim((string)$raw);
            if ($raw === '') return [];
            $clean = str_ireplace([' & ', ' dan '], [',', ','], $raw);
            $tokens = array_filter(array_map('trim', explode(',', $clean)));
            $ids = [];
            foreach ($tokens as $tok) {
                $t = mb_strtolower($tok);
                if (preg_match('/^marketing\s+wil\.\s*([123])$/i', $t, $m)) {
                    $canon = 'MARKETING WILAYAH ' . $m[1];
                } else {
                    // "marketing lubricant" diarahkan ke LUBRICANT (karena nggak ada dept MARKETING LUBRICANT)
                    if ($t === 'marketing lubricant') {
                        $canon = 'LUBRICANT';
                    } else {
                        $canon = $tokenToCanon[$t] ?? null;
                    }
                }
                if ($canon) {
                    if ($id = $getDeptId($canon)) $ids[$id] = $id;
                } else {
                    // fallback: coba exact name yang sudah rapi
                    if ($id = $getDeptId($tok)) $ids[$id] = $id;
                }
            }
            return array_values($ids);
        };

        // kalau kolom Department kosong → tebak dari jabatan
        $guessDepartmentsFromTitle = function (string $jabatan) use ($getDeptId): array {
            $t = mb_strtolower($jabatan);
            $hits = [];

            $try = function (string $needle, string $canon) use (&$hits, $t, $getDeptId) {
                if (str_contains($t, $needle)) {
                    if ($id = $getDeptId($canon)) $hits[$id] = $id;
                }
            };

            if (preg_match('/marketing\s+wil\.\s*([123])/i', $t, $m)) {
                $canon = 'MARKETING WILAYAH ' . $m[1];
                if ($id = $getDeptId($canon)) $hits[$id] = $id;
            }

            $try('bbm',                 'BBM');
            $try('lubricant',           'LUBRICANT');
            $try('audit',               'AUDIT');
            $try('accounting',          'ACCOUNTING');
            $try('finance',             'FINANCE');
            $try('hrd',                 'HRD');
            $try('hse',                 'HSE');
            $try('hrga',                'HRGA');
            $try('legal',               'HUMAS & LEGAL');
            $try('humas',               'HUMAS & LEGAL');
            $try('it',                  'IT');
            $try('shipping',            'SHIPPING');
            $try('shippping',           'SHIPPING');
            $try('vhs',                 'VHS');
            $try('transport',           'TRANSPORT');
            $try('retail',              'RETAIL & SPBU');
            $try('spbu',                'RETAIL & SPBU');
            $try('procurement',         'PROCUREMENT');
            $try('plantation',          'PLANTATION');
            $try('kebun',               'PLANTATION');
            $try('admin operasional',   'ADMIN OPERASIONAL');
            $try('operasional',         'OPERASIONAL');
            $try('admin marketing',     'ADMIN MARKETING');
            $try('komersial',           'KOMERSIAL');

            if (empty($hits)) {
                // generik "marketing" → "MARKETING "
                $try('marketing', 'MARKETING ');
            }

            return array_values($hits);
        };

        /** ===== Data akun ===== */
        $accounts = [
            ['name' => 'Mustika Lautan', 'jabatan' => 'Direktur Utama',                 'departments' => 'Audit, IT, Legal, SPBD',                                   'email' => 'mustika@indraangkola.com',  'password' => 'Mustika123'],
            ['name' => 'Meriawati',       'jabatan' => 'Direktur Administrasi & Umum',  'departments' => 'HRGA, HRD, Admin Operasional, Admin Marketing, Procurement',     'email' => 'meri@indraangkola.com',      'password' => 'Meri123'],
            ['name' => 'Hartono',         'jabatan' => 'Direktur Komersial',  'departments' => 'BBM, Lubricant',                                          'email' => 'hartono@indraangkola.com',   'password' => 'Hartono123'],
            ['name' => 'Handry Yunus',    'jabatan' => 'Direktur Accounting', 'departments' => 'Accounting',                                              'email' => 'handry@indraangkola.com',    'password' => 'Handry123'],
            ['name' => 'Wuryanto',        'jabatan' => 'Direktur Operasional','departments' => 'Transport, Shipping, VHS, HSE, Plantation, Retail & SPBU','email' => 'wuryanto@indraangkola.com',  'password' => 'Wuryanto123'],
            ['name' => 'Hamdani',   'jabatan' => 'Manager Lubricant',         'departments' => '', 'email' => 'hamdani@indraangkola.com',   'password' => 'Hamdani123'],
            ['name' => 'Yonathan',  'jabatan' => 'Manager Marketing wil. 1',  'departments' => '', 'email' => 'yonathan@indraangkola.com',  'password' => 'Yonathan123'],
            ['name' => 'Hadi',      'jabatan' => 'Manager Marketing wil. 2',  'departments' => '', 'email' => 'hadi@indraangkola.com',      'password' => 'Hadi123'],
            ['name' => 'Ekadian',   'jabatan' => 'Manager Marketing wil. 3',  'departments' => '', 'email' => 'ekadian@indraangkola.com',   'password' => 'Eka123'],
            ['name' => 'Tegar',     'jabatan' => 'Manager Shipping',          'departments' => '', 'email' => 'tegar@indraangkola.com',     'password' => 'Tegar123'],
            ['name' => 'Wisnu',     'jabatan' => 'Manager VHS',               'departments' => '', 'email' => 'wisnu@indraangkola.com',     'password' => 'Wisnu123'],
            ['name' => 'Kuncoro',   'jabatan' => 'Manager Audit',             'departments' => '', 'email' => 'wahyu@indraangkola.com',     'password' => 'Kuncoro123'],
            ['name' => 'Erwin',     'jabatan' => 'Manager Retail & SPBU',     'departments' => '', 'email' => 'erwin@indraangkola.com',     'password' => 'Erwin123'],
            ['name' => 'Hara',      'jabatan' => 'Asmen HSE',                 'departments' => '', 'email' => 'hara@indraangkola.com',      'password' => 'Hara123'],
            ['name' => 'Wildya',    'jabatan' => 'Asmen Finance',             'departments' => '', 'email' => 'wildya@indraangkola.com',    'password' => 'Wildya123'],
            ['name' => 'Widdy',     'jabatan' => 'Asmen HRD',                 'departments' => '', 'email' => 'widdy@indraangkola.com',     'password' => 'Widdy123'],
            ['name' => 'Erlis',     'jabatan' => 'Asmen Admin Marketing',     'departments' => '', 'email' => 'erlis@indraangkola.com',     'password' => 'Erlis123'],
            ['name' => 'Olib',      'jabatan' => 'Asmen IT',                  'departments' => '', 'email' => 'it@indraangkola.com',        'password' => 'Luciefer210'],
            ['name' => 'Enty',      'jabatan' => 'SPV PROCUREMENT',           'departments' => '', 'email' => 'enty@indraangkola.com',        'password' => 'IAGproc210'],
            ['name' => 'Ibnu',      'jabatan' => 'SPV ACCOUNTING',            'departments' => '', 'email' => 'ibnu@indraangkola.com',        'password' => 'IAGaccjkt210'],
            ['name' => 'Akhmad Kurniawan',      'jabatan' => 'SPV TRANSPORT',            'departments' => '', 'email' => 'kurniawan@indraangkola.com',        'password' => 'IAGtrans210'],
            ['name' => 'Lin Idham',      'jabatan' => 'Manager PLANTATION',            'departments' => '', 'email' => 'linidham@indraangkola.com',        'password' => 'Linidham123'],

            //STAFF HRD
            ['name' => 'Sherlin',      'jabatan' => 'STAFF HRD',            'departments' => '', 'email' => 'hrd-recruitment@indraangkola.com',        'password' => 'IAGhuman210'],
            ['name' => 'Teguh',      'jabatan' => 'STAFF HRD',            'departments' => '', 'email' => 'hrd-transport@indraangkola.com',        'password' => 'IAGhuman210'],
            ['name' => 'Mangara',      'jabatan' => 'STAFF HRD',            'departments' => '', 'email' => 'hrd-crewing@indraangkola.com',        'password' => 'IAGhuman210']

        ];

        /** ===== Create/Update users + roles + departments ===== */
        foreach ($accounts as $row) {
            $email    = mb_strtolower(trim($row['email']));
            $name     = trim($row['name']);
            $jabatan  = trim($row['jabatan']);
            $pwdPlain = (string) $row['password'];
            $deptRaw  = $row['departments'] ?? '';

            $user = User::updateOrCreate(
                ['email' => $email],
                [
                    'id'       => (string) Str::uuid(), // hanya saat create
                    'name'     => $name,
                    'password' => Hash::make($pwdPlain),
                ]
            );

            // Role
            $roleName = $guessRole($jabatan);
            if (! $user->hasRole($roleName)) {
                $user->assignRole($roleName);
            }

            // Departments
            $ids = $parseDepartments($deptRaw);
            if (empty($ids)) {
                $ids = $guessDepartmentsFromTitle($jabatan);
            }
            if (!empty($ids)) {
                $user->departments()->syncWithoutDetaching($ids);
            }
        }

        // ===== Akun SU: Samuel Zakaria =====
        $suEmail = 'samuelzakaria28@gmail.com';
        $su = User::firstOrNew(['email' => $suEmail]);
        if (! $su->exists) {
            $su->id       = (string) Str::uuid();
            $su->name     = 'Samuel Zakaria';
            $su->password = Hash::make('password');
            $su->save();
        }
        $attach = [];
        if ($id = $getDeptId('IT'))  $attach[] = $id;
        if ($id = $getDeptId('TOP')) $attach[] = $id;
        if ($attach) $su->departments()->syncWithoutDetaching($attach);
        foreach (['SU', 'Super-Admin'] as $r) {
            if (! $su->hasRole($r)) $su->assignRole($r);
        }
    }
}
