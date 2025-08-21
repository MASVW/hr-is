<?php

namespace Database\Seeders;

use App\Models\Location;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class LocationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $names = [
            'Medan 63',
            'Medan 210',
            'Sibolga',
            'Dumai',
            'Padang Sidimpuan (PT BT)',
            'Padang Sidimpuan (PT MAP, PT IAR)',
            'Mandailing Natal (PT MNP)',
            'Sosa, Padang Lawas (PT HSC)',
            'Binanga, Padang Lawas (PT TAS)',
            'Jakarta',
            'Palembang',
            'Palembang (POOL BARU)',
            'Surabaya',
            'Balikpapan',
            'Samarinda (pool baru)',
            'Sampit',
            'Pontianak',
            'Banjarmasin',
            'Pangkalan Bun',
            'Makassar',
            'Rumah Ibu Meriawaty',
            'Rumah Bapak Sartono',
            'Rumah Bapak Hartono',
            'Rumah Bapak Sugeng',
            'MESS JAKARTA',
            'LAMPUNG',
            'KENDARI',
            'SAM',
            'TBM',
            'SAMARINDA',
            'Absen Shiping',
            'Contoh 1 Location Setting Berisi Lebih Dari 1 Titik Absen',
            'Contoh 1 Location Setting Berisi Lebih Dari 1 Titik Absen',
            'All Location',
            'All Location',
            'All Location',
            'All Location',
            'All Location',
            'All Location',
            'All Location',
            'All Location',
            'All Location',
            'All Location',
            'All Location',
            'All Location',
            'All Location',
            'All Location',
            'All Location',
            'All Location',
            'All Location',
            'All Location',
            'All Location',
            'All Location',
            'All Location',
            'All Location',
            'All Location',
            'All Location',
            'All Location',
            'All Location',
            'All Location',
            'All Location',
            'All Location',
            'All Location',
            'All Location',
            'All Location',
            'All Location',
            'All Location',
            'All Location',
            'All Location',
            'All Location',
            'All Location',
            'SPBU Natal Bintang Tapanuli',
            'SPOB AHZA DANIS',
            'SPOB PRIMA PERTAMA',
            'MT BINTANG MAS',
            'SPOB LAUTAN MAS PERKASA ',
            'OB LILI 11',
        ];

        // 2) Daftar ADDRESS persis sesuai urutan yang kamu berikan
        //    (baris kosong akan diabaikan agar pasangan tetap presisi)
        $addresses = array_values(array_filter([
            'Medan 63',
            'Medan 210',
            'Sibolga',
            'Dumai',
            'Padang Sidimpuan (PT BT)',
            'Padang Sidimpuan (PT MAP, PT IAR)',
            'Mandailing Natal (PT MNP)',
            'Sosa, Padang Lawas (PT HSC)',
            'Binanga, Padang Lawas (PT TAS)',
            'Jakarta',
            'Palembang',
            'Palembang (POOL BARU)',
            'Surabaya',
            'Balikpapan',
            'Samarinda (pool baru)',
            'Sampit',
            'Pontianak',
            'Banjarmasin',
            'Pangkalan Bun',
            'Makassar',
            'Rumah Ibu Meriawaty',
            'Rumah Bapak Sartono',
            'Rumah Bapak Hartono',
            'Rumah Bapak Sugeng',
            'MESS JAKARTA',
            'LAMPUNG',
            'KENDARI',
            'SAM',
            'TBM',
            'SAMARINDA',

            'Indra Angkola Medan',
            'Indra Angkola Jakarta',
            'Palembang (POOL BARU)',
            'Dumai',
            'Protank Samarinda',
            'SAM',
            'Pool Baru Pangkalan Bun',
            'Mandailing Natal (PT MNP)',
            'Rumah Bapak Sartono',
            'Medan 63',
            'TBM',
            'Padang Lawas',
            'MESS JAKARTA',
            'Rumah Bapak Sugeng',
            'Padang Sidimpuan (PT BT)',
            'Samarinda (pool baru)',
            'LAMPUNG',
            'Surabaya',
            'Makassar',
            'SAMARINDA',
            'Rumah Bapak Handry',
            'Sibolga',
            'Rumah Bapak Hartono',
            'Office Palembang',
            'Pangkalan Bun',
            'Jakarta',
            'HSC',
            'Medan 210',
            'Rumah Ibu Meriawaty',
            'Balikpapan',
            'Pontianak 02',
            'VHS SAM',
            'Banjarmasin',
            'Sosa, Padang Lawas (PT HSC)',
            'Pool Kendari',
            'Binanga, Padang Lawas (PT TAS)',
            'Pontianak',
            'Padang Sidimpuan (PT MAP, PT IAR)',
            'Sampit',
            'SPBU Natal ',
            'Banjarmasin',
            'Belawan',
            'Balikpapan',
            'Samarinda',
            'Samarinda',
        ], fn ($v) => trim((string)$v) !== ''));
        $count = max(count($names), count($addresses));

        for ($i = 0; $i < $count; $i++) {
            $name = isset($names[$i]) ? trim($names[$i]) : null;
            if (!$name) {
                continue;
            }

            $address = isset($addresses[$i]) ? trim($addresses[$i]) : $name;

            Location::create([
                'id'      => (string) Str::uuid(),
                'name'    => $name,
                'address' => $address,
            ]);
        }
    }
}
