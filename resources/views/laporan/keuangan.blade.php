<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Laporan Keuangan TPQ</title>
    <style>
        @page {
            margin: 24px;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 11px;
            color: #263238;
            background: #fff;
        }

        .header {
            background: #27876f;
            color: white;
            text-align: center;
            padding: 18px 16px;
            margin-bottom: 18px;
        }

        .header h1 {
            font-size: 24px;
            letter-spacing: 4px;
            margin-bottom: 4px;
        }

        .header p {
            font-size: 11px;
            color: #C8E6C9;
        }

        .report-title {
            text-align: center;
            color: #1B5E20;
            font-size: 18px;
            font-weight: bold;
            margin-top: 4px;
        }

        .report-date {
            text-align: center;
            font-size: 10px;
            color: #607D8B;
            margin-top: 4px;
            margin-bottom: 14px;
        }

        .divider {
            border: none;
            border-top: 2px solid #2E7D32;
            margin-bottom: 18px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        .main-table {
            margin-bottom: 22px;
        }

        thead tr {
            background: #6fcf97;
            color: black;
        }

        thead th {
            padding: 10px 8px;
            text-align: center;
            font-size: 10px;
            border: 0.5px solid #C8E6C9;
        }

        tbody td {
            padding: 9px 8px;
            border: 0.5px solid #CFD8DC;
            font-size: 10px;
            vertical-align: middle;
        }

        tbody tr:nth-child(even) {
            background: #F4F6F7;
        }

        .td-center {
            text-align: center;
        }

        .td-right {
            text-align: right;
        }

        .badge-pemasukan {
            color: #1B5E20;
            font-weight: bold;
        }

        .badge-pengeluaran {
            color: #C62828;
            font-weight: bold;
        }

        .summary-section {
            width: 45%;
            margin-left: auto;
            margin-top: 8px;
        }

        .summary-table thead th {
            background: #2E7D32;
            color: white;
            padding: 9px;
            font-size: 10px;
        }

        .summary-table tbody td {
            padding: 9px 10px;
            font-size: 10px;
            border: 0.5px solid #C8E6C9;
        }

        .summary-table tbody tr {
            background: #F8FBF8;
        }

        .summary-table .saldo-row td {
            background: #E8F5E9;
            font-weight: bold;
            font-size: 11px;
        }

        .val-hijau {
            color: #1B5E20;
            text-align: right;
            font-weight: bold;
        }

        .val-merah {
            color: #C62828;
            text-align: right;
            font-weight: bold;
        }

        .footer-line {
            border-top: 0.5px solid #A5D6A7;
            margin-top: 28px;
            padding-top: 10px;
        }

        .footer-text {
            text-align: center;
            font-size: 9px;
            color: #90A4AE;
            font-style: italic;
        }

        .empty-row td {
            text-align: center;
            padding: 24px;
            color: #90A4AE;
            font-style: italic;
        }
    </style>
</head>

<body>

    {{-- HEADER --}}
    <div class="header">
        <h1>TPQ</h1>
        <p>Taman Pendidikan Al-Qur'an</p>
    </div>

    {{-- JUDUL --}}
    <div class="report-title">LAPORAN KEUANGAN</div>
    <div class="report-date">Tanggal Cetak: {{ date('d-m-Y') }}</div>
    <hr class="divider">

    {{-- TABEL DATA --}}
    <table class="main-table">
        <thead>
            <tr>
                <th style="width:5%">No</th>
                <th style="width:15%">Tanggal</th>
                <th style="width:18%">Jenis</th>
                <th style="width:20%">Nominal (Rp)</th>
                <th>Keterangan</th>
            </tr>
        </thead>
        <tbody>
            @if ($data->isEmpty())
                <tr class="empty-row">
                    <td colspan="5">Tidak ada data keuangan</td>
                </tr>
            @else
                @foreach ($data as $i => $item)
                    <tr>
                        <td class="td-center">{{ $i + 1 }}</td>
                        <td class="td-center">{{ $item->tanggal }}</td>
                        <td class="td-center">
                            @if ($item->jenis === 'pemasukan')
                                <span class="badge-pemasukan">▲ Pemasukan</span>
                            @else
                                <span class="badge-pengeluaran">▼ Pengeluaran</span>
                            @endif
                        </td>
                        <td class="td-right">{{ number_format($item->nominal, 0, ',', '.') }}</td>
                        <td>{{ $item->keterangan }}</td>
                    </tr>
                @endforeach
            @endif
        </tbody>
    </table>

    {{-- RINGKASAN --}}
    @php
        $totalPemasukan = $data->where('jenis', 'pemasukan')->sum('nominal');
        $totalPengeluaran = $data->where('jenis', 'pengeluaran')->sum('nominal');
        $saldo = $totalPemasukan - $totalPengeluaran;
    @endphp

    <div class="summary-section">
        <table class="summary-table">
            <thead>
                <tr>
                    <th colspan="2">RINGKASAN KEUANGAN</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>Total Pemasukan</td>
                    <td class="val-hijau">Rp {{ number_format($totalPemasukan, 0, ',', '.') }}</td>
                </tr>
                <tr>
                    <td>Total Pengeluaran</td>
                    <td class="val-merah">Rp {{ number_format($totalPengeluaran, 0, ',', '.') }}</td>
                </tr>
                <tr class="saldo-row">
                    <td>Saldo Akhir</td>
                    <td class="val-saldo {{ $saldo >= 0 ? 'val-hijau' : 'val-merah' }}">
                        Rp {{ number_format($saldo, 0, ',', '.') }}
                    </td>
                </tr>
            </tbody>
        </table>
    </div>


    {{-- FOOTER --}}
    <div class="footer-line">
        <div class="footer-text">
            Dokumen ini dicetak secara otomatis oleh sistem TPQ · {{ date('d-m-Y') }}
        </div>
    </div>

</body>

</html>
