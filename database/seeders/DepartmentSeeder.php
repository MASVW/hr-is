<?php

namespace Database\Seeders;

use App\Models\Department;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class DepartmentSeeder extends Seeder
{

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
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
            "BBM",
            "MARKETING WILAYAH 1",
            "MARKETING WILAYAH 2",
            "MARKETING WILAYAH 3",
            "MARKETING ",
            "PLANTATION",
            "PROCUREMENT",
            "RETAIL & SPBU",
            "SHIPPING",
            "SPBD",
            "TOP",
            "TRANSPORT",
            "ADMIN OPERASIONAL",
            "OPERASIONAL",
            "ADMIN MARKETING",
            "VHS",
            "HSE",
            "KOMERSIAL"
        ];

        foreach ($departmentData as $name) {
            Department::create([
                'name' => $name,
            ]);
        }
    }
}
