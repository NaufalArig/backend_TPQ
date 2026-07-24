<?php

namespace App\Console\Commands;

use App\Models\Notification;
use App\Models\Santri;
use Carbon\Carbon;
use Illuminate\Console\Command;

class CheckSantriAge extends Command
{
    protected $signature = 'check:santri-age';

    protected $description = 'Cek santri yang sudah berusia 3 tahun tetapi belum aktif';

    public function handle()
    {
        $today = Carbon::today();

        // Ambil semua santri yang:
        // 1. Memiliki TPQ
        // 2. Memiliki tanggal lahir
        // 3. Statusnya belum aktif
        $santris = Santri::query()
            ->whereNotNull('tpq_id')
            ->whereNotNull('birth_date')
            ->where(function ($query) {
                $query->whereNull('status')
                    ->orWhere('status', '!=', 'aktif');
            })
            ->get();

        if ($santris->isEmpty()) {
            $this->info('Tidak ada santri yang perlu diperiksa.');

            return Command::SUCCESS;
        }

        $totalCreated = 0;
        $totalUpdated = 0;

        foreach ($santris as $santri) {

            $birthDate = Carbon::parse($santri->birth_date);

            // Tanggal ketika santri tepat berusia 3 tahun
            $threeYearsDate = $birthDate->copy()->addYears(3);

            // Jika umur santri belum mencapai 3 tahun,
            // lanjut ke santri berikutnya
            if ($today->lt($threeYearsDate)) {
                continue;
            }

            // Hitung berapa hari yang sudah lewat
            // sejak santri tepat berusia 3 tahun
            $daysOver = $threeYearsDate->diffInDays($today);

            $title = 'Santri Belum Diaktifkan';

            if ($daysOver === 0) {
                $message = "{$santri->name} telah mencapai usia 3 tahun "
                    . "dan statusnya masih belum aktif. "
                    . "Silakan periksa dan aktifkan data santri.";
            } else {
                $message = "{$santri->name} telah berusia 3 tahun "
                    . "{$daysOver} hari dan statusnya masih belum aktif. "
                    . "Silakan periksa dan aktifkan data santri.";
            }

            // Cari notifikasi student_age milik santri tersebut
            $notification = Notification::query()
                ->where('tpq_id', $santri->tpq_id)
                ->where('student_id', $santri->id)
                ->where('type', 'student_age')
                ->first();

            if ($notification) {

                // Update notifikasi yang sudah ada
                $notification->update([
                    'title' => $title,
                    'message' => $message,
                    'is_read' => false,
                ]);

                $totalUpdated++;

                $this->info(
                    "Notifikasi diperbarui: {$santri->name} "
                    . "(3 tahun {$daysOver} hari)"
                );

            } else {

                // Buat notifikasi baru
                Notification::create([
                    'tpq_id' => $santri->tpq_id,
                    'student_id' => $santri->id,
                    'user_id' => null,
                    'title' => $title,
                    'message' => $message,
                    'type' => 'student_age',
                    'is_read' => false,
                ]);

                $totalCreated++;

                $this->info(
                    "Notifikasi dibuat: {$santri->name} "
                    . "(3 tahun {$daysOver} hari)"
                );
            }
        }

        $this->info("Total notifikasi baru: {$totalCreated}");
        $this->info("Total notifikasi diperbarui: {$totalUpdated}");

        return Command::SUCCESS;
    }
}
