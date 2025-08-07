<?php

namespace Database\Seeders;

use App\Models\Location;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class LocationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $locations = [
            [
                'name' => 'Kantor Medan',
                'address' => 'Jl. Cemara No. 210 Kec. Pulo Bryan Darat Medan Timur',
            ],
            [
                'name' => 'Kantor Jakarta',
                'address' => 'Komplek Puri Mutiara No. D-123 Jalan. Danau Sunter Barat Jakarta Utara, 14350',
            ],
            [
                'name' => 'Pool Medan',
                'address' => 'Jl. Cemara No. 63 Kec. Pulo Bryan Darat Medan Timur',
            ],
            [
                'name' => 'Pool Sibolga',
                'address' => 'Jl. Padangsidempuan (Sebelum Simpang Matauli) KM 8,5 Kecamatan Lubuk Tukko, Kelurahan Pandan',
            ],
            [
                'name' => 'Pool Dumai',
                'address' => 'Jl. Soekarno - Hatta, Bukit Timah, Dumai Selatan, Dumai City, Riau',
            ],
            [
                'name' => 'Kantor Palembang',
                'address' => 'Jln. Kimerogan No.23 Sebelah Lrg. Naskah atau Tunggal Diesel Kec. Kertapati Kel. Kemang Agung Palembang 30257',
            ],
            [
                'name' => 'Pool Palembang',
                'address' => 'Jln. Kimerogan No.23 Sebelah Lrg. Naskah atau Tunggal Diesel Kec. Kertapati Kel. Kemang Agung Palembang 30257',
            ],
            [
                'name' => 'Pool Samarinda',
                'address' => 'Jl. Gerbang Dayaku, no.19 RT. 06, RW. 02, Loa Duri Ulu Kec. Loa Janan, Kukar, Kalimantan Timur',
            ],
            [
                'name' => 'Pool Sampit',
                'address' => 'Jalan Tjilik Riwut Km 4,5 sebelah pom bensin semekto Sampit - Kotawaringin Timur Kalimantan Tengah, 74312',
            ],
            [
                'name' => 'Pool Surabaya',
                'address' => 'Jalan Mayjen Sungkono Nomor 888, Prambangan Gresik, Jawa Timur',
            ],
            [
                'name' => 'Kantor Balikpapan',
                'address' => 'JL. A.W. Syahrani RT.03 Somber Batu Ampar Balikpapan Utara Balikpapan, 76126',
            ],
            [
                'name' => 'Pool Pangkalan Bun',
                'address' => '',
            ],
            [
                'name' => 'Pool Kendari',
                'address' => 'Jl. Ruruhi, Anggoeya, Kec. Poasia, Kota Kendari, Sulawesi Tenggara 93232 Depan SDN 65 KENDARI',
            ],
            [
                'name' => 'Pool Pontianak',
                'address' => 'Jl Budi Utomo No. 89 Siantan Hulu, Kec Pontianak Utara. Kota Pontianak Kalimantan Barat',
            ],
            [
                'name' => 'Pool Banjarmasin',
                'address' => '',
            ],
            [
                'name' => 'SPBU Padangsidimpuan',
                'address' => 'Jl. imam bonjol no.197 - PSP SPBU 14.227.316',
            ],
            [
                'name' => 'SPBU Natal',
                'address' => 'Desa Panggautan     Kec. Natal Kab.Mandiling Natal SPBU 14.229.325',
            ],
            [
                'name' => 'IndraMart',
                'address' => 'Jl. imam bonjol no.197 - PSP SPBU 14.227.316',
            ]
        ];

        foreach ($locations as $loc) {
            Location::create([
                'id' => Str::uuid(),
                'name' => $loc['name'],
                'address' => $loc['address'],
            ]);
        }
    }
}
