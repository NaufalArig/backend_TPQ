{{-- Expects: $i (zero-based index), $item (array with type === 'SPP') --}}
<tr>
    <td class="td-center">{{ $i + 1 }}</td>

    <td class="td-center">
        {{ $item['payment_date'] ? \Carbon\Carbon::parse($item['payment_date'])->format('d-m-Y') : '-' }}
    </td>

    <td class="td-center">
        <span class="badge-spp">SPP</span>
    </td>

    <td>
        {{ $item['name'] ?? '-' }}
    </td>

    <td>
        SPP {{ $item['month'] }}/{{ $item['year'] }}
    </td>

    <td class="td-right">
        Rp {{ number_format($item['amount'], 0, ',', '.') }}
    </td>

    <td>
        {{ $item['note'] ?? '-' }}
    </td>

    <td class="td-center">
        {{ $item['user'] ?? '-' }}
    </td>
</tr>
