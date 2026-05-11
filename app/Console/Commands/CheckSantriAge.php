<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Santri;
use App\Models\Notification;
use Carbon\Carbon;

class CheckSantriAge extends Command
{
    protected $signature = 'check:santri-age';
    protected $description = 'Cek santri yang sudah mencapai usia 3 tahun';

    public function handle()
    {
        $santris = Santri::where('notifikasi_usia', false)->get();

        foreach ($santris as $santri) {
            $usia = Carbon::parse($santri->tanggal_lahir)->age;

            if ($usia >= 3) {
                Notification::create([
                    'santri_id' => $santri->id,
                    'judul' => 'Santri mencapai usia 3 tahun',
                    'pesan' => $santri->nama . ' telah mencapai usia ' . $usia . ' tahun.'
                ]);

                $santri->update([
                    'notifikasi_usia' => true
                ]);

                $this->info('Notifikasi dibuat untuk: ' . $santri->nama);
            }
        }

        $this->info('Pengecekan usia santri selesai.');
    }
}
