@extends('layouts.admin')

@section('title', 'Import Resi')
@section('page_title', 'Import Resi')

@section('content')
<div class="row g-6 g-xl-9">
    <div class="col-xl-8">
        <div class="card">
            <div class="card-header border-0 pt-6 pb-2 align-items-center">
                <div class="card-title">
                    <h2 class="fw-bolder mb-0">Import Resi (CSV)</h2>
                </div>
                <div class="card-toolbar">
                    <button type="button" class="btn btn-light-danger" id="btn_clear_resi">
                        <i class="fa-solid fa-trash me-2"></i>Hapus Semua Data
                    </button>
                </div>
            </div>
            <div class="card-body py-8 px-10">
                <form id="import_resi_form" action="{{ route('admin.import.store') }}" method="POST" enctype="multipart/form-data" class="form">
                    @csrf
                    <div class="mb-8">
                        <label for="import_resi_file" class="required form-label fw-bold">File CSV Resi</label>
                        <input type="file" name="file" id="import_resi_file" class="form-control form-control-solid" accept=".csv,text/csv" required>
                        <div class="text-danger small mt-2" id="import_resi_error"></div>
                        <div class="text-muted small mt-2">Maksimal 4 MB. Header harus berisi kolom nomor resi.</div>
                    </div>
                    <div class="d-flex align-items-center gap-3">
                        <button type="submit" class="btn btn-primary" id="btn_import_resi_submit">
                            <span class="indicator-label">Mulai Import</span>
                            <span class="indicator-progress d-none">Memproses...
                                <span class="spinner-border spinner-border-sm align-middle ms-2"></span>
                            </span>
                        </button>
                        <span class="text-muted">Nomor resi yang sama tidak akan ditambahkan ulang.</span>
                    </div>
                </form>
                <div class="row mt-10" id="import_summary" style="display:none;">
                    <div class="col-xl-6 mb-6">
                        <div class="card border border-dashed">
                            <div class="card-body p-6">
                                <h4 class="fw-bold mb-4">Ringkasan Import</h4>
                                <div class="d-flex flex-column gap-3">
                                    <div class="d-flex justify-content-between"><span class="text-muted">Total baris</span><span class="fw-bold" id="sum_total_rows">0</span></div>
                                    <div class="d-flex justify-content-between"><span class="text-muted">Berhasil dimasukkan</span><span class="fw-bold text-success" id="sum_inserted">0</span></div>
                                    <div class="d-flex justify-content-between"><span class="text-muted">Duplikat di database</span><span class="fw-bold text-warning" id="sum_dup_db">0</span></div>
                                    <div class="d-flex justify-content-between"><span class="text-muted">Duplikat di file</span><span class="fw-bold text-warning" id="sum_dup_file">0</span></div>
                                    <div class="d-flex justify-content-between"><span class="text-muted">Dilewati (kosong/tidak valid)</span><span class="fw-bold text-gray-700" id="sum_skipped">0</span></div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-xl-6 mb-6">
                        <div class="card border border-dashed h-100">
                            <div class="card-body p-6">
                                <div class="d-flex justify-content-between align-items-center mb-4">
                                    <h4 class="fw-bold mb-0">Daftar Duplikat</h4>
                                    <span class="badge badge-light-primary" id="sum_dup_count">0</span>
                                </div>
                                <div id="duplicate_list" class="mh-300px overflow-auto">
                                    <div class="text-muted">Belum ada duplikat.</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-4">
        <div class="card h-100">
            <div class="card-header border-0 pt-6 pb-2">
                <div class="card-title">
                    <h3 class="fw-bolder mb-0">Format CSV</h3>
                </div>
            </div>
            <div class="card-body py-6 px-8">
                <p class="text-gray-700 mb-4">Gunakan salah satu nama kolom berikut untuk nomor resi:</p>
                <ul class="fw-semibold text-gray-800 mb-6">
                    <li><code>resi_number</code></li>
                    <li><code>resi</code></li>
                    <li><code>nomor_resi</code></li>
                    <li><code>no_resi</code></li>
                    <li><code>tracking_number</code></li>
                </ul>
                <p class="text-muted small mb-3">Contoh sederhana:</p>
                <pre class="rounded bg-light p-4 mb-0">resi_number
