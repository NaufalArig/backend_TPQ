<?php

namespace App\Http\Controllers;

use App\Models\Keuangan;
use Barryvdh\DomPDF\Facade\Pdf;

class LaporanController extends Controller
{
    public function preview()
    {
        $data = Keuangan::all();

        $pdf = Pdf::loadView('laporan.keuangan', compact('data'));

        return $pdf->stream();
    }

    public function download()
    {
        $data = Keuangan::all();

        $pdf = Pdf::loadView('laporan.keuangan', compact('data'));

        return $pdf->download('laporan-keuangan.pdf');
    }
}
