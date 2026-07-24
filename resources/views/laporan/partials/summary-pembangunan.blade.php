{{-- Expects: $totalPembangunanPemasukan, $totalPembangunanPengeluaran, $totalPembangunan --}}
<div class="summary-section">
    <table class="summary-table">
        <thead>
            <tr>
                <th colspan="2">RINGKASAN KEUANGAN PEMBANGUNAN</th>
            </tr>
        </thead>
        <tbody>
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
            <tr class="saldo-row">
                <td>Saldo Pembangunan</td>
                <td class="val-total">
                    Rp {{ number_format($totalPembangunan, 0, ',', '.') }}
                </td>
            </tr>
        </tbody>
    </table>
</div>
