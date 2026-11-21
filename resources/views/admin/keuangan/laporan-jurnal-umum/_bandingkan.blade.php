@php use App\Services\CostReport; use App\Support\Fmt; @endphp
@php
    $n = count($periodes);
    $rows = $banding['rows'];
    $tot = $banding['totals'];
    $maxTot = max(1, max($tot['r']), max($tot['b']));
    $judulKelompok = ['akun' => 'Akun / kategori', 'divisi' => 'Divisi', 'toko' => 'Toko'][$kelompok];
    // Kenaikan biaya ditandai merah, penurunan hijau.
    $delta = function (float $dasar, float $nilai) {
        $c = CostReport::change($dasar, $nilai);
        if ($c === null) {
            return $nilai > 0 ? '<div class="b">baru</div>' : '';
        }
        if (abs($c) < 0.05) {
            return '<div class="b">sama</div>';
        }
        return '<div class="b"><span class="'.($c > 0 ? 'c-bad' : 'c-ok').'">'.($c > 0 ? '▲' : '▼').' '.number_format(abs($c), 1, ',', '.').'%</span></div>';
    };
    $detail = fn ($row, $p) => json_encode(array_filter([
        'akun_id' => $row->akun_id ?? null, 'sub_akun_id' => $row->sub_akun_id ?? null,
        'division_id' => $row->division_id ?? null, 'sub_divisi_id' => $row->sub_divisi_id ?? null,
    ]) + $p->toQuery());
@endphp

<div class="ui-panel mb-4">
    <div class="hd">
        <h3>Total realisasi per periode</h3>
        <span class="ui-sp"></span>
        <div class="ui-legend"><span><i style="background:#3b82f6"></i>Realisasi</span><span><i style="background:#e2e8f0"></i>Budget</span></div>
    </div>
    <div class="px-5 py-4 ui-cmp">
        @foreach($periodes as $i => $p)
            @php $pct = CostReport::pct($tot['r'][$i], $tot['b'][$i]); @endphp
            <div class="row-c">
                <div class="lbl">
                    <div class="fw-semibold">{{ $p->label() }}</div>
                    <div class="ui-muted fs-8">{{ $i === 0 ? 'Periode utama' : 'Pembanding '.$i }}</div>
                </div>
                <div class="bars">
                    <div class="bg" style="width: {{ $tot['b'][$i] / $maxTot * 100 }}%"></div>
                    <div class="fg {{ $tot['b'][$i] > 0 && $tot['r'][$i] > $tot['b'][$i] ? 'over' : '' }}" style="width: {{ $tot['r'][$i] / $maxTot * 100 }}%"></div>
                </div>
                <div class="val">
                    <div class="fw-bold">{{ Fmt::rpShort($tot['r'][$i]) }}</div>
                    <div class="fs-8 ui-muted">
                        @if($tot['b'][$i] > 0){{ Fmt::pct($pct) }} dari budget {{ Fmt::rpShort($tot['b'][$i]) }}@else tanpa budget @endif
                    </div>
                </div>
                <div class="chg">
                    @if($i > 0)
                        @php $c = CostReport::change($tot['r'][0], $tot['r'][$i]); @endphp
                        @if($c !== null)
                            <span class="{{ $c > 0 ? 'c-bad' : 'c-ok' }} fw-semibold">{{ $c > 0 ? '▲' : '▼' }} {{ number_format(abs($c), 1, ',', '.') }}%</span>
                            <div class="fs-8 ui-muted">{{ $c > 0 ? '+' : '−' }}{{ Fmt::rpShort(abs($tot['r'][$i] - $tot['r'][0])) }} vs utama</div>
                        @endif
                    @endif
                </div>
            </div>
        @endforeach
    </div>
</div>

<div class="ui-panel">
    <div class="hd">
        <h3>Perbandingan per {{ strtolower($judulKelompok) }}</h3>
        <span class="ui-sp"></span>
        <small>dalam {{ $satuanLabel }} · ▲▼ = perubahan dibanding periode utama · klik angka untuk detail</small>
    </div>
    @if($rows->isEmpty())
        <div class="p-5 ui-muted">Belum ada realisasi atau budget untuk periode dan filter ini.</div>
    @else
        <div class="ui-scroll">
            <table class="ui-mx">
                <thead>
                    <tr>
                        <th>{{ $judulKelompok }}</th>
                        @foreach($periodes as $i => $p)
                            <th>{{ $p->label() }}@if($i === 0)<div class="fw-normal text-lowercase" style="letter-spacing:0">utama</div>@endif</th>
                        @endforeach
                        @if($n > 1)<th>Selisih {{ $n > 2 ? 'terakhir' : '' }} vs utama</th>@endif
                    </tr>
                </thead>
                <tbody>
                    @foreach($rows as $row)
                        <tr class="{{ $row->level === 1 ? 'sub' : ($kelompok === 'akun' ? 'ak' : '') }}">
                            <td>
                                <div class="{{ $row->level === 0 ? 'fw-semibold' : '' }}">{{ $row->name }}</div>
                                @if($kelompok === 'toko' && $row->sub)<div class="ui-muted fs-8">{{ $row->sub }}</div>@endif
                            </td>
                            @foreach($periodes as $i => $p)
                                @php $r = $row->r[$i]; $b = $row->b[$i]; @endphp
                                <td class="cell {{ $r ? 'clk' : '' }}" @if($r) data-detail="{{ $detail($row, $p) }}" @endif>
                                    <div class="r">{{ $r ? Fmt::unit($r, $satuan) : '–' }}</div>
                                    @if($b)<div class="b">{{ Fmt::pct(CostReport::pct($r, $b)) }} dari {{ Fmt::unit($b, $satuan) }}</div>@endif
                                    @if($i > 0){!! $delta($row->r[0], $r) !!}@endif
                                </td>
                            @endforeach
                            @if($n > 1)
                                @php $d = $row->r[$n - 1] - $row->r[0]; @endphp
                                <td class="cell"><div class="r {{ $d > 0 ? 'c-bad' : ($d < 0 ? 'c-ok' : '') }}">{{ $d == 0 ? '–' : ($d > 0 ? '+' : '−').Fmt::unit(abs($d), $satuan) }}</div></td>
                            @endif
                        </tr>
                    @endforeach
                    <tr class="gt">
                        <td>Total</td>
                        @foreach($periodes as $i => $p)
                            <td class="cell">
                                <div class="r">{{ Fmt::unit($tot['r'][$i], $satuan) }}</div>
                                @if($tot['b'][$i] > 0)<div class="b">{{ Fmt::pct(CostReport::pct($tot['r'][$i], $tot['b'][$i])) }} dari {{ Fmt::unit($tot['b'][$i], $satuan) }}</div>@endif
                                @if($i > 0){!! $delta($tot['r'][0], $tot['r'][$i]) !!}@endif
                            </td>
                        @endforeach
                        @if($n > 1)
                            @php $d = $tot['r'][$n - 1] - $tot['r'][0]; @endphp
                            <td class="cell"><div class="r">{{ $d == 0 ? '–' : ($d > 0 ? '+' : '−').Fmt::unit(abs($d), $satuan) }}</div></td>
                        @endif
                    </tr>
                </tbody>
            </table>
        </div>
    @endif
</div>
