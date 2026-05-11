<?php

namespace App\Http\Controllers;

use App\Models\Santri;
use App\Models\Guru;
use App\Models\Keuangan;
use App\Models\Notification;

class DashboardController extends Controller
{
    public function index()
    {
        $totalSantri = Santri::count();
        $totalGuru = Guru::count();

        $totalPemasukan = Keuangan::where('jenis', 'pemasukan')->sum('nominal');
        $totalPengeluaran = Keuangan::where('jenis', 'pengeluaran')->sum('nominal');

        $saldo = $totalPemasukan - $totalPengeluaran;

        $notifikasiBelumDibaca = Notification::where('dibaca', false)->count();

        return response()->json([
            'total_santri' => $totalSantri,
            'total_guru' => $totalGuru,
            'total_pemasukan' => $totalPemasukan,
            'total_pengeluaran' => $totalPengeluaran,
            'saldo' => $saldo,
            'notifikasi_belum_dibaca' => $notifikasiBelumDibaca,
        ]);
    }
}