ABC123456789
XYZ987654321
TRK-20240606</pre>
            </div>
        </div>
    </div>
</div>

<div class="row g-6 g-xl-9 mt-5">
    <div class="col-xl-8">
        <div class="card">
            <div class="card-header border-0 pt-6 pb-2 align-items-center">
                <div class="card-title">
                    <h2 class="fw-bolder mb-0">Import Resi Instan (CSV)</h2>
                </div>
                <div class="card-toolbar">
                    <button type="button" class="btn btn-light-danger" id="btn_clear_resi_instan">
                        <i class="fa-solid fa-trash me-2"></i>Hapus Semua Data Instan
                    </button>
                </div>
            </div>
            <div class="card-body py-8 px-10">
                <form id="import_resi_instan_form" action="{{ route('admin.import.instan') }}" method="POST" enctype="multipart/form-data" class="form">
                    @csrf
                    <div class="mb-8">
                        <label for="import_resi_instan_file" class="required form-label fw-bold">File CSV Resi Instan</label>
                        <input type="file" name="file" id="import_resi_instan_file" class="form-control form-control-solid" accept=".csv,text/csv" required>
                        <div class="text-danger small mt-2" id="import_resi_instan_error"></div>
                        <div class="text-muted small mt-2">Maksimal 4 MB. Header harus berisi kolom ID pesanan.</div>
                    </div>
                    <div class="d-flex align-items-center gap-3">
                        <button type="submit" class="btn btn-primary" id="btn_import_resi_instan_submit">
                            <span class="indicator-label">Mulai Import</span>
                            <span class="indicator-progress d-none">Memproses...
                                <span class="spinner-border spinner-border-sm align-middle ms-2"></span>
                            </span>
                        </button>
                        <span class="text-muted">Nomor resi yang sama tidak akan ditambahkan ulang.</span>
                    </div>
                </form>
                <div class="row mt-10" id="import_instan_summary" style="display:none;">
                    <div class="col-xl-6 mb-6">
                        <div class="card border border-dashed">
                            <div class="card-body p-6">
                                <h4 class="fw-bold mb-4">Ringkasan Import</h4>
                                <div class="d-flex flex-column gap-3">
                                    <div class="d-flex justify-content-between"><span class="text-muted">Total baris</span><span class="fw-bold" id="instan_total_rows">0</span></div>
                                    <div class="d-flex justify-content-between"><span class="text-muted">Berhasil dimasukkan</span><span class="fw-bold text-success" id="instan_inserted">0</span></div>
                                    <div class="d-flex justify-content-between"><span class="text-muted">Duplikat di database</span><span class="fw-bold text-warning" id="instan_dup_db">0</span></div>
                                    <div class="d-flex justify-content-between"><span class="text-muted">Duplikat di file</span><span class="fw-bold text-warning" id="instan_dup_file">0</span></div>
                                    <div class="d-flex justify-content-between"><span class="text-muted">Dilewati (kosong/tidak valid)</span><span class="fw-bold text-gray-700" id="instan_skipped">0</span></div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-xl-6 mb-6">
                        <div class="card border border-dashed h-100">
                            <div class="card-body p-6">
                                <div class="d-flex justify-content-between align-items-center mb-4">
                                    <h4 class="fw-bold mb-0">Daftar Duplikat</h4>
                                    <span class="badge badge-light-primary" id="instan_dup_count">0</span>
                                </div>
                                <div id="instan_duplicate_list" class="mh-300px overflow-auto">
                                    <div class="text-muted">Belum ada duplikat.</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-4">
        <div class="card h-100">
            <div class="card-header border-0 pt-6 pb-2">
                <div class="card-title">
                    <h3 class="fw-bolder mb-0">Format CSV Instan</h3>
                </div>
            </div>
                        <div class="card-body py-6 px-8">
                <p class="text-gray-700 mb-4">Gunakan salah satu nama kolom berikut untuk ID pesanan:</p>
                <ul class="fw-semibold text-gray-800 mb-6">
                    <li><code>order_id</code></li>
                    <li><code>id_pesanan</code></li>
                    <li><code>nomor_pesanan</code></li>
                    <li><code>no_pesanan</code></li>
                    <li><code>order_number</code></li>
                </ul>
                <p class="text-muted small mb-3">Contoh sederhana:</p>
                <pre class="rounded bg-light p-4 mb-0">order_id
