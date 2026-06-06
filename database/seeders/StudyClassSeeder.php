<?php

namespace Database\Seeders;

use App\Models\Kelas;
use Illuminate\Database\Seeder;

class StudyClassSeeder extends Seeder
{
    public function run(): void
    {
        $classes = [
            'Pra TPQ',
            'Iqro 1',
            'Iqro 2',
            'Iqro 3',
            'Iqro 4',
            'Iqro 5',
            'Iqro 6',
            'Al-Qur’an',
            'Gharib',
            'Tajwid',
            'Tahfidz',
        ];

        foreach ($classes as $class) {
            Kelas::updateOrCreate(
                ['name' => $class],
                [
                    'description' => 'Kelas ' . $class,
                    'status' => 'active',
                ]
            );
        }
    }
}
