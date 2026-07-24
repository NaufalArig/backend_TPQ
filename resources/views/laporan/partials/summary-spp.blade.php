{{-- Expects: $sppSummary --}}
<div class="summary-section" style="width:60%;">
    <table class="summary-table">
        <thead>
            <tr>
                <th colspan="2">RINGKASAN LAPORAN SPP</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>Tahun</td>
                <td class="td-right">{{ $sppSummary['year'] }}</td>
            </tr>
            <tr>
                <td>Bulan</td>
                <td class="td-right">{{ $sppSummary['month_label'] }}</td>
            </tr>
            <tr>
                <td>Tanggal Cetak</td>
                <td class="td-right">{{ $sppSummary['print_date'] }}</td>
            </tr>
            <tr>
                <td>Jumlah Santri</td>
                <td class="td-right">{{ $sppSummary['total_santri'] }}</td>
            </tr>
            <tr>
                <td>Sudah Membayar</td>
                <td class="val-total">{{ $sppSummary['sudah_membayar'] }}</td>
            </tr>
            <tr>
                <td>Belum Membayar</td>
                <td class="val-expense">{{ $sppSummary['belum_membayar'] }}</td>
            </tr>
            <tr class="saldo-row">
                <td>Total Pemasukan</td>
                <td class="val-total">
                    Rp {{ number_format($sppSummary['total_pemasukan'], 0, ',', '.') }}
                </td>
            </tr>
        </tbody>
    </table>
</div>
