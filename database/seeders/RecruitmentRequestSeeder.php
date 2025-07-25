<?php

namespace Database\Seeders;

use App\Models\RecruitmentRequest;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class RecruitmentRequestSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        RecruitmentRequest::factory()->count(5)->create();
    }
}
