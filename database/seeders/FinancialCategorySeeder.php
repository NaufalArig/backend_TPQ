<?php

namespace Database\Seeders;

use App\Models\KategoriKeuangan;
use Illuminate\Database\Seeder;

class FinancialCategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            'Infaq',
            'Pendaftaran Gedung',
            'Wakaf',
            'Renovasi',
            'Operasional TPQ',
            'Lainnya',
        ];

        foreach ($categories as $category) {
            KategoriKeuangan::updateOrCreate(
                ['name' => $category],
                [
                    'description' => 'Kategori keuangan ' . $category,
                    'status' => 'active',
                ]
            );
        }
    }
}
