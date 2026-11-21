@extends('layouts.admin')

@php
    use App\Services\CostReport;
    use App\Support\Fmt;

    $satuanLabel = ['penuh' => 'Rupiah', 'ribu' => 'ribu Rupiah', 'juta' => 'juta Rupiah'][$satuan];
    $baseQuery = array_merge($periode->toQuery(), array_filter([
        'division_ids' => $selected_division_ids ?: null,
        'akun_ids' => $selected_akun_ids ?: null,
        'satuan' => $satuan !== 'juta' ? $satuan : null,
    ]));
    $tabUrl = fn ($t) => route('admin.keuangan.laporan.index', array_merge($baseQuery, ['tab' => $t]));

    // Satu sel matriks: realisasi, lalu "dari budget · %", lalu bar berwarna.
    $cell = function (float $r, ?float $b = 0) use ($satuan) {
        // $b null = baris tanpa konsep budget (kategori), jadi tidak ditandai "tanpa budget".
        $html = '<div class="r">'.($r == 0 && $b == 0 ? '–' : e(Fmt::unit($r, $satuan))).'</div>';
        if ($b > 0) {
            $p = CostReport::pct($r, $b);
            $s = CostReport::status($p, $r);
            $html .= '<div class="b">dari '.e(Fmt::unit($b, $satuan)).' · <span class="c-'.$s.'">'.e(Fmt::pct($p)).'</span></div>'
                .'<div class="ui-bar"><i style="width:'.min(100, $p).'%;background:'.Fmt::color($s).'"></i></div>';
        } elseif ($r > 0 && $b !== null) {
            $html .= '<div class="b c-bad">tanpa budget</div>';
        }
        return $html;
    };
@endphp

@section('title', 'Laporan Realisasi')
@section('page_title', 'Laporan realisasi vs budget')

@section('page_actions')
    @if($tab === 'toko')
        <a href="{{ route('admin.keuangan.laporan.export-divisi', request()->query()) }}" class="btn btn-light btn-sm"><i class="fa-solid fa-file-excel me-1"></i> Export Excel</a>
    @else
        <a href="{{ route('admin.keuangan.laporan.export', request()->query()) }}" class="btn btn-light btn-sm"><i class="fa-solid fa-file-excel me-1"></i> Export Excel</a>
    @endif
@endsection

