@extends('layouts.admin')

@section('title', 'Import Jurnal Umum')
@section('page_title', 'Import Jurnal Umum')

@section('content')
<div class="card">
    <div class="card-header border-0 pt-6">
        <div class="card-title">
            <h2 class="fw-bolder mb-0">Import Jurnal Umum (Excel)</h2>
        </div>
    </div>
    <div class="card-body pt-0">
        <form id="jurnal_import_form" class="form">
            @csrf
            <div class="mb-10">
                <label for="jurnal_import_file" class="required form-label fw-bold">File Excel</label>
                <input type="file" name="file" id="jurnal_import_file" class="form-control form-control-solid" accept=".xlsx,.xls,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet,application/vnd.ms-excel" required>
                <div class="text-danger small mt-2" id="jurnal_import_error"></div>
                <div class="form-text mt-2">
                    Header wajib: <code>keterangan</code>, <code>toko</code>, <code>kategori</code>, <code>debet</code>, <code>kredit</code>.
                </div>
            </div>
            <div class="mb-10">
                <div class="fw-bold mb-2">Syarat Import</div>
                <ul class="text-muted fs-7 mb-0">
                    <li>Format file Excel (.xlsx/.xls), ukuran maksimal 5 MB.</li>
                    <li>Header berada di baris pertama.</li>
                    <li><code>toko</code> akan dicocokkan ke tabel <code>sub_divisions</code> (kolom <code>name</code>).</li>
                    <li><code>kategori</code> akan dicocokkan ke tabel <code>sub_akun_biaya</code> (kolom <code>name</code>).</li>
                    <li><code>debet</code> dan <code>kredit</code> bernilai angka >= 0. Boleh pakai pemisah ribuan.</li>
                    <li>Baris kosong akan diabaikan. Maksimal 20 error akan ditampilkan pada ringkasan.</li>
                    <li>Jika ada error, sistem akan menyediakan file Excel berisi semua baris error untuk diunduh.</li>
                </ul>
            </div>
            <div class="d-flex align-items-center gap-3">
                <button type="submit" class="btn btn-primary" id="btn_import_submit">
                    <span class="indicator-label">Mulai Import</span>
                    <span class="indicator-progress">Please wait...
                        <span class="spinner-border spinner-border-sm align-middle ms-2"></span>
                    </span>
                </button>
            </div>
        </form>

        <div class="row mt-10" id="import_summary" style="display:none;">
            <div class="col-md-6 col-lg-4">
                <div class="border rounded p-4">
                    <div class="fw-bold">Ringkasan Import</div>
                    <div class="d-flex justify-content-between mt-3">
                        <span>Created</span>
                        <span id="import_created">0</span>
                    </div>
                    <div class="d-flex justify-content-between mt-2">
                        <span>Skipped</span>
                        <span id="import_skipped">0</span>
                    </div>
                </div>
            </div>
            <div class="col-lg-8 mt-6 mt-lg-0">
                <div class="border rounded p-4">
                    <div class="fw-bold mb-3">Error (max 20)</div>
                    <ul class="mb-0" id="import_errors"></ul>
                    <div class="mt-4" id="error_file_wrapper" style="display:none;">
                        <a href="#" class="btn btn-light-danger" id="error_file_link" download>Download File Error</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    const importUrl = '{{ route('admin.keuangan.jurnal-umum.store') }}';
    const csrfToken = '{{ csrf_token() }}';

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

        form?.addEventListener('submit', async (e) => {
            e.preventDefault();
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
                            ? 'Semua data tidak dimasukkan ke database karena ada error.'
                            : 'Sebagian data gagal diimport. Download file error untuk detail.';
                        Swal.fire({
                            title: 'Import selesai dengan error',
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
                        Swal.fire('Berhasil', json.message || 'Import selesai', 'success');
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
    });
</script>
@endpush
