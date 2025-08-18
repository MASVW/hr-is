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
            "IT",
            "FINANCE",
            "ACCOUNTING",
            "AUDIT",
            "HR Manager",
            "HR Staff",
            "PROCUREMENT",
            "AMT",
            "MT"
        ];

        foreach ($departmentData as $name) {
            Department::create([
                'name' => $name,
            ]);
        }
    }
}
