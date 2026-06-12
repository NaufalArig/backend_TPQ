<?php

namespace App\Console\Commands;

use App\Models\Notification;
use App\Models\Santri;
use Carbon\Carbon;
use Illuminate\Console\Command;

class CheckSantriAge extends Command
{
    protected $signature = 'check:santri-age';
    protected $description = 'Cek santri yang sudah mencapai usia 3 tahun';

    public function handle()
    {
        $santris = Santri::where('age_notification_sent', false)
            ->whereNotNull('tpq_id')
            ->whereNotNull('birth_date')
            ->get();

        foreach ($santris as $santri) {
            $usia = Carbon::parse($santri->birth_date)->age;

            if ($usia >= 3) {
                Notification::create([
                    'tpq_id' => $santri->tpq_id,
                    'student_id' => $santri->id,
                    'user_id' => null,
                    'title' => 'Santri mencapai usia 3 tahun',
                    'message' => $santri->name . ' telah mencapai usia ' . $usia . ' tahun.',
                    'type' => 'student_age',
                    'is_read' => false,
                ]);

                $santri->update([
                    'age_notification_sent' => true,
                ]);

                $this->info('Notifikasi dibuat untuk: ' . $santri->name);
            }
        }

        $this->info('Pengecekan usia santri selesai.');
    }
}
