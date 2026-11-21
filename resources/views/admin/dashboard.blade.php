@extends('layouts.admin')

@php
    use App\Services\CostReport;
    use App\Support\Fmt;
    $tr = $matrix['total_realisasi'];
    $tb = $matrix['total_budget'];
    $pct = CostReport::pct($tr, $tb);
    $st = CostReport::status($pct, $tr);
    $yoy = $realisasiLalu > 0 ? ($tr - $realisasiLalu) / $realisasiLalu * 100 : null;
    $maxBar = max(1, collect($monthly)->map(fn ($m) => max($m['realisasi'], $m['budget']))->max());
    $bulanPendek = ['', 'Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
    $laporanUrl = fn ($extra = []) => route('admin.keuangan.laporan.index', array_merge($periode->toQuery(), $extra));
@endphp

@section('title', 'Dashboard')
@section('page_title', 'Dashboard')

@section('page_actions')
    <form method="GET" class="d-flex align-items-center gap-2">
        <select name="tahun" class="form-select form-select-solid form-select-sm w-auto" onchange="this.form.submit()">
            @foreach($tahunOptions as $th)
                <option value="{{ $th }}" @selected($periode->tahun === $th)>{{ $th }}</option>
            @endforeach
        </select>
        <input type="hidden" name="bulan_dari" value="1">
        <select name="bulan_sampai" class="form-select form-select-solid form-select-sm w-auto" onchange="this.form.submit()">
            @foreach($namaBulan as $no => $nama)
                <option value="{{ $no }}" @selected($periode->bulanSampai === $no)>s/d {{ $nama }}</option>
            @endforeach
        </select>
    </form>
@endsection

@section('content')
    @if($undatedCount > 0)
        <div class="alert alert-warning d-flex align-items-center gap-3 py-3 mb-5">
            <i class="fa-solid fa-triangle-exclamation"></i>
            <div><strong>{{ number_format($undatedCount, 0, ',', '.') }}</strong> baris jurnal lama belum punya tanggal dan tidak ikut dihitung di dashboard.</div>
        </div>
    @endif

    <div class="ui-kpis mb-5">
        <div class="ui-kpi">
            <div class="l">Budget {{ $periode->label() }}</div>
            <div class="v">{{ Fmt::rpShort($tb) }}</div>
            <div class="s">Budget setahun {{ Fmt::rpShort($budgetSetahun) }}</div>
        </div>
        <div class="ui-kpi">
            <div class="l">Realisasi</div>
            <div class="v">{{ Fmt::rpShort($tr) }}</div>
            <div class="s">
                @if($yoy !== null)
                    <span class="{{ $yoy > 0 ? 'c-bad' : 'c-ok' }}">{{ $yoy > 0 ? '▲' : '▼' }} {{ number_format(abs($yoy), 1, ',', '.') }}%</span> vs periode sama {{ $periode->tahun - 1 }}
                @else
                    Belum ada data {{ $periode->tahun - 1 }} untuk dibandingkan
                @endif
            </div>
        </div>
        <div class="ui-kpi">
            <div class="l">Serapan budget</div>
            <div class="v c-{{ $st === 'none' ? 'ok' : $st }}">{{ $pct === null ? '–' : number_format($pct, 1, ',', '.').'%' }}</div>
            <div class="ui-bar"><i style="width:{{ min(100, $pct ?? 0) }}%;background:{{ Fmt::color($st) }}"></i></div>
            <div class="s">Sisa {{ Fmt::rpShort(max(0, $tb - $tr)) }}</div>
        </div>
        <a class="ui-kpi text-reset" href="{{ $laporanUrl(['tab' => 'lewat']) }}">
            <div class="l">Pos melebihi budget</div>
            <div class="v {{ $overCount ? 'c-bad' : 'c-ok' }}">{{ $overCount }} pos</div>
            <div class="s">{{ $warnCount }} pos lain sudah di atas 80%</div>
        </a>
    </div>

    <div class="row g-4 mb-5">
        <div class="col-xl-7">
            <div class="ui-panel h-100">
                <div class="hd">
                    <h3>Tren realisasi vs budget {{ $periode->tahun }}</h3>
                    <span class="ui-sp"></span>
                    <div class="ui-legend"><span><i style="background:#3b82f6"></i>Realisasi</span><span><i style="background:var(--ui-bad)"></i>Lewat budget</span><span><i style="background:none;border-top:2px dashed #f59e0b;height:0;border-radius:0"></i>Budget</span></div>
                </div>
                <div class="px-5 pt-4 pb-4">
                    <div class="ui-chart">
                        @foreach($monthly as $m => $v)
                            <div class="col" title="{{ $bulanPendek[$m] }}: realisasi {{ Fmt::rp($v['realisasi']) }}, budget {{ Fmt::rp($v['budget']) }}">
                                @if($v['budget'] > 0)
                                    <div class="bud" style="bottom: {{ $v['budget'] / $maxBar * 100 }}%"></div>
                                @endif
                                <div class="bar {{ $v['budget'] > 0 && $v['realisasi'] > $v['budget'] ? 'over' : '' }}" style="height: {{ $v['realisasi'] / $maxBar * 100 }}%; {{ $m > $periode->bulanSampai ? 'opacity:.35' : '' }}"></div>
                            </div>
                        @endforeach
                    </div>
                    <div class="ui-chart-x">
                        @foreach($monthly as $m => $v)<div>{{ $bulanPendek[$m] }}</div>@endforeach
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-5">
            <div class="ui-panel h-100">
                <div class="hd">
                    <h3><i class="fa-solid fa-triangle-exclamation c-warn me-1"></i> Melebihi / mendekati budget</h3>
                    <span class="ui-sp"></span>
                    <a href="{{ $laporanUrl(['tab' => 'lewat']) }}" class="fs-7">Lihat semua →</a>
                </div>
                @forelse($positions->filter(fn ($p) => in_array(CostReport::status($p->pct, $p->realisasi), ['bad', 'warn']))->take(7) as $p)
                    @php $s = CostReport::status($p->pct, $p->realisasi); @endphp
                    <div class="ui-row">
                        <div class="n">
                            <b>{{ $p->akun }}</b>
                            <small>{{ $p->division }} · {{ Fmt::rpShort($p->realisasi) }} dari {{ $p->budget > 0 ? Fmt::rpShort($p->budget) : 'tanpa budget' }}</small>
                            <div class="ui-bar"><i style="width:{{ min(100, $p->pct ?? 100) }}%;background:{{ Fmt::color($s) }}"></i></div>
                        </div>
                        <span class="badge b-{{ $s }}">{{ $p->pct === null ? 'Tanpa budget' : Fmt::pct($p->pct) }}</span>
                    </div>
                @empty
                    <div class="ui-row ui-muted">Belum ada pos yang mendekati atau melebihi budget. 👍</div>
                @endforelse
            </div>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-xl-6">
            <div class="ui-panel h-100">
                <div class="hd"><h3>Serapan per divisi</h3><span class="ui-sp"></span><small>{{ $periode->label() }}</small></div>
                @forelse($matrix['divisions'] as $d)
                    @php
                        $r = $matrix['div_totals'][$d->id]['r'] ?? 0;
                        $b = $matrix['div_totals'][$d->id]['b'] ?? 0;
                        $p = CostReport::pct($r, $b);
                        $s = CostReport::status($p, $r);
                    @endphp
                    <a class="ui-row text-reset" href="{{ $laporanUrl(['division_ids' => [$d->id]]) }}">
                        <div class="n">{{ $d->name }}<div class="ui-bar"><i style="width:{{ min(100, $p ?? 0) }}%;background:{{ Fmt::color($s) }}"></i></div></div>
                        <span class="num" style="width:120px">{{ Fmt::rpShort($r) }}</span>
                        <span class="badge b-{{ $s === 'none' ? 'gray' : $s }}" style="min-width:56px">{{ Fmt::pct($p) }}</span>
                    </a>
                @empty
                    <div class="ui-row ui-muted">Belum ada data untuk periode ini.</div>
                @endforelse
            </div>
        </div>
        <div class="col-xl-6">
            <div class="ui-panel h-100">
                <div class="hd"><h3>Import jurnal terakhir</h3><span class="ui-sp"></span><a href="{{ route('admin.keuangan.jurnal-umum.index', ['tab' => 'riwayat']) }}" class="fs-7">Riwayat import →</a></div>
                @php $statusBadge = ['berhasil' => 'b-ok', 'gagal' => 'b-bad', 'ditolak' => 'b-warn', 'dibatalkan' => 'b-gray']; @endphp
                @forelse($batches as $b)
                    <div class="ui-row">
                        <span class="badge {{ $statusBadge[$b->status] ?? 'b-gray' }}" style="min-width:84px">{{ ucfirst($b->status) }}</span>
                        <div class="n">{{ $b->file_name }}<small>{{ $b->kode() }} · {{ $b->user?->name ?? '-' }} · {{ $b->rows_count }} baris{{ $b->rows_count ? ' · '.Fmt::rpShort($b->total_kredit) : '' }}</small></div>
                        <small class="ui-muted text-nowrap">{{ $b->created_at->format('d/m H:i') }}</small>
                    </div>
                @empty
                    <div class="ui-row ui-muted">Belum ada import. <a href="{{ route('admin.keuangan.jurnal-umum.index', ['tab' => 'import']) }}" class="ms-1">Import jurnal sekarang</a></div>
                @endforelse
            </div>
        </div>
    </div>
@endsection
