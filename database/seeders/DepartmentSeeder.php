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
            "MARKETING",
            "PLANTATION",
            "PROCUREMENT",
            "RETAIL",
            "SHIPPING",
            "SPBD",
            "TOP",
            "TRANSPORT",
        ];

        foreach ($departmentData as $name) {
            Department::create([
                'name' => $name,
            ]);
        }
    }
}
