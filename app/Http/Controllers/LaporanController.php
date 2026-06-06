<?php

namespace App\Http\Controllers;

use App\Models\KeuanganSpp;
use App\Models\KeuanganPembangunan;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use App\Services\ActivityLogService;

class LaporanController extends Controller
{
    private function getDataLaporan(Request $request)
    {
        $dateFrom = $request->get('date_from');
        $dateTo = $request->get('date_to');
        $type = $request->get('type', 'all');

        $sppQuery = KeuanganSpp::with([
            'student:id,name,nisn',
            'user:id,name,username',
        ]);

        $pembangunanQuery = KeuanganPembangunan::with([
            'financialCategory:id,name',
            'user:id,name,username',
        ]);

        if ($dateFrom) {
            $sppQuery->whereDate('payment_date', '>=', $dateFrom);
            $pembangunanQuery->whereDate('payment_date', '>=', $dateFrom);
        }

        if ($dateTo) {
            $sppQuery->whereDate('payment_date', '<=', $dateTo);
            $pembangunanQuery->whereDate('payment_date', '<=', $dateTo);
        }

        $spp = $sppQuery
            ->get()
            ->map(function ($item) {
                return [
                    'id' => $item->id,
                    'payment_date' => optional($item->payment_date)->format('Y-m-d'),
                    'type' => 'SPP',
                    'name' => $item->student?->name ?? '-',
                    'category' => 'SPP',
                    'month' => $item->month,
                    'year' => $item->year,
                    'amount' => (float) $item->amount,
                    'note' => $item->note,
                    'user' => $item->user?->name ?? '-',
                    'created_at' => $item->created_at,
                ];
            });

        $pembangunan = $pembangunanQuery
            ->get()
            ->map(function ($item) {
                return [
                    'id' => $item->id,
                    'payment_date' => optional($item->payment_date)->format('Y-m-d'),
                    'type' => 'Pembangunan',
                    'name' => '-',
                    'category' => $item->financialCategory?->name ?? '-',
                    'month' => null,
                    'year' => null,
                    'amount' => (float) $item->amount,
                    'note' => $item->note,
                    'user' => $item->user?->name ?? '-',
                    'created_at' => $item->created_at,
                ];
            });

        if ($type === 'spp') {
            $pembangunan = collect();
        }

        if ($type === 'pembangunan') {
            $spp = collect();
        }

        $data = $spp
            ->merge($pembangunan)
            ->sortByDesc(function ($item) {
                return $item['payment_date'] . ' ' . $item['created_at'];
            })
            ->values();

        $totalSpp = $spp->sum('amount');
        $totalPembangunan = $pembangunan->sum('amount');
        $totalPemasukan = $totalSpp + $totalPembangunan;

        $reportTitle = match ($type) {
            'spp' => 'Laporan Keuangan SPP',
            'pembangunan' => 'Laporan Keuangan Pembangunan',
            default => 'Laporan Keuangan',
        };

        return compact(
            'data',
            'totalSpp',
            'totalPembangunan',
            'totalPemasukan',
            'dateFrom',
            'dateTo',
            'type',
            'reportTitle'
        );
    }

    public function preview(Request $request)
    {
        $laporan = $this->getDataLaporan($request);

        ActivityLogService::log(
            action: 'print',
            module: 'reports',
            entity: null,
            oldValues: null,
            newValues: [
                'report_type' => 'financial_report',
                'type' => $request->get('type', 'all'),
                'date_from' => $request->get('date_from'),
                'date_to' => $request->get('date_to'),
                'mode' => 'preview',
            ],
            description: 'Previewed financial report'
        );

        $pdf = Pdf::loadView('laporan.keuangan', $laporan)
            ->setPaper('a4', 'landscape');

        return $pdf->stream('laporan-keuangan.pdf');
    }

    public function download(Request $request)
    {
        $laporan = $this->getDataLaporan($request);

        $type = $request->get('type', 'all');

        $fileName = match ($type) {
            'spp' => 'laporan-keuangan-spp.pdf',
            'pembangunan' => 'laporan-keuangan-pembangunan.pdf',
            default => 'laporan-keuangan.pdf',
        };

        ActivityLogService::log(
            action: 'export',
            module: 'reports',
            entity: null,
            oldValues: null,
            newValues: [
                'report_type' => 'financial_report',
                'type' => $type,
                'date_from' => $request->get('date_from'),
                'date_to' => $request->get('date_to'),
                'mode' => 'download_pdf',
            ],
            description: 'Downloaded financial report PDF'
        );

        $pdf = Pdf::loadView('laporan.keuangan', $laporan)
            ->setPaper('a4', 'landscape');

        return $pdf->download($fileName);
    }
}
