@extends('layouts.admin')

@section('title', 'Jurnal Umum')
@section('page_title', 'Jurnal Umum')

@section('content')
@php
    $rp = fn ($v) => 'Rp '.number_format((float) $v, 0, ',', '.');
    $statusBadge = [
        'berhasil' => ['badge-light-success', 'Berhasil'],
        'gagal' => ['badge-light-danger', 'Gagal'],
        'ditolak' => ['badge-light-warning', 'Ditolak'],
        'dibatalkan' => ['badge-light', 'Dibatalkan'],
    ];
    $tabUrl = fn ($t) => route('admin.keuangan.jurnal-umum.index', ['tab' => $t]);
@endphp
<div class="card">
    <div class="card-header border-0 pt-6">
        <div class="card-title">
            <h2 class="fw-bolder mb-0">Jurnal Umum</h2>
        </div>
        <div class="card-toolbar gap-2">
            <a href="{{ $tabUrl('import') }}" class="btn btn-primary"><i class="fa-solid fa-file-import me-1"></i> Import Jurnal</a>
        </div>
    </div>
    <div class="card-body pt-0">
        <ul class="nav nav-tabs nav-line-tabs mb-6 fs-6">
            <li class="nav-item"><a class="nav-link @if($tab === 'transaksi') active @endif" href="{{ $tabUrl('transaksi') }}">Daftar Transaksi</a></li>
            <li class="nav-item"><a class="nav-link @if($tab === 'riwayat') active @endif" href="{{ $tabUrl('riwayat') }}">Riwayat Import</a></li>
            <li class="nav-item"><a class="nav-link @if($tab === 'import') active @endif" href="{{ $tabUrl('import') }}">Import Excel</a></li>
        </ul>

        @if($tab === 'transaksi')
            <form method="GET" class="ui-filter mb-5">
                <input type="hidden" name="tab" value="transaksi">
                <div class="row g-3 align-items-end">
                    <div class="col-12 col-md-6 col-lg-4">
                        <label class="form-label">Cari</label>
                        <input type="text" name="q" value="{{ $filters['q'] }}" class="form-control form-control-solid form-control-sm" placeholder="Keterangan, toko, kategori...">
                    </div>
                    <div class="col-6 col-lg-2">
                        <label class="form-label">Dari tanggal</label>
                        <input type="date" name="tanggal_dari" value="{{ $filters['tanggal_dari'] }}" class="form-control form-control-solid form-control-sm">
                    </div>
                    <div class="col-6 col-lg-2">
                        <label class="form-label">Sampai tanggal</label>
                        <input type="date" name="tanggal_sampai" value="{{ $filters['tanggal_sampai'] }}" class="form-control form-control-solid form-control-sm">
                    </div>
                    <div class="col-6 col-lg-2">
                        <label class="form-label">Divisi</label>
                        <select name="division_id" class="form-select form-select-solid form-select-sm">
                            <option value="">Semua</option>
                            @foreach($divisionOptions as $o)
                                <option value="{{ $o->id }}" @selected($filters['division_id'] === $o->id)>{{ $o->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-6 col-lg-2">
                        <label class="form-label">Toko (Sub Divisi)</label>
                        <select name="sub_divisi_id" class="form-select form-select-solid form-select-sm" data-control="select2" data-allow-clear="true" data-placeholder="Semua">
                            <option value=""></option>
                            @foreach($subDivisiOptions->groupBy('division_name') as $group => $items)
                                <optgroup label="{{ $group }}">
                                    @foreach($items as $o)
                                        <option value="{{ $o->id }}" @selected($filters['sub_divisi_id'] === $o->id)>{{ $o->name }}</option>
                                    @endforeach
                                </optgroup>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-6 col-lg-2">
                        <label class="form-label">Akun</label>
                        <select name="akun_id" class="form-select form-select-solid form-select-sm">
                            <option value="">Semua</option>
                            @foreach($akunOptions as $o)
                                <option value="{{ $o->id }}" @selected($filters['akun_id'] === $o->id)>{{ $o->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-6 col-lg-3">
                        <label class="form-label">Kategori (Sub Akun)</label>
                        <select name="sub_akun_id" class="form-select form-select-solid form-select-sm" data-control="select2" data-allow-clear="true" data-placeholder="Semua">
                            <option value=""></option>
                            @foreach($subAkunOptions->groupBy('akun_name') as $group => $items)
                                <optgroup label="{{ $group }}">
                                    @foreach($items as $o)
                                        <option value="{{ $o->id }}" @selected($filters['sub_akun_id'] === $o->id)>{{ $o->name }}</option>
                                    @endforeach
                                </optgroup>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-6 col-lg-3">
                        <label class="form-label">Batch import</label>
                        <select name="batch_id" class="form-select form-select-solid form-select-sm">
                            <option value="">Semua</option>
                            @foreach($batchOptions as $o)
                                <option value="{{ $o->id }}" @selected($filters['batch_id'] === $o->id)>{{ $o->kode() }} · {{ \Illuminate\Support\Str::limit($o->file_name, 22) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-6 col-lg-2">
                        <label class="form-label">Per halaman</label>
                        <select name="per_page" class="form-select form-select-solid form-select-sm">
                            @foreach([25, 50, 100] as $n)
                                <option value="{{ $n }}" @selected($perPage === $n)>{{ $n }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-12 d-flex flex-wrap gap-2 align-items-center">
                        <label class="form-check form-check-sm form-check-custom form-check-solid me-2">
                            <input class="form-check-input" type="checkbox" name="tanpa_tanggal" value="1" @checked($filters['tanpa_tanggal'])>
                            <span class="form-check-label">Hanya tanpa tanggal</span>
                        </label>
                        <a href="{{ $tabUrl('transaksi') }}" class="btn btn-light btn-sm ms-auto">Reset</a>
                        <button type="submit" class="btn btn-primary btn-sm">Terapkan</button>
                    </div>
                </div>
            </form>

            <div class="d-flex flex-wrap gap-6 gap-md-8 px-4 py-3 mb-4 border rounded bg-white">
                <div><div class="text-muted fs-7">Jumlah transaksi</div><div class="fw-bolder fs-4">{{ number_format($summary->jumlah ?? 0, 0, ',', '.') }}</div></div>
                <div><div class="text-muted fs-7">Total debet</div><div class="fw-bolder fs-4">{{ $rp($summary->total_debet ?? 0) }}</div></div>
                <div><div class="text-muted fs-7">Total kredit (biaya)</div><div class="fw-bolder fs-4">{{ $rp($summary->total_kredit ?? 0) }}</div></div>
                <div><div class="text-muted fs-7">Toko terlibat</div><div class="fw-bolder fs-4">{{ number_format($summary->jumlah_toko ?? 0, 0, ',', '.') }}</div></div>
            </div>

            <div class="table-responsive">
                <table class="table table-row-dashed table-striped align-middle fs-6 gy-3 ui-stack">
                    <thead>
                        <tr class="text-start text-gray-500 fw-bolder fs-7 text-uppercase">
                            <th class="ps-3">Tanggal</th>
                            <th>Batch</th>
                            <th>Keterangan</th>
                            <th>Toko · Divisi</th>
                            <th>Kategori · Akun</th>
                            <th class="text-end">Debet</th>
                            <th class="text-end pe-3">Kredit</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($transaksi as $row)
                            <tr>
                                <td class="ps-3 text-nowrap" data-label="Tanggal">
                                    @if($row->tanggal)
                                        {{ \Illuminate\Support\Carbon::parse($row->tanggal)->translatedFormat('d M Y') }}
                                    @else
                                        <span class="badge badge-light-warning">Tanpa tanggal</span>
                                    @endif
                                </td>
                                <td data-label="Batch">
                                    @if($row->import_batch_id)
                                        <a href="{{ route('admin.keuangan.jurnal-umum.index', ['tab' => 'transaksi', 'batch_id' => $row->import_batch_id]) }}" class="badge badge-light-primary">#{{ str_pad($row->import_batch_id, 4, '0', STR_PAD_LEFT) }}</a>
                                    @else
                                        <span class="text-muted fs-7">lama</span>
                                    @endif
                                </td>
                                <td style="min-width:220px" class="ui-stack-main" data-label="">{{ $row->keterangan ?? '-' }}</td>
                                <td class="text-nowrap" data-label="Toko">{{ $row->toko }}<div class="text-muted fs-7">{{ $row->divisi }}</div></td>
                                <td class="text-nowrap" data-label="Kategori">{{ $row->kategori }}<div class="text-muted fs-7">{{ $row->akun }}</div></td>
                                <td class="text-end text-nowrap" data-label="Debet">{!! (float) $row->debet ? e($rp($row->debet)) : '<span class="text-muted">–</span>' !!}</td>
                                <td class="text-end text-nowrap pe-3" data-label="Kredit">{!! (float) $row->kredit ? e($rp($row->kredit)) : '<span class="text-muted">–</span>' !!}</td>
                            </tr>
                        @empty
                            <tr><td colspan="7" data-label="" class="text-center text-muted py-10">Tidak ada transaksi untuk filter ini.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="d-flex flex-wrap justify-content-between align-items-center mt-4">
                <div class="text-muted fs-7">
                    @if($transaksi->total())
                        Menampilkan {{ $transaksi->firstItem() }}–{{ $transaksi->lastItem() }} dari {{ number_format($transaksi->total(), 0, ',', '.') }} transaksi
                    @endif
                </div>
                <div class="ui-pager">{{ $transaksi->onEachSide(1)->links('pagination::bootstrap-5') }}</div>
            </div>
        @endif

        @if($tab === 'riwayat')
            <div class="text-muted fs-7 mb-4">
                Setiap file yang di-import dicatat sebagai satu batch. Import yang salah bisa dibatalkan sendiri tanpa menghapus data lain.
                File yang sama persis, atau baris yang sudah pernah masuk, otomatis ditolak.
            </div>
            <div class="table-responsive">
                <table class="table table-row-dashed table-striped align-middle fs-6 gy-3 ui-stack">
                    <thead>
                        <tr class="text-start text-gray-500 fw-bolder fs-7 text-uppercase">
                            <th class="ps-3">Batch</th>
                            <th>Waktu import</th>
                            <th>Nama file</th>
                            <th>Oleh</th>
                            <th>Rentang tanggal</th>
                            <th class="text-end">Baris masuk</th>
                            <th class="text-end">Baris error</th>
                            <th class="text-end">Total kredit</th>
                            <th>Status</th>
                            <th class="text-end pe-3">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($batches as $b)
                            @php [$badgeClass, $badgeText] = $statusBadge[$b->status] ?? ['badge-light', $b->status]; @endphp
                            <tr>
                                <td class="ps-3" data-label="Batch"><span class="badge badge-light-primary">{{ $b->kode() }}</span></td>
                                <td class="text-nowrap" data-label="Waktu">{{ $b->created_at->format('d/m/Y H:i') }}</td>
                                <td data-label="File">{{ $b->file_name }}</td>
                                <td data-label="Oleh">{{ $b->user?->name ?? '-' }}</td>
                                <td class="text-nowrap" data-label="Rentang">
                                    @if($b->tanggal_awal)
                                        {{ $b->tanggal_awal->format('d/m/Y') }}@if($b->tanggal_akhir && !$b->tanggal_akhir->equalTo($b->tanggal_awal)) – {{ $b->tanggal_akhir->format('d/m/Y') }}@endif
                                    @else
                                        <span class="text-muted">–</span>
                                    @endif
                                </td>
                                <td class="text-end" data-label="Baris masuk">{{ number_format($b->rows_count, 0, ',', '.') }}</td>
                                <td class="text-end" data-label="Baris error">{{ $b->error_count ?: '0' }}</td>
                                <td class="text-end text-nowrap" data-label="Total kredit">{{ $b->rows_count ? $rp($b->total_kredit) : '–' }}</td>
                                <td data-label="Status">
                                    <span class="badge {{ $badgeClass }}">{{ $badgeText }}</span>
                                    @if($b->message)<div class="text-muted fs-8 mt-1">{{ $b->message }}</div>@endif
                                    @if($b->status === 'dibatalkan' && $b->cancelled_at)
                                        <div class="text-muted fs-8 mt-1">{{ $b->cancelled_at->format('d/m/Y H:i') }}@if($b->cancelledBy) oleh {{ $b->cancelledBy->name }}@endif</div>
                                    @endif
                                </td>
                                <td class="text-end pe-3 text-nowrap" data-label="">
                                    @if($b->status === 'berhasil')
                                        <a href="{{ route('admin.keuangan.jurnal-umum.index', ['tab' => 'transaksi', 'batch_id' => $b->id]) }}" class="btn btn-sm btn-light">Lihat</a>
                                        <button type="button" class="btn btn-sm btn-light-danger btn-cancel-batch" data-url="{{ route('admin.keuangan.jurnal-umum.batches.destroy', $b) }}" data-kode="{{ $b->kode() }}" data-rows="{{ $b->rows_count }}">Batalkan</button>
                                    @elseif($b->error_file_path)
                                        <a href="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($b->error_file_path) }}" class="btn btn-sm btn-light"><i class="fa-solid fa-download me-1"></i> File error</a>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="10" data-label="" class="text-center text-muted py-10">Belum ada riwayat import.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="d-flex justify-content-end mt-4">{{ $batches->links('pagination::bootstrap-5') }}</div>
        @endif

        @if($tab === 'import')
        <form id="jurnal_import_form" class="form">
            @csrf
            <div class="mb-10">
                <label for="jurnal_import_file" class="required form-label fw-bold">File Excel</label>
                <input type="file" name="file" id="jurnal_import_file" class="form-control form-control-solid" accept=".xlsx,.xls,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet,application/vnd.ms-excel" required>
                <div class="text-danger small mt-2" id="jurnal_import_error"></div>
                <div class="form-text mt-2">
                    Header wajib: <code>tanggal</code>, <code>keterangan</code>, <code>toko</code>, <code>kategori</code>, <code>debet</code>, <code>kredit</code>.
                    <a href="{{ route('admin.keuangan.jurnal-umum.template') }}" class="ms-1">Download template</a>
                </div>
            </div>
            <div class="mb-10">
                <div class="fw-bold mb-2">Syarat Import</div>
                <ul class="text-muted fs-7 mb-0">
                    <li>Format file Excel (.xlsx/.xls), ukuran maksimal 5 MB.</li>
                    <li>Header berada di baris pertama.</li>
                    <li><code>tanggal</code> wajib diisi. Boleh berupa sel tanggal Excel, atau teks <code>dd/mm/yyyy</code> (mis. 21/11/2026) atau <code>yyyy-mm-dd</code> (mis. 2026-11-21).</li>
                    <li><code>toko</code> akan dicocokkan ke tabel <code>sub_divisions</code> (kolom <code>name</code>).</li>
                    <li><code>kategori</code> akan dicocokkan ke tabel <code>sub_akun_biaya</code> (kolom <code>name</code>).</li>
                    <li><code>debet</code> dan <code>kredit</code> bernilai angka >= 0. Boleh pakai pemisah ribuan.</li>
                    <li>Baris kosong akan diabaikan. Maksimal 20 error akan ditampilkan pada ringkasan.</li>
                    <li>Jika ada error, sistem akan menyediakan file Excel berisi semua baris error untuk diunduh.</li>
                    <li>File yang sama persis dengan import sebelumnya akan ditolak. Baris yang sama persis (tanggal, toko, kategori, debet, kredit, keterangan) dengan data yang sudah ada juga ditolak sebagai duplikat.</li>
                    <li>Setiap import tercatat di tab <a href="{{ route('admin.keuangan.jurnal-umum.index', ['tab' => 'riwayat']) }}">Riwayat Import</a> dan bisa dibatalkan per import.</li>
                </ul>
            </div>
            <div class="d-flex align-items-center gap-3">
                <button type="submit" class="btn btn-primary" id="btn_import_submit">
                    <span class="indicator-label">Cek file (pratinjau)</span>
                    <span class="indicator-progress">Memeriksa…
                        <span class="spinner-border spinner-border-sm align-middle ms-2"></span>
                    </span>
                </button>
                <button type="button" class="btn btn-light-danger" id="btn_jurnal_clear">
                    Hapus Semua
                </button>
            </div>
        </form>

        <div class="ui-panel mt-8" id="import_preview" style="display:none;">
            <div class="hd"><h3>Pratinjau import</h3><span class="ui-sp"></span><small>Belum ada data yang disimpan</small></div>
            <div class="p-5">
                <div class="ui-kpis mb-4" id="pv_kpis"></div>
                <div class="row g-4">
                    <div class="col-lg-4"><div class="fw-semibold fs-7 text-uppercase ui-muted mb-2">Per toko</div><div id="pv_toko"></div></div>
                    <div class="col-lg-8"><div class="fw-semibold fs-7 text-uppercase ui-muted mb-2">Contoh baris (10 pertama)</div>
                        <div class="table-responsive"><table class="table table-sm align-middle mb-0 no-ui"><thead><tr><th>Tanggal</th><th>Keterangan</th><th>Toko</th><th>Kategori</th><th class="text-end">Kredit</th></tr></thead><tbody id="pv_rows"></tbody></table></div>
                    </div>
                </div>
                <div class="d-flex gap-2 justify-content-end mt-5">
                    <button type="button" class="btn btn-light" id="pv_cancel">Batal</button>
                    <button type="button" class="btn btn-primary" id="pv_save"><i class="fa-solid fa-check me-1"></i> Simpan import</button>
                </div>
            </div>
        </div>

        <div class="row mt-10" id="import_summary" style="display:none;">
            <div class="col-md-6 col-lg-4">
                <div class="border rounded p-4">
                    <div class="fw-bold">Ringkasan import</div>
                    <div class="d-flex justify-content-between mt-3">
                        <span>Masuk</span>
                        <span id="import_created">0</span>
                    </div>
                    <div class="d-flex justify-content-between mt-2">
                        <span>Dilewati</span>
                        <span id="import_skipped">0</span>
                    </div>
                </div>
            </div>
            <div class="col-lg-8 mt-6 mt-lg-0">
                <div class="border rounded p-4">
                    <div class="fw-bold mb-3">Error (maks. 20)</div>
                    <ul class="mb-0" id="import_errors"></ul>
                    <div class="mt-4" id="error_file_wrapper" style="display:none;">
                        <a href="#" class="btn btn-light-danger" id="error_file_link" download>Unduh file error</a>
                    </div>
                </div>
            </div>
        </div>
        @endif
    </div>
</div>
@endsection

@push('scripts')
<script>
    const importUrl = '{{ route('admin.keuangan.jurnal-umum.store') }}';
    const destroyUrl = '{{ route('admin.keuangan.jurnal-umum.destroy') }}';
    const csrfToken = '{{ csrf_token() }}';
    const listUrl = '{{ route('admin.keuangan.jurnal-umum.index') }}';

    document.addEventListener('DOMContentLoaded', () => {
        const form = document.getElementById('jurnal_import_form');
        const fileInput = document.getElementById('jurnal_import_file');
        const submitBtn = document.getElementById('btn_import_submit');
        const errorEl = document.getElementById('jurnal_import_error');
        const summaryEl = document.getElementById('import_summary');
        const createdEl = document.getElementById('import_created');
        const skippedEl = document.getElementById('import_skipped');
        const errorsEl = document.getElementById('import_errors');
        const errorFileWrapper = document.getElementById('error_file_wrapper');
        const errorFileLink = document.getElementById('error_file_link');
        const clearBtn = document.getElementById('btn_jurnal_clear');

        const setLoading = (loading) => {
            if (!submitBtn) return;
            if (loading) {
                submitBtn.setAttribute('data-kt-indicator', 'on');
                submitBtn.disabled = true;
            } else {
                submitBtn.removeAttribute('data-kt-indicator');
                submitBtn.disabled = false;
            }
        };

        const previewEl = document.getElementById('import_preview');
        const esc = (v) => String(v ?? '').replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
        const rp = (v) => 'Rp ' + new Intl.NumberFormat('id-ID', { maximumFractionDigits: 0 }).format(Number(v || 0));
        const tgl = (v) => { const m = String(v || '').match(/^(\d{4})-(\d{2})-(\d{2})/); return m ? `${m[3]}/${m[2]}/${m[1]}` : '–'; };
        let saveForReal = false;
        fileInput?.addEventListener('change', () => { if (previewEl) previewEl.style.display = 'none'; });
        document.getElementById('pv_cancel')?.addEventListener('click', () => { previewEl.style.display = 'none'; if (fileInput) fileInput.value = ''; });
        document.getElementById('pv_save')?.addEventListener('click', () => { saveForReal = true; form.requestSubmit(); });
        const showPreview = (json) => {
            document.getElementById('pv_kpis').innerHTML = [
                ['Baris siap masuk', new Intl.NumberFormat('id-ID').format(json.created)],
                ['Total kredit', rp(json.total_kredit)],
                ['Total debet', rp(json.total_debet)],
                ['Rentang tanggal', json.tanggal_awal ? `${tgl(json.tanggal_awal)} – ${tgl(json.tanggal_akhir)}` : '–'],
            ].map(([l, v]) => `<div class="ui-kpi"><div class="l">${l}</div><div class="v" style="font-size:17px">${esc(v)}</div></div>`).join('');
            document.getElementById('pv_toko').innerHTML = (json.per_toko || []).map((t) =>
                `<div class="d-flex justify-content-between py-1 border-bottom"><span>${esc(t.name)} <span class="ui-muted fs-8">${t.jumlah} baris</span></span><span>${rp(t.total)}</span></div>`).join('');
            document.getElementById('pv_rows').innerHTML = (json.sample || []).map((r) =>
                `<tr><td class="text-nowrap">${tgl(r.tanggal)}</td><td>${esc(r.keterangan)}</td><td>${esc(r.toko)}</td><td>${esc(r.kategori)}</td><td class="text-end text-nowrap">${rp(r.kredit)}</td></tr>`).join('');
            previewEl.style.display = 'block';
            previewEl.scrollIntoView({ behavior: 'smooth', block: 'start' });
        };

        form?.addEventListener('submit', async (e) => {
            e.preventDefault();
            const isPreview = !saveForReal;
            saveForReal = false;
            if (previewEl) previewEl.style.display = 'none';
            if (errorEl) errorEl.textContent = '';
            if (summaryEl) summaryEl.style.display = 'none';
            if (errorsEl) errorsEl.innerHTML = '';
            if (errorFileWrapper) errorFileWrapper.style.display = 'none';
            if (errorFileLink) errorFileLink.removeAttribute('href');

            const file = fileInput?.files?.[0];
            if (!file) {
                if (errorEl) errorEl.textContent = 'Pilih file Excel terlebih dahulu.';
                return;
            }

            const formData = new FormData();
            formData.append('file', file);
            if (isPreview) formData.append('preview', '1');

            setLoading(true);
            try {
                const res = await fetch(importUrl, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json',
                    },
                    body: formData,
                });
                const text = await res.text();
                let json;
                try { json = JSON.parse(text); } catch (err) {
                    if (errorEl) errorEl.textContent = 'Respons server tidak valid.';
                    return;
                }
                if (!res.ok) {
                    if (errorEl) errorEl.textContent = json.message || 'Gagal import.';
                    return;
                }
                if (json.preview && !json.all_failed) {
                    showPreview(json);
                    return;
                }
                if (createdEl) createdEl.textContent = json.created ?? 0;
                if (skippedEl) skippedEl.textContent = json.skipped ?? 0;
                if (errorsEl && Array.isArray(json.errors)) {
                    json.errors.forEach(err => {
                        const li = document.createElement('li');
                        li.textContent = err;
                        errorsEl.appendChild(li);
                    });
                }
                if (summaryEl) summaryEl.style.display = 'flex';
                if (json.error_file_url && errorFileWrapper && errorFileLink) {
                    errorFileLink.href = json.error_file_url;
                    errorFileWrapper.style.display = 'block';
                }
                if (typeof Swal !== 'undefined') {
                    if (json.error_file_url) {
                        const warnText = json.all_failed
                            ? (json.preview ? 'File ini belum bisa di-import karena ada baris error. Perbaiki dulu barisnya.' : 'Semua data tidak dimasukkan ke database karena ada error.')
                            : 'Sebagian data gagal diimport. Download file error untuk detail.';
                        Swal.fire({
                            title: json.preview ? 'Ada baris yang perlu diperbaiki' : 'Import selesai dengan error',
                            text: warnText,
                            icon: 'warning',
                            showCancelButton: true,
                            confirmButtonText: 'Download',
                            cancelButtonText: 'Tutup',
                        }).then((result) => {
                            if (result.isConfirmed && json.error_file_url) {
                                window.location.href = json.error_file_url;
                            }
                        });
                    } else {
                        Swal.fire({
                            title: 'Import berhasil',
                            text: `${json.created ?? 0} baris jurnal masuk sebagai batch ${json.batch ?? ''}.`,
                            icon: 'success',
                            showCancelButton: true,
                            confirmButtonText: 'Lihat transaksinya',
                            cancelButtonText: 'Tutup',
                        }).then((result) => {
                            if (result.isConfirmed && json.batch_id) {
                                window.location.href = `${listUrl}?tab=transaksi&batch_id=${json.batch_id}`;
                            }
                        });
                    }
                }
            } catch (err) {
                if (errorEl) errorEl.textContent = 'Gagal import.';
                if (typeof Swal !== 'undefined') {
                    Swal.fire('Error', 'Gagal import', 'error');
                }
            } finally {
                setLoading(false);
            }
        });

        document.querySelectorAll('.btn-cancel-batch').forEach((btn) => {
            btn.addEventListener('click', async () => {
                const kode = btn.dataset.kode;
                const rows = btn.dataset.rows;
                if (typeof Swal !== 'undefined') {
                    const confirm = await Swal.fire({
                        title: `Batalkan import ${kode}?`,
                        text: `${rows} baris jurnal dari import ini akan dihapus. Data dari import lain tidak terpengaruh.`,
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonText: 'Ya, batalkan',
                        cancelButtonText: 'Tidak',
                    });
                    if (!confirm.isConfirmed) return;
                } else if (!window.confirm(`Batalkan import ${kode}?`)) {
                    return;
                }
                btn.disabled = true;
                try {
                    const res = await fetch(btn.dataset.url, {
                        method: 'DELETE',
                        headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
                    });
                    const json = await res.json().catch(() => ({}));
                    if (!res.ok) throw new Error(json.message || 'Gagal membatalkan import.');
                    if (typeof Swal !== 'undefined') await Swal.fire('Berhasil', json.message, 'success');
                    window.location.reload();
                } catch (err) {
                    btn.disabled = false;
                    if (typeof Swal !== 'undefined') Swal.fire('Error', err.message, 'error');
                }
            });
        });

        clearBtn?.addEventListener('click', async () => {
            const doDelete = async () => {
                const res = await fetch(destroyUrl, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json',
                    },
                });
                const text = await res.text();
                let json;
                try { json = JSON.parse(text); } catch (err) {
                    throw new Error('Respons server tidak valid.');
                }
                if (!res.ok) {
                    throw new Error(json.message || 'Gagal menghapus data.');
                }
                return json;
            };

            if (typeof Swal !== 'undefined') {
                const confirm = await Swal.fire({
                    title: 'Hapus semua jurnal?',
                    text: 'Tindakan ini tidak dapat dibatalkan.',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: 'Hapus',
                    cancelButtonText: 'Batal',
                });
                if (!confirm.isConfirmed) return;
            }

            try {
                const json = await doDelete();
                if (typeof Swal !== 'undefined') {
                    if (json?.was_empty) {
                        Swal.fire('Info', json.message || 'Data jurnal umum sudah kosong.', 'info');
                    } else {
                        const deleted = json?.deleted ?? 0;
                        const msg = deleted > 0
                            ? `Berhasil menghapus ${deleted} data jurnal umum.`
                            : (json.message || 'Semua data jurnal umum berhasil dihapus.');
                        Swal.fire('Berhasil', msg, 'success');
                    }
                }
            } catch (err) {
                if (typeof Swal !== 'undefined') {
                    Swal.fire('Error', err.message || 'Gagal menghapus data.', 'error');
                }
            }
        });
    });
</script>
@endpush
