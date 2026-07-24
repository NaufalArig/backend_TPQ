@if (($letterhead['style'] ?? 'barakatul') === 'tsaubatul')
    <div class="letterhead-tsaubatul">
        <table class="letterhead-tsaubatul-table">
            <tr>
                <td class="tsaubatul-text">
                    <div class="tsaubatul-name">{{ $letterhead['name'] }}</div>
                    <div class="tsaubatul-address">{{ $letterhead['address'] }}</div>
                    <div class="tsaubatul-meta">
                        {{ $letterhead['registration'] }}
                        {{ $letterhead['statistic'] }}
                    </div>
                    <div class="tsaubatul-meta">{{ $letterhead['phone'] }}</div>
                </td>
            </tr>
        </table>
    </div>
@else
    <div class="letterhead">
        <div class="letterhead-top">
            <table class="letterhead-top-table">
                <tr>
                    <td class="logo-cell">
                        <img src="{{ public_path($letterhead['logo'] ?? 'images/qiraati-logo-putih.png') }}" class="qiraati-logo" alt="Logo Qiraati">
                    </td>
                    <td class="org-text">
                        <div class="arabic-line">
                            <img src="{{ public_path($letterhead['kaligrafi'] ?? 'images/kaligrafi-putih-transparan.png') }}" alt="Kaligrafi">
                        </div>
                        <div class="org-label">KOORDINATOR PENDIDIKAN AL-QUR'AN</div>
                        <div class="method-name">METODE QIRA'ATI</div>
                        <div class="branch">CABANG BATAM - KEPRI</div>
                    </td>
                    <td class="logo-cell">
                        <img src="{{ public_path($letterhead['logo-tpq'] ?? 'images/logo-01.png') }}" class="logo-tpq" alt="Logo TPQ">
                    </td>
                </tr>
            </table>
        </div>

        <div class="letterhead-main">
            <div class="school-small">TAMAN PENDIDIKAN AL-QUR'AN</div>
            <div class="school-name">{{ $letterhead['school_name'] ?? "BARAKATUL QUR'AN" }}</div>
            <div class="school-number">NOMOR INDUK : {{ $letterhead['school_number'] ?? '06.02.03.001' }}</div>
            <div class="school-address">
                @foreach (($letterhead['address_lines'] ?? []) as $line)
                    {{ $line }}@if (!$loop->last)<br>@endif
                @endforeach
            </div>
        </div>
    </div>
@endif
