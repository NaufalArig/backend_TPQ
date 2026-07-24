<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>{{ $reportTitle ?? 'Laporan Keuangan TPQ' }}</title>
    @include('laporan.partials.style')
</head>

<body>
    @include('laporan.partials.letterhead')

    <div class="report-title">{{ $reportTitle ?? 'Laporan Keuangan' }}</div>
    <div class="report-date">
        Periode:
        {{ $dateFrom ? \Carbon\Carbon::parse($dateFrom)->format('d-m-Y') : 'Awal' }}
        s/d
        {{ $dateTo ? \Carbon\Carbon::parse($dateTo)->format('d-m-Y') : 'Akhir' }}
        @if (!empty($search))
            | Pencarian: {{ $search }}
        @endif
        @if (!empty($transactionType))
            | Jenis:
            {{ $transactionType === 'expense' ? 'Pengeluaran' : 'Pemasukan' }}
        @endif
        | Tanggal Cetak: {{ date('d-m-Y') }}
    </div>
    <hr class="divider">

    @php
        // Nama/Santri hanya relevan untuk baris SPP. Saat laporan khusus
        // Pembangunan (tidak dicampur dengan SPP), kolom ini selalu '-'
        // untuk semua baris sehingga dihilangkan saja.
        $showNamaSantri = $type !== 'pembangunan';
    @endphp

    <table class="main-table">
        <thead>
            <tr>
                @if ($showNamaSantri)
                    <th style="width:4%">No</th>
                    <th style="width:12%">Tanggal</th>
                    <th style="width:13%">Jenis</th>
                    <th style="width:16%">Nama/Santri</th>
                    <th style="width:15%">Kategori</th>
                    <th style="width:14%">Nominal</th>
                    <th style="width:16%">Keterangan</th>
                    <th style="width:10%">Petugas</th>
                @else
                    <th style="width:5%">No</th>
                    <th style="width:14%">Tanggal</th>
                    <th style="width:15%">Jenis</th>
                    <th style="width:18%">Kategori</th>
                    <th style="width:17%">Nominal</th>
                    <th style="width:19%">Keterangan</th>
                    <th style="width:12%">Petugas</th>
                @endif
            </tr>
        </thead>
        <tbody>
            @if ($data->isEmpty())
                <tr class="empty-row">
                    <td colspan="{{ $showNamaSantri ? 8 : 7 }}">Tidak ada data keuangan</td>
                </tr>
            @else
                @foreach ($data as $i => $item)
                    @if ($item['type'] === 'SPP')
                        @include('laporan.partials.row-spp', ['i' => $i, 'item' => $item])
                    @else
                        @include('laporan.partials.row-pembangunan', ['i' => $i, 'item' => $item, 'showNamaSantri' => $showNamaSantri])
                    @endif
                @endforeach
            @endif
        </tbody>
    </table>

    @if ($type === 'spp' && !empty($sppSummary))
        @include('laporan.partials.summary-spp')
    @elseif ($type === 'pembangunan')
        @include('laporan.partials.summary-pembangunan')
    @else
        @include('laporan.partials.summary-gabungan')
    @endif

    <div class="footer-line">
        <div class="footer-text">
            Dokumen ini dicetak secara otomatis oleh sistem TPQ | {{ date('d-m-Y') }}
        </div>
    </div>
</body>

</html>
