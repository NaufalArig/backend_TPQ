<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Santri;
use Carbon\Carbon;

class SantriSeeder extends Seeder
{
    public function run(): void
    {
        Santri::create([
            'nama'          => 'Ahmad Fauzi',
            'jenis_kelamin' => 'L',
            'tanggal_lahir' => Carbon::now()->subYears(3)->format('Y-m-d'),
            'nama_wali'     => 'Bapak Fauzi',
            'kontak_wali'   => '081234567890',
            'alamat'        => 'Jl. Contoh No. 1, Batam',
            'tanggal_masuk' => Carbon::now()->format('Y-m-d'),
            'status'        => 'pending',
        ]);
    }
}
