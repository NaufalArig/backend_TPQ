<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Guru;
use Carbon\Carbon;

class UpdateGuruStatus extends Command
{
    protected $signature = 'guru:update-status';

    protected $description = 'Update status guru pending menjadi aktif';

    public function handle()
    {
        Guru::whereNull('tanggal_keluar')
            ->where('status', 'pending')
            ->whereDate('tanggal_masuk', '<=', now())
            ->update(['status' => 'aktif']);

        Guru::whereNotNull('tanggal_keluar')
            ->whereDate('tanggal_keluar', '<=', now())
            ->update(['status' => 'nonaktif']);

        Guru::where('status', 'nonaktif')
            ->whereNotNull('tanggal_keluar')
            ->whereDate('tanggal_keluar', '<=', now()->subDays(5))
            ->delete();

        $this->info('Status guru berhasil diperbarui.');
    }
}
