{{-- Expects: $i (zero-based index), $item (array with type === 'Pembangunan') --}}
<tr>
    <td class="td-center">{{ $i + 1 }}</td>

    <td class="td-center">
        {{ $item['payment_date'] ? \Carbon\Carbon::parse($item['payment_date'])->format('d-m-Y') : '-' }}
    </td>

    <td class="td-center">
        @if (($item['transaction_type'] ?? 'income') === 'expense')
            <span class="badge-expense">Pembangunan - Pengeluaran</span>
        @else
            <span class="badge-income">Pembangunan - Pemasukan</span>
        @endif
    </td>

    <td>
        {{ $item['category'] ?? '-' }}
    </td>

    <td class="td-right">
        @if (($item['transaction_type'] ?? 'income') === 'expense')
            <span class="val-expense">- Rp {{ number_format($item['amount'], 0, ',', '.') }}</span>
        @else
            Rp {{ number_format($item['amount'], 0, ',', '.') }}
        @endif
    </td>

    <td>
        {{ $item['note'] ?? '-' }}
    </td>

    <td class="td-center">
        {{ $item['user'] ?? '-' }}
    </td>
</tr>