INV-1001
INV-2002
INV-3003</pre>
            </div>
        </div>
    </div>
</div>

<div class="row g-6 g-xl-9 mt-5">
    <div class="col-xl-8">
        <div class="card">
            <div class="card-header border-0 pt-6 pb-2 align-items-center">
                <div class="card-title">
                    <h2 class="fw-bolder mb-0">Import Data Kirim per Resi (CSV)</h2>
                </div>
                <div class="card-toolbar">
                    <button type="button" class="btn btn-light-danger" id="btn_clear_shipments">
                        <i class="fa-solid fa-trash me-2"></i>Hapus Semua Data Kirim
                    </button>
                </div>
            </div>
            <div class="card-body py-8 px-10">
                <form id="import_shipments_form" action="{{ route('admin.import.shipments') }}" method="POST" enctype="multipart/form-data" class="form">
                    @csrf
                    <div class="mb-8">
                        <label for="import_shipments_file" class="required form-label fw-bold">File CSV Resi + SKU + Jumlah</label>
                        <input type="file" name="file" id="import_shipments_file" class="form-control form-control-solid" accept=".csv,text/csv" required>
                        <div class="text-danger small mt-2" id="import_shipments_error"></div>
                        <div class="text-muted small mt-2">Header wajib memuat kolom resi, ID pesanan, sku, dan jumlah.</div>
                    </div>
                    <div class="d-flex align-items-center gap-3">
                        <button type="submit" class="btn btn-primary" id="btn_import_shipments_submit">
                            <span class="indicator-label">Mulai Import</span>
                            <span class="indicator-progress d-none">Memproses...
                                <span class="spinner-border spinner-border-sm align-middle ms-2"></span>
                            </span>
                        </button>
                        <span class="text-muted">Data dengan kombinasi Resi + SKU sama akan diperbarui.</span>
                    </div>
                </form>
                <div class="row mt-10" id="shipments_summary" style="display:none;">
                    <div class="col-xl-6 mb-6">
                        <div class="card border border-dashed">
                            <div class="card-body p-6">
                                <h4 class="fw-bold mb-4">Ringkasan Import</h4>
                                <div class="d-flex flex-column gap-3">
                                    <div class="d-flex justify-content-between"><span class="text-muted">Total baris</span><span class="fw-bold" id="ship_total_rows">0</span></div>
                                    <div class="d-flex justify-content-between"><span class="text-muted">Berhasil dimasukkan</span><span class="fw-bold text-success" id="ship_inserted">0</span></div>
                                    <div class="d-flex justify-content-between"><span class="text-muted">Diperbarui</span><span class="fw-bold text-primary" id="ship_updated">0</span></div>
                                    <div class="d-flex justify-content-between"><span class="text-muted">Duplikat di database</span><span class="fw-bold text-warning" id="ship_dup_db">0</span></div>
                                    <div class="d-flex justify-content-between"><span class="text-muted">Duplikat di file</span><span class="fw-bold text-warning" id="ship_dup_file">0</span></div>
                                    <div class="d-flex justify-content-between"><span class="text-muted">Dilewati (kosong/tidak valid)</span><span class="fw-bold text-gray-700" id="ship_skipped">0</span></div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-xl-6 mb-6">
                        <div class="card border border-dashed h-100">
                            <div class="card-body p-6">
                                <div class="d-flex justify-content-between align-items-center mb-4">
                                    <h4 class="fw-bold mb-0">Duplikat Resi + SKU</h4>
                                    <span class="badge badge-light-primary" id="ship_dup_count">0</span>
                                </div>
                                <div id="ship_duplicate_list" class="mh-300px overflow-auto">
                                    <div class="text-muted">Belum ada duplikat.</div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-12 mb-6">
                        <div class="card border border-dashed h-100">
                            <div class="card-body p-6">
                                <div class="d-flex justify-content-between align-items-center mb-4">
                                    <h4 class="fw-bold mb-0">Data Dilewati</h4>
                                    <span class="badge badge-light-secondary" id="ship_skipped_count">0</span>
                                </div>
                                <div class="table-responsive mh-300px">
                                    <table class="table table-row-dashed align-middle mb-0">
                                        <thead>
                                            <tr class="text-gray-500 fw-semibold text-uppercase fs-8">
                                                <th>Resi</th>
                                                <th>ID Pesanan</th>
                                                <th>SKU</th>
                                                <th class="text-end">Jumlah</th>
                                            </tr>
                                        </thead>
                                        <tbody id="ship_skipped_list">
                                            <tr><td colspan="4" class="text-muted text-center">Tidak ada data dilewati.</td></tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-4">
        <div class="card h-100">
            <div class="card-header border-0 pt-6 pb-2">
                <div class="card-title">
                    <h3 class="fw-bolder mb-0">Format CSV Kirim</h3>
                </div>
            </div>
            <div class="card-body py-6 px-8">
                <p class="text-gray-700 mb-4">Header wajib memuat:</p>
                <ul class="fw-semibold text-gray-800 mb-6">
                    <li><code>resi</code> / <code>resi_number</code> / <code>nomor_resi</code></li>
                    <li><code>id_pesanan</code> / <code>order_id</code> / <code>nomor_pesanan</code></li>
                    <li><code>sku</code></li>
                    <li><code>jumlah</code> / <code>qty</code> / <code>quantity</code></li>
                </ul>
                <p class="text-muted small mb-3">Contoh sederhana:</p>
                <pre class="rounded bg-light p-4 mb-0">resi,id_pesanan,sku,jumlah
