{{-- Expects: $totalSpp, $totalPembangunanPemasukan, $totalPembangunanPengeluaran, $totalPembangunan, $totalPemasukan --}}
<div class="summary-section">
    <table class="summary-table">
        <thead>
            <tr>
                <th colspan="2">RINGKASAN KEUANGAN</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>Total SPP</td>
                <td class="val-total">
                    Rp {{ number_format($totalSpp, 0, ',', '.') }}
                </td>
            </tr>
            <tr>
                <td>Pemasukan Pembangunan</td>
                <td class="val-total">
                    Rp {{ number_format($totalPembangunanPemasukan, 0, ',', '.') }}
                </td>
            </tr>
            <tr>
                <td>Pengeluaran Pembangunan</td>
                <td class="val-expense">
                    - Rp {{ number_format($totalPembangunanPengeluaran, 0, ',', '.') }}
                </td>
            </tr>
            <tr>
                <td>Saldo Pembangunan</td>
                <td class="val-total">
                    Rp {{ number_format($totalPembangunan, 0, ',', '.') }}
                </td>
            </tr>
            <tr class="saldo-row">
                <td>Total Saldo</td>
                <td class="val-total">
                    Rp {{ number_format($totalPemasukan, 0, ',', '.') }}
                </td>
            </tr>
        </tbody>
    </table>
</div>
