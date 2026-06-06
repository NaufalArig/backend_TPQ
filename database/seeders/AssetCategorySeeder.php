<?php

namespace Database\Seeders;

use App\Models\AssetCategory;
use Illuminate\Database\Seeder;

class AssetCategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            'Elektronik',
            'Meja Kursi',
            'Alat Tulis',
            'Buku/Iqro',
            'Perlengkapan Ibadah',
            'Bangunan/Ruangan',
            'Lainnya',
        ];

        foreach ($categories as $category) {
            AssetCategory::updateOrCreate(
                ['name' => $category],
                [
                    'description' => 'Kategori aset ' . $category,
                    'status' => 'active',
                ]
            );
        }
    }
}