ABC123456789,INV-1001,SKU-01,3
ABC123456789,INV-1001,SKU-02,1
XYZ987654321,INV-2001,SKU-05,2</pre>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById('import_resi_form');
    if (!form) return;

    const submitBtn = document.getElementById('btn_import_resi_submit');
    const errorEl = document.getElementById('import_resi_error');
    const summaryEl = document.getElementById('import_summary');
    const duplicateListEl = document.getElementById('duplicate_list');
    const sumTotalRows = document.getElementById('sum_total_rows');
    const sumInserted = document.getElementById('sum_inserted');
    const sumDupDb = document.getElementById('sum_dup_db');
    const sumDupFile = document.getElementById('sum_dup_file');
    const sumSkipped = document.getElementById('sum_skipped');
    const sumDupCount = document.getElementById('sum_dup_count');
    const clearBtn = document.getElementById('btn_clear_resi');
    const destroyUrl = '{{ route('admin.import.destroy') }}';
    // Instan elements
    const instanForm = document.getElementById('import_resi_instan_form');
    const instanError = document.getElementById('import_resi_instan_error');
    const instanSummary = document.getElementById('import_instan_summary');
    const instanFile = document.getElementById('import_resi_instan_file');
    const instanBtn = document.getElementById('btn_import_resi_instan_submit');
    const instanTotalRows = document.getElementById('instan_total_rows');
    const instanInserted = document.getElementById('instan_inserted');
    const instanDupDb = document.getElementById('instan_dup_db');
    const instanDupFile = document.getElementById('instan_dup_file');
    const instanSkipped = document.getElementById('instan_skipped');
    const instanDupCount = document.getElementById('instan_dup_count');
    const instanDupList = document.getElementById('instan_duplicate_list');
    const instanClearBtn = document.getElementById('btn_clear_resi_instan');
    const instanDestroyUrl = '{{ route('admin.import.instan.destroy') }}';
    const instanUrl = '{{ route('admin.import.instan') }}';
    // Shipments elements
    const shipForm = document.getElementById('import_shipments_form');
    const shipError = document.getElementById('import_shipments_error');
    const shipSummary = document.getElementById('shipments_summary');
    const shipFile = document.getElementById('import_shipments_file');
    const shipBtn = document.getElementById('btn_import_shipments_submit');
    const shipTotalRows = document.getElementById('ship_total_rows');
    const shipInserted = document.getElementById('ship_inserted');
    const shipUpdated = document.getElementById('ship_updated');
    const shipDupDb = document.getElementById('ship_dup_db');
    const shipDupFile = document.getElementById('ship_dup_file');
    const shipSkipped = document.getElementById('ship_skipped');
    const shipDupCount = document.getElementById('ship_dup_count');
    const shipDupList = document.getElementById('ship_duplicate_list');
    const shipSkippedList = document.getElementById('ship_skipped_list');
    const shipSkippedCount = document.getElementById('ship_skipped_count');
    const shipUrl = '{{ route('admin.import.shipments') }}';
    const shipClearBtn = document.getElementById('btn_clear_shipments');
    const shipDestroyUrl = '{{ route('admin.import.shipments.destroy') }}';
    const fileInput = document.getElementById('import_resi_file');
    const toggleLoading = (loading) => {
        if (!submitBtn) return;
        const indicator = submitBtn.querySelector('.indicator-progress');
        const label = submitBtn.querySelector('.indicator-label');
        if (loading) {
            submitBtn.disabled = true;
            indicator?.classList.remove('d-none');
            label?.classList.add('d-none');
        } else {
            submitBtn.disabled = false;
            indicator?.classList.add('d-none');
            label?.classList.remove('d-none');
        }
    };

    toggleLoading(false);

    function toggleInstanLoading(loading) {
        if (!instanBtn) return;
        const indicator = instanBtn.querySelector('.indicator-progress');
        const label = instanBtn.querySelector('.indicator-label');
        if (loading) {
            instanBtn.disabled = true;
            indicator?.classList.remove('d-none');
            label?.classList.add('d-none');
        } else {
            instanBtn.disabled = false;
            indicator?.classList.add('d-none');
            label?.classList.remove('d-none');
        }
    }

    form.addEventListener('submit', async (e) => {
        e.preventDefault();
        errorEl.textContent = '';
        if (summaryEl) summaryEl.style.display = 'none';

        const file = fileInput?.files?.[0];
        if (!file) {
            errorEl.textContent = 'Pilih file CSV terlebih dahulu.';
            return;
        }

        const formData = new FormData(form);
        toggleLoading(true);
        try {
            const res = await fetch(form.action, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json',
                },
                body: formData,
            });

            const text = await res.text();
            let data;
            try {
                data = JSON.parse(text);
            } catch (err) {
                console.error('Invalid JSON', text);
                throw new Error('Respons server tidak valid');
            }

            if (!res.ok) {
                if (data?.errors) {
                    const messages = Object.values(data.errors).flat().join(', ');
                    errorEl.textContent = messages || data.message || 'Gagal mengimpor file';
                } else {
                    errorEl.textContent = data.message || 'Gagal mengimpor file';
                }
                if (data?.summary) {
                    updateSummary(data.summary);
                }
                return;
            }

            form.reset();
            updateSummary(data.summary || {});
            if (summaryEl) summaryEl.style.display = 'flex';
            if (typeof Swal !== 'undefined') {
                Swal.fire('Berhasil', data.message || 'Import selesai', 'success');
            }
        } catch (err) {
            console.error(err);
            errorEl.textContent = err.message || 'Terjadi kesalahan.';
            if (typeof Swal !== 'undefined') {
                Swal.fire('Error', err.message || 'Terjadi kesalahan', 'error');
            }
        } finally {
            toggleLoading(false);
        }
    });

    instanForm?.addEventListener('submit', async (e) => {
        e.preventDefault();
        if (instanError) instanError.textContent = '';
        if (instanSummary) instanSummary.style.display = 'none';

        const file = instanFile?.files?.[0];
        if (!file) {
            if (instanError) instanError.textContent = 'Pilih file CSV terlebih dahulu.';
            return;
        }

        const formData = new FormData(instanForm);
        toggleInstanLoading(true);
        try {
            const res = await fetch(instanUrl, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json',
                },
                body: formData,
            });

            const text = await res.text();
            let data;
            try {
                data = JSON.parse(text);
            } catch (err) {
                console.error('Invalid JSON', text);
                throw new Error('Respons server tidak valid');
            }

            if (!res.ok) {
                if (data?.errors) {
                    const messages = Object.values(data.errors).flat().join(', ');
                    if (instanError) instanError.textContent = messages || data.message || 'Gagal mengimpor file';
                } else if (instanError) {
                    instanError.textContent = data.message || 'Gagal mengimpor file';
                }
                if (data?.summary) {
                    updateInstanSummary(data.summary);
                }
                return;
            }

            instanForm.reset();
            updateInstanSummary(data.summary || {});
            if (instanSummary) instanSummary.style.display = 'flex';
            if (typeof Swal !== 'undefined') {
                Swal.fire('Berhasil', data.message || 'Import selesai', 'success');
            }
        } catch (err) {
            console.error(err);
            if (instanError) instanError.textContent = err.message || 'Terjadi kesalahan.';
            if (typeof Swal !== 'undefined') {
                Swal.fire('Error', err.message || 'Terjadi kesalahan', 'error');
            }
        } finally {
            toggleInstanLoading(false);
        }
    });

    clearBtn?.addEventListener('click', async () => {
        let confirmed = true;
        if (typeof Swal !== 'undefined') {
            const res = await Swal.fire({
                title: 'Hapus semua data resi?',
                text: 'Tindakan ini akan mengosongkan tabel scan_resi.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Ya, hapus',
                cancelButtonText: 'Batal',
                buttonsStyling: false,
                customClass: {
                    confirmButton: 'btn btn-danger',
                    cancelButton: 'btn btn-light'
                }
            });
            confirmed = res.isConfirmed;
        } else {
            confirmed = confirm('Hapus semua data resi?');
        }

        if (!confirmed) return;

        clearBtn.disabled = true;
        try {
            const res = await fetch(destroyUrl, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json',
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams({ _method: 'DELETE' }),
            });

            const data = await res.json();
            if (!res.ok) {
                throw new Error(data?.message || 'Gagal menghapus data');
            }

            updateSummary({
                total_rows: 0,
                inserted: 0,
                duplicate_in_db: 0,
                duplicate_in_file: 0,
                skipped_empty: 0,
                duplicates: [],
            });
            if (summaryEl) summaryEl.style.display = 'flex';
            if (typeof Swal !== 'undefined') {
                Swal.fire('Berhasil', data.message || 'Data dihapus', 'success');
            }
        } catch (err) {
            console.error(err);
            if (typeof Swal !== 'undefined') {
                Swal.fire('Error', err.message || 'Gagal menghapus data', 'error');
            } else {
                alert(err.message || 'Gagal menghapus data');
            }
        } finally {
            clearBtn.disabled = false;
        }
    });

    function updateSummary(summary) {
        if (!summaryEl) return;
        summaryEl.style.display = 'flex';

        sumTotalRows.textContent = summary.total_rows ?? 0;
        sumInserted.textContent = summary.inserted ?? 0;
        sumDupDb.textContent = summary.duplicate_in_db ?? 0;
        sumDupFile.textContent = summary.duplicate_in_file ?? 0;
        sumSkipped.textContent = summary.skipped_empty ?? 0;

        const duplicates = summary.duplicates ?? [];
        sumDupCount.textContent = duplicates.length;

        if (!duplicateListEl) return;
        if (duplicates.length === 0) {
            duplicateListEl.innerHTML = '<div class="text-muted">Tidak ada duplikat.</div>';
            return;
        }

        const items = duplicates.map(d => `
            <div class="d-flex align-items-start mb-3">
                <div class="symbol symbol-35px me-3">
                    <span class="symbol-label bg-light-warning text-warning fw-bold">#</span>
                </div>
                <div class="flex-grow-1">
                    <div class="fw-bold">${d.resi_number}</div>
                    <div class="text-muted small">${d.reason || 'Duplikat'}</div>
                </div>
            </div>
        `);
        duplicateListEl.innerHTML = items.join('');
    }

    function toggleShipLoading(loading) {
        if (!shipBtn) return;
        const indicator = shipBtn.querySelector('.indicator-progress');
        const label = shipBtn.querySelector('.indicator-label');
        if (loading) {
            shipBtn.disabled = true;
            indicator?.classList.remove('d-none');
            label?.classList.add('d-none');
        } else {
            shipBtn.disabled = false;
            indicator?.classList.add('d-none');
            label?.classList.remove('d-none');
        }
    }

    function updateInstanSummary(summary) {
        if (!instanSummary) return;
        instanSummary.style.display = 'flex';

        instanTotalRows.textContent = summary.total_rows ?? 0;
        instanInserted.textContent = summary.inserted ?? 0;
        instanDupDb.textContent = summary.duplicate_in_db ?? 0;
        instanDupFile.textContent = summary.duplicate_in_file ?? 0;
        instanSkipped.textContent = summary.skipped_empty ?? 0;

        const duplicates = summary.duplicates ?? [];
        instanDupCount.textContent = duplicates.length;

        if (!instanDupList) return;
        if (duplicates.length === 0) {
            instanDupList.innerHTML = '<div class="text-muted">Tidak ada duplikat.</div>';
            return;
        }

        const items = duplicates.map(d => `
            <div class="d-flex align-items-start mb-3">
                <div class="symbol symbol-35px me-3">
                    <span class="symbol-label bg-light-warning text-warning fw-bold">#</span>
                </div>
                <div class="flex-grow-1">
                    <div class="fw-bold">${d.order_id}</div>
                    <div class="text-muted small">${d.reason || 'Duplikat'}</div>
                </div>
            </div>
        `);
        instanDupList.innerHTML = items.join('');
    }

    instanClearBtn?.addEventListener('click', async () => {
        let confirmed = true;
        if (typeof Swal !== 'undefined') {
            const res = await Swal.fire({
                title: 'Hapus semua data resi instan?',
                text: 'Tindakan ini akan mengosongkan tabel scan_resi_instan.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Ya, hapus',
                cancelButtonText: 'Batal',
                buttonsStyling: false,
                customClass: {
                    confirmButton: 'btn btn-danger',
                    cancelButton: 'btn btn-light'
                }
            });
            confirmed = res.isConfirmed;
        } else {
            confirmed = confirm('Hapus semua data resi instan?');
        }

        if (!confirmed) return;

        instanClearBtn.disabled = true;
        try {
            const res = await fetch(instanDestroyUrl, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json',
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams({ _method: 'DELETE' }),
            });

            const data = await res.json();
            if (!res.ok) {
                throw new Error(data?.message || 'Gagal menghapus data');
            }

            updateInstanSummary({
                total_rows: 0,
                inserted: 0,
                duplicate_in_db: 0,
                duplicate_in_file: 0,
                skipped_empty: 0,
                duplicates: [],
            });
            if (instanSummary) instanSummary.style.display = 'flex';
            if (typeof Swal !== 'undefined') {
                Swal.fire('Berhasil', data.message || 'Data dihapus', 'success');
            }
        } catch (err) {
            console.error(err);
            if (typeof Swal !== 'undefined') {
                Swal.fire('Error', err.message || 'Gagal menghapus data', 'error');
            } else {
                alert(err.message || 'Gagal menghapus data');
            }
        } finally {
            instanClearBtn.disabled = false;
        }
    });

    shipForm?.addEventListener('submit', async (e) => {
        e.preventDefault();
        if (shipError) shipError.textContent = '';
        if (shipSummary) shipSummary.style.display = 'none';

        const file = shipFile?.files?.[0];
        if (!file) {
            if (shipError) shipError.textContent = 'Pilih file CSV terlebih dahulu.';
            return;
        }

        const formData = new FormData(shipForm);
        toggleShipLoading(true);
        try {
            const res = await fetch(shipUrl, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json',
                },
                body: formData,
            });
            const text = await res.text();
            let data;
            try {
                data = JSON.parse(text);
            } catch (err) {
                console.error('Invalid JSON', text);
                throw new Error('Respons server tidak valid');
            }

            if (!res.ok) {
                if (data?.errors) {
                    const messages = Object.values(data.errors).flat().join(', ');
                    if (shipError) shipError.textContent = messages || data.message || 'Gagal mengimpor file';
                } else if (shipError) {
                    shipError.textContent = data.message || 'Gagal mengimpor file';
                }
                if (data?.summary) {
                    updateShipSummary(data.summary);
                }
                return;
            }

            shipForm.reset();
            updateShipSummary(data.summary || {});
            if (shipSummary) shipSummary.style.display = 'flex';
            if (typeof Swal !== 'undefined') {
                Swal.fire('Berhasil', data.message || 'Import selesai', 'success');
            }
        } catch (err) {
            console.error(err);
            if (shipError) shipError.textContent = err.message || 'Terjadi kesalahan.';
            if (typeof Swal !== 'undefined') {
                Swal.fire('Error', err.message || 'Terjadi kesalahan', 'error');
            }
        } finally {
            toggleShipLoading(false);
        }
    });

    function updateShipSummary(summary) {
        if (!shipSummary) return;
        shipSummary.style.display = 'flex';

        shipTotalRows.textContent = summary.total_rows ?? 0;
        shipInserted.textContent = summary.inserted ?? 0;
        shipUpdated.textContent = summary.updated ?? 0;
        shipDupDb.textContent = summary.duplicate_in_db ?? 0;
        shipDupFile.textContent = summary.duplicate_in_file ?? 0;
        shipSkipped.textContent = summary.skipped_empty ?? 0;
        shipSkippedCount.textContent = (summary.skipped_entries ?? []).length;

        const duplicates = summary.duplicates ?? [];
        shipDupCount.textContent = duplicates.length;

        if (!shipDupList) return;
        if (duplicates.length === 0) {
            shipDupList.innerHTML = '<div class="text-muted">Tidak ada duplikat.</div>';
        } else {
            const items = duplicates.map(d => `
                <div class="d-flex align-items-start mb-3">
                    <div class="symbol symbol-35px me-3">
                        <span class="symbol-label bg-light-warning text-warning fw-bold">#</span>
                    </div>
                    <div class="flex-grow-1">
                        <div class="fw-bold">${d.resi_number} &ndash; ${d.order_id || '-'}</div>
                        <div class="text-muted small">SKU: ${d.sku}</div>
                        <div class="text-muted small">${d.reason || 'Duplikat'}</div>
                    </div>
                </div>
            `);
            shipDupList.innerHTML = items.join('');
        }

        if (shipSkippedList) {
            const skippedEntries = summary.skipped_entries ?? [];
            if (skippedEntries.length === 0) {
                shipSkippedList.innerHTML = '<tr><td colspan="4" class="text-muted text-center">Tidak ada data dilewati.</td></tr>';
            } else {
                shipSkippedList.innerHTML = skippedEntries.map(s => `
                    <tr>
                        <td>${s.resi_number || '-'}</td>
                        <td>${s.order_id || '-'}</td>
                        <td>${s.sku || '-'}</td>
                        <td class="text-end">${s.quantity || '0'}</td>
                    </tr>
                `).join('');
            }
        }
    }

    shipClearBtn?.addEventListener('click', async () => {
        let confirmed = true;
        if (typeof Swal !== 'undefined') {
            const res = await Swal.fire({
                title: 'Hapus semua data kirim?',
                text: 'Tindakan ini akan mengosongkan tabel scan_resi_shipments.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Ya, hapus',
                cancelButtonText: 'Batal',
                buttonsStyling: false,
                customClass: {
                    confirmButton: 'btn btn-danger',
                    cancelButton: 'btn btn-light'
                }
            });
            confirmed = res.isConfirmed;
        } else {
            confirmed = confirm('Hapus semua data kirim?');
        }

        if (!confirmed) return;

        shipClearBtn.disabled = true;
        try {
            const res = await fetch(shipDestroyUrl, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json',
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams({ _method: 'DELETE' }),
            });

            const data = await res.json();
            if (!res.ok) {
                throw new Error(data?.message || 'Gagal menghapus data');
            }

            updateShipSummary({
                total_rows: 0,
                inserted: 0,
                updated: 0,
                duplicate_in_db: 0,
                duplicate_in_file: 0,
                skipped_empty: 0,
                duplicates: [],
            });
            if (shipSummary) shipSummary.style.display = 'flex';
            if (typeof Swal !== 'undefined') {
                Swal.fire('Berhasil', data.message || 'Data dihapus', 'success');
            }
        } catch (err) {
            console.error(err);
            if (typeof Swal !== 'undefined') {
                Swal.fire('Error', err.message || 'Gagal menghapus data', 'error');
            } else {
                alert(err.message || 'Gagal menghapus data');
            }
        } finally {
            shipClearBtn.disabled = false;
        }
    });
});
</script>
@endpush
