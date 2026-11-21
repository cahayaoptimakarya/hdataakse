@php use App\Services\CostReport; use App\Support\Fmt; @endphp
@php
    $bln = ['', 'Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
    $totR = []; $totB = [];
    foreach (range(1, 12) as $m) {
        $totR[$m] = array_sum(array_map(fn ($x) => $x[$m] ?? 0, $tren['r']));
        $totB[$m] = array_sum(array_map(fn ($x) => $x[$m] ?? 0, $tren['b']));
    }
    $lalu = $tren_lalu ?? array_fill(1, 12, 0.0);
    $adaLalu = array_sum($lalu) > 0;
    $maxBar = max(1, max($totR), max($totB), max($lalu));
    $kumR = 0; $kumB = 0;
@endphp
<div class="ui-panel mb-4">
    <div class="hd">
        <h3>Tren realisasi vs budget {{ $tren_tahun }}</h3>
        <span class="ui-sp"></span>
        <div class="ui-legend"><span><i style="background:#3b82f6"></i>Realisasi</span><span><i style="background:var(--ui-bad)"></i>Lewat budget</span><span><i style="background:none;border-top:2px dashed #f59e0b;height:0;border-radius:0"></i>Budget</span>@if($adaLalu)<span><i style="background:#cbd5e1"></i>{{ $tren_tahun - 1 }}</span>@endif</div>
    </div>
    <div class="px-5 pt-4 pb-4">
        <div class="ui-chart">
            @foreach(range(1, 12) as $m)
                <div class="col" title="{{ $bln[$m] }}: realisasi {{ Fmt::rp($totR[$m]) }}, budget {{ Fmt::rp($totB[$m]) }}{{ $adaLalu ? ', '.($tren_tahun - 1).' '.Fmt::rp($lalu[$m]) : '' }}">
                    @if($totB[$m] > 0)<div class="bud" style="bottom: {{ $totB[$m] / $maxBar * 100 }}%"></div>@endif
                    @if($adaLalu)<div class="ly" style="height: {{ $lalu[$m] / $maxBar * 100 }}%"></div>@endif
                    <div class="bar {{ $totB[$m] > 0 && $totR[$m] > $totB[$m] ? 'over' : '' }}" style="height: {{ $totR[$m] / $maxBar * 100 }}%"></div>
                </div>
            @endforeach
        </div>
        <div class="ui-chart-x">@foreach(range(1, 12) as $m)<div>{{ $bln[$m] }}</div>@endforeach</div>
    </div>
</div>

<div class="ui-panel">
    <div class="hd"><h3>Realisasi per akun per bulan</h3><span class="ui-sp"></span><small>dalam {{ $satuanLabel }} · merah = melebihi budget bulan itu</small></div>
    @if($tren['akun']->isEmpty())
        <div class="p-5 ui-muted">Belum ada data untuk tahun {{ $tren_tahun }}.</div>
    @else
        <div class="ui-scroll">
            <table class="ui-mx">
                <thead><tr><th>Akun</th>@foreach(range(1, 12) as $m)<th>{{ $bln[$m] }}</th>@endforeach<th>Total</th></tr></thead>
                <tbody>
                    @foreach($tren['akun'] as $a)
                        <tr>
                            <td class="fw-semibold">{{ $a->name }}</td>
                            @foreach(range(1, 12) as $m)
                                @php $r = $tren['r'][$a->id][$m] ?? 0; $b = $tren['b'][$a->id][$m] ?? 0; @endphp
                                <td class="cell {{ $r ? 'clk' : '' }}" @if($r) data-detail="{{ json_encode(['akun_id' => $a->id, 'tahun' => $tren_tahun, 'bulan_dari' => $m, 'bulan_sampai' => $m]) }}" @endif>
                                    <div class="r {{ $b > 0 && $r > $b ? 'c-bad' : '' }}">{{ $r ? Fmt::unit($r, $satuan) : '–' }}</div>
                                    @if($b > 0)<div class="b">dari {{ Fmt::unit($b, $satuan) }}</div>@endif
                                </td>
                            @endforeach
                            {!! '<td class="cell">'.$cell(array_sum($tren['r'][$a->id] ?? []), array_sum($tren['b'][$a->id] ?? [])).'</td>' !!}
                        </tr>
                    @endforeach
                    <tr class="gt">
                        <td>Total</td>
                        @foreach(range(1, 12) as $m)
                            <td class="cell"><div class="r">{{ $totR[$m] ? Fmt::unit($totR[$m], $satuan) : '–' }}</div>@if($totB[$m] > 0)<div class="b">dari {{ Fmt::unit($totB[$m], $satuan) }}</div>@endif</td>
                        @endforeach
                        <td class="cell">{!! $cell(array_sum($totR), array_sum($totB)) !!}</td>
                    </tr>
                    <tr class="ak">
                        <td>Kumulatif</td>
                        @foreach(range(1, 12) as $m)
                            @php $kumR += $totR[$m]; $kumB += $totB[$m]; $p = CostReport::pct($kumR, $kumB); @endphp
                            <td class="cell"><div class="r">{{ Fmt::unit($kumR, $satuan) }}</div>@if($kumB > 0)<div class="b">{{ Fmt::pct($p) }} dari budget</div>@endif</td>
                        @endforeach
                        <td class="cell"></td>
                    </tr>
                    @if($adaLalu)
                        <tr class="ak">
                            <td>{{ $tren_tahun - 1 }}</td>
                            @foreach(range(1, 12) as $m)
                                @php $d = $lalu[$m] > 0 ? ($totR[$m] - $lalu[$m]) / $lalu[$m] * 100 : null; @endphp
                                <td class="cell"><div class="r">{{ $lalu[$m] ? Fmt::unit($lalu[$m], $satuan) : '–' }}</div>@if($d !== null && $totR[$m] > 0)<div class="b {{ $d > 0 ? 'c-bad' : 'c-ok' }}">{{ $d > 0 ? '▲' : '▼' }} {{ number_format(abs($d), 1, ',', '.') }}%</div>@endif</td>
                            @endforeach
                            <td class="cell"><div class="r">{{ Fmt::unit(array_sum($lalu), $satuan) }}</div></td>
                        </tr>
                    @endif
                </tbody>
            </table>
        </div>
    @endif
</div>
