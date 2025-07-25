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
        return [
            'phase_id' => null,
            'title' => "Testing Request",
            'status' => 'pending',
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
