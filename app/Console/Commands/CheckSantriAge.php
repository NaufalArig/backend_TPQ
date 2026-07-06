<?php

namespace App\Console\Commands;

use App\Models\Notification;
use App\Models\Santri;
use Illuminate\Console\Command;

class CheckSantriAge extends Command
{
    protected $signature = 'check:santri-age';
    protected $description = 'Cek santri yang akan atau sudah mencapai usia 3 tahun';

    public function handle()
    {
        $today = now()->toDateString();
        $nextWeek = now()->addDays(7)->toDateString();

        $santris = Santri::where('age_notification_sent', false)
            ->whereNotNull('tpq_id')
            ->whereNotNull('join_date')
            ->whereDate('join_date', '<=', $nextWeek)
            ->get();

        if ($santris->isEmpty()) {
            $this->info('Tidak ada santri yang perlu dibuatkan notifikasi.');
            return Command::SUCCESS;
        }

        foreach ($santris as $santri) {
            $joinDate = \Carbon\Carbon::parse($santri->join_date);
            $daysLeft = now()->startOfDay()->diffInDays($joinDate->copy()->startOfDay(), false);

            if ($daysLeft > 0) {
                $title = 'Santri Akan Siap Masuk TPQ';
                $message = $santri->name . ' akan mencapai usia 3 tahun pada ' .
                    $joinDate->format('d-m-Y') . ' atau sekitar ' . $daysLeft . ' hari lagi.';
            } else {
                $title = 'Santri Siap Masuk TPQ';
                $message = $santri->name . ' sudah mencapai usia 3 tahun dan siap masuk TPQ.';
            }

            Notification::create([
                'tpq_id' => $santri->tpq_id,
                'student_id' => $santri->id,
                'user_id' => null,
                'title' => $title,
                'message' => $message,
                'type' => 'student_age',
                'is_read' => false,
            ]);

            $santri->update([
                'age_notification_sent' => true,
            ]);

            $this->info('Notifikasi dibuat untuk: ' . $santri->name);
        }

        $this->info('Total notifikasi dibuat: ' . $santris->count());

        return Command::SUCCESS;
    }
}
