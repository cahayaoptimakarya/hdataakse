@php use App\Services\CostReport; use App\Support\Fmt; @endphp
@php
    $divs = $matrix['divisions'];
    $tr = $matrix['total_realisasi'];
    $tb = $matrix['total_budget'];
    $overCount = $positions->filter(fn ($p) => CostReport::status($p->pct, $p->realisasi) === 'bad')->count();
@endphp

<div class="ui-kpis mb-4">
    <div class="ui-kpi"><div class="l">Realisasi {{ $periode->label() }}</div><div class="v">{{ Fmt::rpShort($tr) }}</div></div>
    <div class="ui-kpi"><div class="l">Budget</div><div class="v">{{ Fmt::rpShort($tb) }}</div><div class="s">Sisa {{ Fmt::rpShort(max(0, $tb - $tr)) }}</div></div>
    @php $p = CostReport::pct($tr, $tb); $s = CostReport::status($p, $tr); @endphp
    <div class="ui-kpi"><div class="l">Serapan</div><div class="v c-{{ $s === 'none' ? 'ok' : $s }}">{{ $p === null ? '–' : number_format($p, 1, ',', '.').'%' }}</div>
        <div class="ui-bar"><i style="width:{{ min(100, $p ?? 0) }}%;background:{{ Fmt::color($s) }}"></i></div></div>
    <a class="ui-kpi text-reset" href="{{ $tabUrl('lewat') }}"><div class="l">Pos melebihi budget</div><div class="v {{ $overCount ? 'c-bad' : 'c-ok' }}">{{ $overCount }} pos</div><div class="s">Lihat daftar →</div></a>
</div>

<div class="ui-panel">
    <div class="hd">
        <h3>Realisasi vs budget per akun dan divisi</h3>
        <span class="ui-sp"></span>
        <div class="ui-legend"><span><i class="bg-ok"></i>&lt; 80%</span><span><i class="bg-warn"></i>80–100%</span><span><i class="bg-bad"></i>&gt; 100% / tanpa budget</span></div>
        <small class="ms-3">dalam {{ $satuanLabel }} · klik angka untuk detail</small>
    </div>
    @if($matrix['akun']->isEmpty())
        <div class="p-5 ui-muted">Belum ada realisasi atau budget untuk periode dan filter ini.</div>
    @else
        <div class="ui-scroll">
            <table class="ui-mx">
                <thead>
                    <tr>
                        <th>Akun / Kategori</th>
                        @foreach($divs as $d)<th>{{ $d->name }}</th>@endforeach
                        <th>Total</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($matrix['akun'] as $a)
                        @php $at = $matrix['akun_totals'][$a->id] ?? ['r' => 0, 'b' => 0]; @endphp
                        <tr class="ak">
                            <td>{{ $a->name }}</td>
                            @foreach($divs as $d)
                                <td class="cell clk" data-detail='@json(['akun_id' => $a->id, 'division_id' => $d->id])'>{!! $cell($matrix['cells'][$a->id]['_'][$d->id] ?? 0, $matrix['budget'][$a->id][$d->id] ?? 0) !!}</td>
                            @endforeach
                            <td class="cell clk" data-detail='@json(['akun_id' => $a->id])'>{!! $cell($at['r'], $at['b']) !!}</td>
                        </tr>
                        @foreach($a->subs as $sub)
                            <tr class="sub">
                                <td><button type="button" class="lnk" data-detail='@json(['sub_akun_id' => $sub->id])'>{{ $sub->name }}</button></td>
                                @foreach($divs as $d)
                                    @php $v = $matrix['cells'][$a->id][$sub->id][$d->id] ?? 0; @endphp
                                    <td class="cell {{ $v ? 'clk' : '' }}" @if($v) data-detail='@json(['sub_akun_id' => $sub->id, 'division_id' => $d->id])' @endif>{!! $cell($v, null) !!}</td>
                                @endforeach
                                <td class="cell">{!! $cell(array_sum($matrix['cells'][$a->id][$sub->id] ?? []), null) !!}</td>
                            </tr>
                        @endforeach
                    @endforeach
                    <tr class="gt">
                        <td>Total</td>
                        @foreach($divs as $d)
                            @php $dt = $matrix['div_totals'][$d->id] ?? ['r' => 0, 'b' => 0]; @endphp
                            <td class="cell">{!! $cell($dt['r'], $dt['b']) !!}</td>
                        @endforeach
                        <td class="cell">{!! $cell($tr, $tb) !!}</td>
                    </tr>
                </tbody>
            </table>
        </div>
    @endif
</div>
