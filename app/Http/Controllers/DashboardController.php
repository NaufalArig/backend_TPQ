<?php

namespace App\Http\Controllers;

use App\Models\Santri;
use App\Models\Guru;
use App\Models\User;
use App\Models\Keuangan;
use App\Models\Notification;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function index()
    {
        $bulanIni = Carbon::now()->month;
        $tahunIni = Carbon::now()->year;

        $keuanganBulanIni = Keuangan::whereMonth('tanggal', $bulanIni)
            ->whereYear('tanggal', $tahunIni)
            ->get();

        $pemasukan   = $keuanganBulanIni->where('jenis', 'pemasukan')->sum('nominal');
        $pengeluaran = $keuanganBulanIni->where('jenis', 'pengeluaran')->sum('nominal');

        $chartKeuangan = [];
        for ($i = 5; $i >= 0; $i--) {
            $bulan = Carbon::now()->subMonths($i);
            $pemasukann = Keuangan::where('jenis', 'pemasukan')
                ->whereMonth('tanggal', $bulan->month)
                ->whereYear('tanggal', $bulan->year)
                ->sum('nominal');
            $pengeluarann = Keuangan::where('jenis', 'pengeluaran')
                ->whereMonth('tanggal', $bulan->month)
                ->whereYear('tanggal', $bulan->year)
                ->sum('nominal');

            $chartKeuangan[] = [
                'bulan'       => $bulan->translatedFormat('M Y'),
                'pemasukan'   => (float) $pemasukann,
                'pengeluaran' => (float) $pengeluarann,
            ];
        }

        return response()->json([
            'total_santri'           => Santri::count(),
            'total_guru'             => Guru::count(),
            'total_user'             => User::count(),
            'pemasukan'              => $pemasukan,
            'pengeluaran'            => $pengeluaran,
            'saldo'                  => $pemasukan - $pengeluaran,
            'chart_keuangan'         => $chartKeuangan,
            'notifikasi_belum_dibaca' => Notification::where('dibaca', false)->count(),
            'santri_pending'         => Santri::where('status', 'pending')
                ->latest()
                ->take(5)
                ->get(['id', 'nama', 'tanggal_lahir', 'tanggal_masuk']),
            'transaksi_terakhir'     => Keuangan::with('user:id,name')
                ->latest()
                ->take(5)
                ->get(),
        ]);
    }
}
