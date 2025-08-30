<?php

namespace Database\Factories;

use App\Models\RecruitmentRequest;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\RecruitmentRequest>
 */
class RecruitmentRequestFactory extends Factory
{
    protected $model = RecruitmentRequest::class;

    public function definition(): array
    {
        $titleOptions = [
            'Permintaan Rekrutmen Staff Marketing',
            'Permintaan Rekrutmen Staff IT',
            'Permintaan Rekrutmen HRD',
            'Permintaan Rekrutmen Sales Executive',
            'Permintaan Rekrutmen Staff Finance',
            'Permintaan Rekrutmen Staff Accounting',
            'Permintaan Rekrutmen Staff Administrasi',
            'Permintaan Rekrutmen Supervisor Produksi',
            'Permintaan Rekrutmen Operator Produksi',
            'Permintaan Rekrutmen Staff Purchasing',
            'Permintaan Rekrutmen Staff Legal',
            'Permintaan Rekrutmen Staff R&D',
            'Permintaan Rekrutmen Staff Quality Control',
            'Permintaan Rekrutmen Staff PPIC',
            'Permintaan Rekrutmen Staff Warehouse',
            'Permintaan Rekrutmen Staff Logistik',
            'Permintaan Rekrutmen Sales Supervisor',
            'Permintaan Rekrutmen Sales Manager',
            'Permintaan Rekrutmen Staff Customer Service',
            'Permintaan Rekrutmen Staff Call Center',
            'Permintaan Rekrutmen Staff GA',
            'Permintaan Rekrutmen Staff Training',
            'Permintaan Rekrutmen Staff Public Relations',
            'Permintaan Rekrutmen Digital Marketing',
            'Permintaan Rekrutmen Content Creator',
            'Permintaan Rekrutmen Social Media Specialist',
            'Permintaan Rekrutmen Business Analyst',
            'Permintaan Rekrutmen Data Analyst',
            'Permintaan Rekrutmen UI/UX Designer',
            'Permintaan Rekrutmen Software Engineer',
        ];
        return [
            'phase_id' => null,
            'title' => $this->faker->randomElement($titleOptions),
            'status' => 'pending',
            'recruitment_type' => 'pergantian',
            'department_id' => \App\Models\Department::factory(),
            'requested_by' => \App\Models\User::factory(),
            'approval_id' => \App\Models\Approval::factory(),
            'form_data' => [
                'recruitmentSection' => [
                    'jabatan' => 'Staff Accounting',
                    'departemen' => 'Finance',
                    'tipeRekrutmen' => 'Baru',
                    'lokasiPenempatan' => 'Jakarta',
                    'jumlahKaryawan' => 2,
                    'urgency' => 'Urgent',
                    'deskripsiPekerjaan' => 'Melakukan pencatatan dan pelaporan keuangan perusahaan',
                    'deskripsiPekerjaanKhusus' => 'Melakukan pencatatan dan pelaporan keuangan perusahaan dengan secara detail pada bagian admin ini adalah testing untuk teks khusus',
                ],
                'kompensasi' => [
                    'gaji' => 6000000,
                    'tunjanganTransport' => 500000,
                    'tunjanganMakan' => 400000,
                    'tunjanganKomunikasi' => 200000,
                    'tunjanganPerumahan' => 0,
                    'hariKerja' => 'Senin - Jumat',
                ],
                'kualifikasi' => [
                    'jenisKelamin' => 'Pria/Wanita',
                    'agama' => 'Bebas',
                    'status' => 'Single',
                    'pendidikan' => 'S1 Akuntansi',
                    'pengalaman' => 'Minimal 1 tahun di bidang terkait',
                    'kemampuanLainnya' => 'Microsoft Office, Accurate',
                    'nilaiPlus' => 'Bisa bahasa Inggris',
                ]
            ],

        ];
    }
}