@section('content')
    <form method="GET" class="ui-filter mb-4" id="laporan_filter">
        <input type="hidden" name="tab" value="{{ $tab }}">
        <div class="row g-3 align-items-end">
            @include('admin.keuangan.laporan-jurnal-umum._periode')
            <div class="col-12 col-md-6 {{ $tab === 'toko' ? 'col-lg-3' : 'col-lg' }}" style="min-width:180px">
                <label class="form-label">Divisi</label>
                <select name="division_ids[]" class="form-select form-select-solid form-select-sm" multiple data-control="select2" data-placeholder="Semua divisi">
                    @foreach($division_options as $div)
                        <option value="{{ $div->id }}" @selected(in_array($div->id, $selected_division_ids, true))>{{ $div->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-12 col-md-6 {{ $tab === 'toko' ? 'col-lg-3' : 'col-lg' }}" style="min-width:180px">
                <label class="form-label">Akun</label>
                <select name="akun_ids[]" class="form-select form-select-solid form-select-sm" multiple data-control="select2" data-placeholder="Semua akun">
                    @foreach($akun_options as $akun)
                        <option value="{{ $akun->id }}" @selected(in_array($akun->id, $selected_akun_ids, true))>{{ $akun->name }}</option>
                    @endforeach
                </select>
            </div>
            @if($tab === 'toko')
                <div class="col-12 col-md-6 col-lg-3">
                    <label class="form-label">Toko</label>
                    <select name="sub_division_ids[]" class="form-select form-select-solid form-select-sm" multiple data-control="select2" data-placeholder="Semua toko">
                        @foreach(collect($sub_division_options)->groupBy('division_name') as $divisionName => $subs)
                            <optgroup label="{{ $divisionName }}">
                                @foreach($subs as $sub)
                                    <option value="{{ $sub->id }}" @selected(in_array($sub->id, $selected_sub_division_ids_divisi, true))>{{ $sub->name }}</option>
                                @endforeach
                            </optgroup>
                        @endforeach
                    </select>
                </div>
                <div class="col-12 col-md-6 col-lg-3">
                    <label class="form-label">Kategori</label>
                    <select name="sub_akun_ids[]" class="form-select form-select-solid form-select-sm" multiple data-control="select2" data-placeholder="Semua kategori">
                        @foreach(collect($sub_akun_options_divisi)->groupBy('akun_name') as $akunName => $subs)
                            <optgroup label="{{ $akunName }}">
                                @foreach($subs as $sub)
                                    <option value="{{ $sub->id }}" @selected(in_array($sub->id, $selected_sub_akun_ids_divisi, true))>{{ $sub->name }}</option>
                                @endforeach
                            </optgroup>
                        @endforeach
                    </select>
                </div>
            @endif
            <div class="col-6 col-md-4 col-lg-auto">
                <label class="form-label">Satuan</label>
                <select name="satuan" class="form-select form-select-solid form-select-sm">
                    <option value="juta" @selected($satuan === 'juta')>Juta</option>
                    <option value="ribu" @selected($satuan === 'ribu')>Ribu</option>
                    <option value="penuh" @selected($satuan === 'penuh')>Rupiah penuh</option>
                </select>
            </div>
            <div class="col-auto ms-auto d-flex gap-2">
                <a href="{{ route('admin.keuangan.laporan.index', ['tab' => $tab]) }}" class="btn btn-light btn-sm">Reset</a>
                <button type="submit" class="btn btn-primary btn-sm">Terapkan</button>
            </div>
        </div>
    </form>

    @if($undated_count > 0)
        <div class="alert alert-warning d-flex align-items-center gap-3 py-3 mb-4">
            <i class="fa-solid fa-triangle-exclamation"></i>
            <div><strong>{{ number_format($undated_count, 0, ',', '.') }}</strong> baris jurnal lama belum punya tanggal dan tidak ikut dihitung di laporan periode ini. Pilih "Semua periode" untuk melihatnya.</div>
        </div>
    @endif

    <ul class="nav nav-tabs mb-4">
        <li class="nav-item"><a class="nav-link @if($tab === 'ringkasan') active @endif" href="{{ $tabUrl('ringkasan') }}">Ringkasan akun × divisi</a></li>
        <li class="nav-item"><a class="nav-link @if($tab === 'toko') active @endif" href="{{ $tabUrl('toko') }}">Per toko</a></li>
        <li class="nav-item"><a class="nav-link @if($tab === 'tren') active @endif" href="{{ $tabUrl('tren') }}">Tren bulanan</a></li>
        <li class="nav-item"><a class="nav-link @if($tab === 'lewat') active @endif" href="{{ $tabUrl('lewat') }}">Pos melebihi budget</a></li>
    </ul>

    @include('admin.keuangan.laporan-jurnal-umum._'.$tab)

    {{-- Panel detail (drill-down) --}}
    <div class="ui-overlay" id="detail_overlay"></div>
    <aside class="ui-drawer" id="detail_drawer" aria-hidden="true">
        <div class="dh d-flex align-items-start gap-3">
            <div class="flex-grow-1 min-w-0">
                <div class="fw-bold fs-5" id="dd_title">Detail</div>
                <div class="ui-muted fs-7" id="dd_scope"></div>
            </div>
            <button type="button" class="btn btn-sm btn-light" id="dd_close" aria-label="Tutup"><i class="fa-solid fa-xmark"></i></button>
        </div>
        <div class="db" id="dd_body"></div>
    </aside>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('.periode-tahun').forEach((sel) => {
        sel.addEventListener('change', () => {
            sel.closest('form')?.querySelectorAll('select[name="bulan_dari"], select[name="bulan_sampai"]').forEach((m) => { m.disabled = sel.value === 'semua'; });
        });
    });

    // ---------- Panel detail ----------
    const detailUrl = @json(route('admin.keuangan.laporan.detail'));
    const periodeQuery = @json($periode->toQuery());
    const drawer = document.getElementById('detail_drawer');
    const overlay = document.getElementById('detail_overlay');
    const body = document.getElementById('dd_body');
    const esc = (s) => String(s ?? '').replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
    const rp = (v) => 'Rp ' + new Intl.NumberFormat('id-ID', { maximumFractionDigits: 0 }).format(Number(v || 0));
    const tgl = (v) => { const m = String(v || '').match(/^(\d{4})-(\d{2})-(\d{2})/); return m ? `${m[3]}/${m[2]}/${m[1]}` : 'Tanpa tanggal'; };
    const bulan = ['', 'Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
    const statusOf = (pct) => pct === null ? 'none' : (pct > 100 ? 'bad' : (pct >= 80 ? 'warn' : 'ok'));
    const close = () => { drawer.classList.remove('open'); overlay.classList.remove('open'); };
    overlay.addEventListener('click', close);
    document.getElementById('dd_close').addEventListener('click', close);
    document.addEventListener('keydown', (e) => { if (e.key === 'Escape') close(); });

    const listBlock = (title, items, label) => {
        if (!items.length) return '';
        const max = Math.max(...items.map((i) => Number(i.total)));
        return `<div class="px-5 pt-4"><div class="fw-semibold fs-7 text-uppercase ui-muted mb-2">${title}</div>` + items.slice(0, 12).map((i) => `
            <div class="d-flex align-items-center gap-3 py-1">
                <div class="flex-grow-1 min-w-0"><div class="text-truncate">${esc(label(i))}</div>
                <div class="ui-bar"><i style="width:${max > 0 ? (i.total / max * 100) : 0}%;background:#93c5fd"></i></div></div>
                <div class="text-end text-nowrap" style="min-width:120px">${rp(i.total)}<div class="ui-muted fs-8">${i.jumlah} transaksi</div></div>
            </div>`).join('') + (items.length > 12 ? `<div class="ui-muted fs-8 pt-1">+${items.length - 12} lainnya</div>` : '') + `</div>`;
    };

    const open = async (params) => {
        document.getElementById('dd_title').textContent = 'Memuat…';
        document.getElementById('dd_scope').textContent = '';
        body.innerHTML = '<div class="p-5 ui-muted">Memuat data…</div>';
        drawer.classList.add('open'); overlay.classList.add('open');
        try {
            const qs = new URLSearchParams({ ...periodeQuery, ...params });
            const res = await fetch(`${detailUrl}?${qs}`, { headers: { Accept: 'application/json' } });
            if (!res.ok) throw new Error('Gagal memuat detail.');
            const d = await res.json();
            document.getElementById('dd_title').textContent = d.title;
            document.getElementById('dd_scope').textContent = d.scope;
            const st = statusOf(d.pct);
            let html = `<div class="px-5 pt-4 d-flex gap-6 flex-wrap">
                <div><div class="ui-muted fs-8">Realisasi</div><div class="fw-bold fs-4">${rp(d.total)}</div></div>
                ${d.budget ? `<div><div class="ui-muted fs-8">Budget</div><div class="fw-bold fs-4">${rp(d.budget)}</div></div>
                <div><div class="ui-muted fs-8">Serapan</div><div class="fw-bold fs-4 c-${st}">${new Intl.NumberFormat('id-ID', { maximumFractionDigits: 1 }).format(d.pct)}%</div></div>` : ''}
                <div><div class="ui-muted fs-8">Transaksi</div><div class="fw-bold fs-4">${d.jumlah}</div></div>
            </div>`;
            const months = Object.entries(d.per_bulan);
            if (months.length > 1) {
                const max = Math.max(...months.map(([, v]) => v));
                html += `<div class="px-5 pt-4"><div class="fw-semibold fs-7 text-uppercase ui-muted mb-1">Per bulan</div><div class="ui-mini">` +
                    months.map(([k, v]) => `<div title="${k}: ${rp(v)}"><i style="height:${max > 0 ? v / max * 100 : 0}%"></i>${bulan[Number(k.slice(5, 7))]}</div>`).join('') + `</div></div>`;
            }
            html += listBlock('Per toko', d.per_toko, (i) => `${i.name} · ${i.division}`);
            if (d.per_kategori.length > 1) html += listBlock('Per kategori', d.per_kategori, (i) => `${i.name} · ${i.akun}`);
            html += `<div class="px-5 pt-5 pb-2 d-flex align-items-center"><div class="fw-semibold fs-7 text-uppercase ui-muted">Transaksi terbaru</div><span class="ui-sp"></span>
                <a href="${d.jurnal_url}" class="fs-7">Buka di Jurnal Umum →</a></div>
                <table class="table table-sm align-middle mb-5 mx-0"><thead><tr><th class="ps-5">Tanggal</th><th>Keterangan</th><th class="text-end pe-5">Kredit</th></tr></thead><tbody>` +
                (d.rows.length ? d.rows.map((r) => `<tr><td class="ps-5 text-nowrap">${tgl(r.tanggal)}</td>
                    <td><div>${esc(r.keterangan)}</div><div class="ui-muted fs-8">${esc(r.toko)} · ${esc(r.kategori)} · ${esc(r.akun)}</div></td>
                    <td class="text-end pe-5 text-nowrap">${rp(r.kredit)}</td></tr>`).join('')
                    : '<tr><td colspan="3" class="ps-5 ui-muted">Tidak ada transaksi.</td></tr>') +
                `</tbody></table>` + (d.jumlah > d.rows.length ? `<div class="px-5 pb-5 ui-muted fs-8">Menampilkan ${d.rows.length} dari ${d.jumlah} transaksi.</div>` : '');
            body.innerHTML = html;
        } catch (err) {
            body.innerHTML = `<div class="p-5 c-bad">${esc(err.message)}</div>`;
        }
    };

    document.addEventListener('click', (e) => {
        if (e.target.closest('.no-detail')) return;
        const el = e.target.closest('[data-detail]');
        if (!el) return;
        e.preventDefault();
        open(JSON.parse(el.dataset.detail));
    });
});
</script>
@endpush
