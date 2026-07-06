<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\UsesTpqScope;
use App\Models\KeuanganSpp;
use App\Models\KeuanganPembangunan;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use App\Services\ActivityLogService;

class LaporanController extends Controller
{
    use UsesTpqScope;

    private function getLetterhead(int $tpqId): array
    {
        if ($tpqId === 2) {
            return [
                'style' => 'tsaubatul',
                'name' => 'TPQ/TQA TSAUBATUL JANNAH',
                'address' => 'Perum GMP Blok E No.60 Duriangkang-Sei Beduk-Batam',
                'registration' => 'No Reg: 41127.1.05/1.213/IX/2016',
                'statistic' => 'No Statistik: 411271.09.1.213',
                'phone' => 'Telp: 0813772812309 - 08556573111',
            ];
        }

        return [
            'style' => 'barakatul',
            'logo' => 'images/qiraati-logo-putih.png',
            'kaligrafi' => 'images/kaligrafi-putih-transparan.png',
            'school_name' => "BARAKATUL QUR'AN",
            'school_number' => '06.02.03.001',
            'address_lines' => [
                'Perum. GMP Blok N No. 85-86, Kel. Duriangkang,',
                'Kec. Sungai Beduk, Kota Batam, Kepri',
                'Telp. 0813 7283 6025',
            ],
        ];
    }

    private function getDataLaporan(Request $request)
    {
        $tpqId = $this->currentTpqId();

        $dateFrom = $request->get('date_from');
        $dateTo = $request->get('date_to');
        $filterMonth = $request->get('filter_month');
        $type = $request->get('type', 'all');
        $search = trim((string) $request->get('search', ''));
        $transactionType = $request->get('transaction_type');

        $sppQuery = KeuanganSpp::with([
            'student:id,tpq_id,name,nisn',
            'user:id,tpq_id,name,username',
        ])
            ->where('tpq_id', $tpqId);

        $pembangunanQuery = KeuanganPembangunan::with([
            'financialCategory:id,tpq_id,name',
            'user:id,tpq_id,name,username',
        ])
            ->where('tpq_id', $tpqId);

        if ($dateFrom) {
            $sppQuery->whereDate('payment_date', '>=', $dateFrom);
            $pembangunanQuery->whereDate('payment_date', '>=', $dateFrom);
        }

        if ($dateTo) {
            $sppQuery->whereDate('payment_date', '<=', $dateTo);
            $pembangunanQuery->whereDate('payment_date', '<=', $dateTo);
        }

        if ($filterMonth) {
            $sppQuery->whereDate('payment_date', '>=', $filterMonth . '-01')
                ->whereDate('payment_date', '<=', date('Y-m-t', strtotime($filterMonth . '-01')));
            $pembangunanQuery->whereDate('payment_date', '>=', $filterMonth . '-01')
                ->whereDate('payment_date', '<=', date('Y-m-t', strtotime($filterMonth . '-01')));
        }

        if ($transactionType && in_array($transactionType, ['income', 'expense'], true)) {
            $pembangunanQuery->where('transaction_type', $transactionType);
        }

        if ($search !== '') {
            $likeSearch = '%' . $search . '%';
            $normalizedSearch = strtolower($search);

            $sppQuery->where(function ($query) use ($likeSearch, $tpqId) {
                $query
                    ->where('payment_date', 'like', $likeSearch)
                    ->orWhere('month', 'like', $likeSearch)
                    ->orWhere('year', 'like', $likeSearch)
                    ->orWhere('amount', 'like', $likeSearch)
                    ->orWhere('note', 'like', $likeSearch)
                    ->orWhereHas('student', function ($studentQuery) use ($likeSearch, $tpqId) {
                        $studentQuery
                            ->where('tpq_id', $tpqId)
                            ->where(function ($q) use ($likeSearch) {
                                $q->where('name', 'like', $likeSearch)
                                    ->orWhere('nisn', 'like', $likeSearch);
                            });
                    })
                    ->orWhereHas('user', function ($userQuery) use ($likeSearch, $tpqId) {
                        $userQuery
                            ->where('tpq_id', $tpqId)
                            ->where(function ($q) use ($likeSearch) {
                                $q->where('name', 'like', $likeSearch)
                                    ->orWhere('username', 'like', $likeSearch);
                            });
                    });
            });

            $pembangunanQuery->where(function ($query) use ($likeSearch, $normalizedSearch, $tpqId) {
                $query
                    ->where('payment_date', 'like', $likeSearch)
                    ->orWhere('amount', 'like', $likeSearch)
                    ->orWhere('note', 'like', $likeSearch)
                    ->orWhereHas('financialCategory', function ($categoryQuery) use ($likeSearch, $tpqId) {
                        $categoryQuery
                            ->where('tpq_id', $tpqId)
                            ->where('name', 'like', $likeSearch);
                    })
                    ->orWhereHas('user', function ($userQuery) use ($likeSearch, $tpqId) {
                        $userQuery
                            ->where('tpq_id', $tpqId)
                            ->where(function ($q) use ($likeSearch) {
                                $q->where('name', 'like', $likeSearch)
                                    ->orWhere('username', 'like', $likeSearch);
                            });
                    });

                if (str_contains($normalizedSearch, 'pengeluaran') || str_contains($normalizedSearch, 'expense')) {
                    $query->orWhere('transaction_type', 'expense');
                }

                if (str_contains($normalizedSearch, 'pemasukan') || str_contains($normalizedSearch, 'income')) {
                    $query->orWhere('transaction_type', 'income');
                }
            });
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
                    'transaction_type' => $item->transaction_type,
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

        $totalPembangunanPemasukan = $pembangunan
            ->where('transaction_type', 'income')
            ->sum('amount');

        $totalPembangunanPengeluaran = $pembangunan
            ->where('transaction_type', 'expense')
            ->sum('amount');

        $totalPembangunan = $totalPembangunanPemasukan - $totalPembangunanPengeluaran;
        $totalPemasukan = $totalSpp + $totalPembangunan;

        $reportTitle = match ($type) {
            'spp' => 'Laporan Keuangan SPP',
            'pembangunan' => 'Laporan Keuangan Pembangunan',
            default => 'Laporan Keuangan',
        };

        $letterhead = $this->getLetterhead($tpqId);

        return compact(
            'data',
            'totalSpp',
            'totalPembangunan',
            'totalPembangunanPemasukan',
            'totalPembangunanPengeluaran',
            'totalPemasukan',
            'dateFrom',
            'dateTo',
            'filterMonth',
            'type',
            'search',
            'transactionType',
            'reportTitle',
            'letterhead'
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
                'tpq_id' => $this->currentTpqId(),
                'report_type' => 'financial_report',
                'type' => $request->get('type', 'all'),
                'date_from' => $request->get('date_from'),
                'date_to' => $request->get('date_to'),
                'filter_month' => $request->get('filter_month'),
                'search' => $request->get('search'),
                'transaction_type' => $request->get('transaction_type'),
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
                'tpq_id' => $this->currentTpqId(),
                'report_type' => 'financial_report',
                'type' => $type,
                'date_from' => $request->get('date_from'),
                'date_to' => $request->get('date_to'),
                'filter_month' => $request->get('filter_month'),
                'search' => $request->get('search'),
                'transaction_type' => $request->get('transaction_type'),
                'mode' => 'download_pdf',
            ],
            description: 'Downloaded financial report PDF'
        );

        $pdf = Pdf::loadView('laporan.keuangan', $laporan)
            ->setPaper('a4', 'landscape');

        return $pdf->download($fileName);
    }
}
