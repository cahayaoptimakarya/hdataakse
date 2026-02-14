@extends('layouts.admin')

@section('title', 'Akun Pembayaran')
@section('page_title', 'Akun Pembayaran')

@section('content')
<div class="card mb-6">
    <div class="card-header border-0 pt-6">
        <div class="card-title">
            <div class="d-flex align-items-center position-relative my-1">
                <span class="svg-icon svg-icon-1 position-absolute ms-6">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none">
                        <rect opacity="0.5" x="17.0365" y="15.1223" width="8.15546" height="2" rx="1" transform="rotate(45 17.0365 15.1223)" fill="black" />
                        <path d="M11 19C6.55556 19 3 15.4444 3 11C3 6.55556 6.55556 3 11 3C15.4444 3 19 6.55556 19 11C19 15.4444 15.4444 19 11 19ZM11 5C7.53333 5 5 7.53333 5 11C5 14.4667 7.53333 17 11 17C14.4667 17 17 14.4667 17 11C17 7.53333 14.4667 5 11 5Z" fill="black" />
                    </svg>
                </span>
                <input type="text" class="form-control form-control-solid w-250px ps-14" placeholder="Search akun pembayaran" data-kt-filter="akun-search" />
            </div>
        </div>
        <div class="card-toolbar">
            <div class="d-flex justify-content-end gap-2" data-kt-user-table-toolbar="base">
                <button type="button" class="btn btn-light-primary" data-bs-toggle="modal" data-bs-target="#modal_import_akun" id="btn_open_import_akun">
                    Import Excel
                </button>
                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modal_akun_form" id="btn_open_create_akun">
                    Add Akun Pembayaran
                </button>
            </div>
        </div>
    </div>
    <div class="card-body py-6">
        <div class="table-responsive">
            <table class="table align-middle table-row-dashed fs-6 gy-5" id="akun_table">
                <thead>
                    <tr class="text-start text-gray-400 fw-bolder fs-7 text-uppercase gs-0">
                        <th>ID</th>
                        <th>Nama</th>
                        <th class="text-end">Aksi</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header border-0 pt-6">
        <div class="card-title">
            <div class="d-flex align-items-center position-relative my-1">
                <span class="svg-icon svg-icon-1 position-absolute ms-6">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none">
                        <rect opacity="0.5" x="17.0365" y="15.1223" width="8.15546" height="2" rx="1" transform="rotate(45 17.0365 15.1223)" fill="black" />
                        <path d="M11 19C6.55556 19 3 15.4444 3 11C3 6.55556 6.55556 3 11 3C15.4444 3 19 6.55556 19 11C19 15.4444 15.4444 19 11 19ZM11 5C7.53333 5 5 7.53333 5 11C5 14.4667 7.53333 17 11 17C14.4667 17 17 14.4667 17 11C17 7.53333 14.4667 5 11 5Z" fill="black" />
                    </svg>
                </span>
                <input type="text" class="form-control form-control-solid w-250px ps-14" placeholder="Search sub akun pembayaran" data-kt-filter="sub-akun-search" />
            </div>
        </div>
        <div class="card-toolbar">
            <div class="d-flex justify-content-end" data-kt-user-table-toolbar="base">
                <button type="button" class="btn btn-light-primary me-3" data-kt-menu-trigger="click" data-kt-menu-placement="bottom-end">
                    <span class="svg-icon svg-icon-2">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none">
                            <path d="M19.0759 3H4.72777C3.95892 3 3.47768 3.83148 3.86067 4.49814L8.56967 12.6949C9.17923 13.7559 9.5 14.9582 9.5 16.1819V19.5072C9.5 20.2189 10.2223 20.7028 10.8805 20.432L13.8805 19.1977C14.2553 19.0435 14.5 18.6783 14.5 18.273V13.8372C14.5 12.8089 14.8171 11.8056 15.408 10.964L19.8943 4.57465C20.3596 3.912 19.8856 3 19.0759 3Z" fill="black" />
                        </svg>
                    </span>
                    Filter
                </button>
                <div class="menu menu-sub menu-sub-dropdown w-300px w-md-325px" data-kt-menu="true" data-kt-menu-dismiss="true">
                    <div class="px-7 py-5">
                        <div class="fs-5 text-dark fw-bolder">Filter Options</div>
                    </div>
                    <div class="separator border-gray-200"></div>
                    <div class="px-7 py-5">
                        <div class="mb-10">
                            <label class="form-label fs-6 fw-bold">Akun Pembayaran:</label>
                            <select id="filter_sub_akun_akun" class="form-select form-select-solid fw-bolder" data-placeholder="Select option" data-allow-clear="true">
                                <option value="">Semua</option>
                                @foreach($akunBiaya as $akun)
                                    <option value="{{ $akun->id }}">{{ $akun->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="d-flex justify-content-end">
                            <button type="button" class="btn btn-light btn-active-light-primary me-2" id="filter_sub_akun_reset">Reset</button>
                            <button type="button" class="btn btn-primary" id="filter_sub_akun_apply">Apply</button>
                        </div>
                    </div>
                </div>
                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modal_sub_akun_form" id="btn_open_create_sub_akun">
                    Add Sub Akun Pembayaran
                </button>
            </div>
        </div>
    </div>
    <div class="card-body py-6">
        <div class="table-responsive">
            <table class="table align-middle table-row-dashed fs-6 gy-5" id="sub_akun_table">
                <thead>
                    <tr class="text-start text-gray-400 fw-bolder fs-7 text-uppercase gs-0">
                        <th>ID</th>
                        <th>Nama</th>
                        <th>Akun Pembayaran</th>
                        <th class="text-end">Aksi</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
    </div>
</div>

<!--begin::Modal-->
<div class="modal fade" id="modal_akun_form" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered mw-650px">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="fw-bolder" id="modal_akun_title">Add Akun Pembayaran</h2>
                <div class="btn btn-icon btn-sm btn-active-icon-primary" data-bs-dismiss="modal">
                    <span class="svg-icon svg-icon-1">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none">
                            <rect opacity="0.5" x="6" y="17.3137" width="16" height="2" rx="1" transform="rotate(-45 6 17.3137)" fill="black" />
                            <rect x="7.41422" y="6" width="16" height="2" rx="1" transform="rotate(45 7.41422 6)" fill="black" />
                        </svg>
                    </span>
                </div>
            </div>
            <div class="modal-body scroll-y mx-5 mx-xl-15 my-7">
                <form class="form" id="akun_form">
                    @csrf
                    <input type="hidden" name="akun_id" id="akun_id" />
                    <div class="fv-row mb-7">
                        <label class="required fs-6 fw-bold form-label mb-2">Nama</label>
                        <input type="text" class="form-control form-control-solid" name="name" id="akun_name" required />
                        <div class="invalid-feedback" id="error_akun_name"></div>
                    </div>
                    <div class="text-end pt-3">
                        <button type="button" class="btn btn-light me-3" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary">
                            <span class="indicator-label">Simpan</span>
                            <span class="indicator-progress">Please wait...
                            <span class="spinner-border spinner-border-sm align-middle ms-2"></span></span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
<!--end::Modal-->

<!--begin::Import Modal-->
<div class="modal fade" id="modal_import_akun" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered mw-650px">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="fw-bolder">Import Sub Akun + Akun Pembayaran (Excel)</h2>
                <div class="btn btn-icon btn-sm btn-active-icon-primary" data-bs-dismiss="modal">
                    <span class="svg-icon svg-icon-1">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none">
                            <rect opacity="0.5" x="6" y="17.3137" width="16" height="2" rx="1" transform="rotate(-45 6 17.3137)" fill="black" />
                            <rect x="7.41422" y="6" width="16" height="2" rx="1" transform="rotate(45 7.41422 6)" fill="black" />
                        </svg>
                    </span>
                </div>
            </div>
            <div class="modal-body scroll-y mx-5 mx-xl-15 my-7">
                <div class="mb-6">
                    <div class="fw-bold mb-2">Format Kolom</div>
                    <div class="text-muted fs-7">
                        Kolom A: <strong>Sub Akun Pembayaran</strong>, Kolom B: <strong>Akun Pembayaran</strong>. Baris header opsional.
                    </div>
                </div>
                <div class="mb-7">
                    <label class="required fs-6 fw-bold form-label mb-2">File Excel</label>
                    <input type="file" class="form-control form-control-solid" id="import_akun_file" accept=".xlsx,.xls" />
                    <div class="invalid-feedback d-block" id="error_import_akun_file"></div>
                </div>
                <div class="text-end">
                    <button type="button" class="btn btn-light me-3" data-bs-dismiss="modal">Batal</button>
                    <button type="button" class="btn btn-primary" id="btn_import_akun_submit">Import</button>
                </div>
                <div class="mt-6 border rounded p-4" id="import_akun_summary" style="display:none;">
                    <div class="fw-bold mb-2">Ringkasan Import</div>
                    <div class="d-flex flex-wrap gap-6">
                        <div><span class="text-muted">Akun dibuat:</span> <strong id="import_akun_created">0</strong></div>
                        <div><span class="text-muted">Sub akun dibuat:</span> <strong id="import_sub_akun_created">0</strong></div>
                        <div><span class="text-muted">Akun skip:</span> <strong id="import_akun_skipped">0</strong></div>
                        <div><span class="text-muted">Sub akun skip:</span> <strong id="import_sub_akun_skipped">0</strong></div>
                        <div><span class="text-muted">Baris skip (duplikat/kosong):</span> <strong id="import_akun_rows_skipped">0</strong></div>
                    </div>
                    <div class="mt-4" id="import_akun_skipped_details" style="display:none;">
                        <div class="fw-bold mb-2">Detail Data Skip (max 20)</div>
                        <ul class="mb-0" id="import_akun_skipped_list"></ul>
                    </div>
                    <div class="mt-3" id="import_akun_skipped_download" style="display:none;">
                        <a href="#" class="btn btn-sm btn-light-primary" id="import_akun_download_link" target="_blank" rel="noopener">
                            Download Data Skip
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<!--end::Import Modal-->

<!--begin::Modal-->
<div class="modal fade" id="modal_sub_akun_form" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered mw-650px">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="fw-bolder" id="modal_sub_akun_title">Add Sub Akun Pembayaran</h2>
                <div class="btn btn-icon btn-sm btn-active-icon-primary" data-bs-dismiss="modal">
                    <span class="svg-icon svg-icon-1">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none">
                            <rect opacity="0.5" x="6" y="17.3137" width="16" height="2" rx="1" transform="rotate(-45 6 17.3137)" fill="black" />
                            <rect x="7.41422" y="6" width="16" height="2" rx="1" transform="rotate(45 7.41422 6)" fill="black" />
                        </svg>
                    </span>
                </div>
            </div>
            <div class="modal-body scroll-y mx-5 mx-xl-15 my-7">
                <form class="form" id="sub_akun_form">
                    @csrf
                    <input type="hidden" name="sub_akun_id" id="sub_akun_id" />
                    <div class="fv-row mb-7">
                        <label class="required fs-6 fw-bold form-label mb-2">Akun Pembayaran</label>
                        <select name="akun_biaya_id" id="sub_akun_akun_id" class="form-select form-select-solid" data-control="select2" data-placeholder="Pilih akun pembayaran" required>
                            <option value="">Pilih akun pembayaran</option>
                            @foreach($akunBiaya as $akun)
                                <option value="{{ $akun->id }}">{{ $akun->name }}</option>
                            @endforeach
                        </select>
                        <div class="invalid-feedback" id="error_sub_akun_akun_biaya_id"></div>
                    </div>
                    <div class="fv-row mb-7">
                        <label class="required fs-6 fw-bold form-label mb-2">Nama</label>
                        <input type="text" class="form-control form-control-solid" name="name" id="sub_akun_name" required />
                        <div class="invalid-feedback" id="error_sub_akun_name"></div>
                    </div>
                    <div class="text-end pt-3">
                        <button type="button" class="btn btn-light me-3" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary">
                            <span class="indicator-label">Simpan</span>
                            <span class="indicator-progress">Please wait...
                            <span class="spinner-border spinner-border-sm align-middle ms-2"></span></span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
<!--end::Modal-->
@endsection

@push('scripts')
<script>
    const csrfToken = '{{ csrf_token() }}';
    const akunDataUrl = '{{ route('admin.keuangan.akun-pembayaran.data') }}';
    const akunStoreUrl = '{{ route('admin.keuangan.akun-pembayaran.store') }}';
    const akunUpdateTpl = '{{ route('admin.keuangan.akun-pembayaran.update', ':id') }}';
    const akunDeleteTpl = '{{ route('admin.keuangan.akun-pembayaran.destroy', ':id') }}';
    const importAkunUrl = '{{ route('admin.keuangan.akun-pembayaran.import') }}';

    const subAkunDataUrl = '{{ route('admin.keuangan.sub-akun-pembayaran.data') }}';
    const subAkunStoreUrl = '{{ route('admin.keuangan.sub-akun-pembayaran.store') }}';
    const subAkunUpdateTpl = '{{ route('admin.keuangan.sub-akun-pembayaran.update', ':id') }}';
    const subAkunDeleteTpl = '{{ route('admin.keuangan.sub-akun-pembayaran.destroy', ':id') }}';

    const ensureOption = (selectEl, id, name) => {
        if (!selectEl) return;
        const exists = Array.from(selectEl.options).some(opt => opt.value == id);
        if (!exists) {
            const opt = document.createElement('option');
            opt.value = id;
            opt.textContent = name;
            selectEl.appendChild(opt);
        } else {
            Array.from(selectEl.options).forEach(opt => {
                if (opt.value == id) opt.textContent = name;
            });
        }
    };

    document.addEventListener('DOMContentLoaded', () => {
        const akunTableEl = $('#akun_table');
        const subTableEl = $('#sub_akun_table');
        const akunSearch = document.querySelector('[data-kt-filter="akun-search"]');
        const subSearch = document.querySelector('[data-kt-filter="sub-akun-search"]');
        const filterAkun = document.getElementById('filter_sub_akun_akun');
        const filterApply = document.getElementById('filter_sub_akun_apply');
        const filterReset = document.getElementById('filter_sub_akun_reset');

        const akunForm = document.getElementById('akun_form');
        const akunModalEl = document.getElementById('modal_akun_form');
        const akunModal = akunModalEl ? new bootstrap.Modal(akunModalEl) : null;
        const akunId = document.getElementById('akun_id');
        const akunName = document.getElementById('akun_name');
        const akunTitle = document.getElementById('modal_akun_title');

        const subForm = document.getElementById('sub_akun_form');
        const subModalEl = document.getElementById('modal_sub_akun_form');
        const subModal = subModalEl ? new bootstrap.Modal(subModalEl) : null;
        const subId = document.getElementById('sub_akun_id');
        const subName = document.getElementById('sub_akun_name');
        const subAkunSelect = document.getElementById('sub_akun_akun_id');
        const subTitle = document.getElementById('modal_sub_akun_title');

        const importModalEl = document.getElementById('modal_import_akun');
        const importModal = importModalEl ? new bootstrap.Modal(importModalEl) : null;
        const importFile = document.getElementById('import_akun_file');
        const importError = document.getElementById('error_import_akun_file');
        const importSubmit = document.getElementById('btn_import_akun_submit');
        const importSummary = document.getElementById('import_akun_summary');
        const importAkunCreated = document.getElementById('import_akun_created');
        const importSubAkunCreated = document.getElementById('import_sub_akun_created');
        const importAkunSkipped = document.getElementById('import_akun_skipped');
        const importSubAkunSkipped = document.getElementById('import_sub_akun_skipped');
        const importRowsSkipped = document.getElementById('import_akun_rows_skipped');
        const importSkippedDetails = document.getElementById('import_akun_skipped_details');
        const importSkippedList = document.getElementById('import_akun_skipped_list');
        const importSkippedDownload = document.getElementById('import_akun_skipped_download');
        const importSkippedLink = document.getElementById('import_akun_download_link');

        const select2Safe = (el, placeholder) => {
            if (el && typeof $ !== 'undefined' && $.fn.select2) {
                $(el).select2({ placeholder, allowClear: true, width: '100%' })
                    .on('select2:opening select2:closing select2:close', function(e){ e.stopPropagation(); });
            }
        };

        select2Safe(filterAkun, 'Semua');
        select2Safe(subAkunSelect, 'Pilih akun pembayaran');

        if (!akunTableEl.length || !$.fn.DataTable || !subTableEl.length) {
            console.error('DataTables unavailable');
            return;
        }

        const refreshMenus = () => { if (window.KTMenu) KTMenu.createInstances(); };

        const akunDt = akunTableEl.DataTable({
            processing: true,
            serverSide: true,
            dom: 'rtip',
            order: [[0, 'desc']],
            ajax: {
                url: akunDataUrl,
                dataSrc: 'data',
                data: function(params) {
                    params.q = akunSearch?.value || '';
                }
            },
            columns: [
                { data: 'id' },
                { data: 'name' },
                { data: 'id', orderable:false, searchable:false, className:'text-end', render: (data, type, row)=>{
                    const editItem = `<div class="menu-item px-3"><a href="#" class="menu-link px-3 btn-edit-akun" data-id="${data}" data-name="${row.name}">Edit</a></div>`;
                    const delItem = `<div class="menu-item px-3"><a href="#" class="menu-link px-3 text-danger btn-delete-akun" data-id="${data}">Hapus</a></div>`;
                    return `
                        <div class="text-end">
                            <a href="#" class="btn btn-sm btn-light btn-active-light-primary" data-kt-menu-trigger="click" data-kt-menu-placement="bottom-end">
                                Actions
                                <span class="svg-icon svg-icon-5 m-0">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none">
                                        <path d="M11.4343 12.7344L7.25 8.55005C6.83579 8.13583 6.16421 8.13584 5.75 8.55005C5.33579 8.96426 5.33579 9.63583 5.75 10.05L11.2929 15.5929C11.6834 15.9835 12.3166 15.9835 12.7071 15.5929L18.25 10.05C18.6642 9.63584 18.6642 8.96426 18.25 8.55005C17.8358 8.13584 17.1642 8.13584 16.75 8.55005L12.5657 12.7344C12.2533 13.0468 11.7467 13.0468 11.4343 12.7344Z" fill="black"></path>
                                    </svg>
                                </span>
                            </a>
                            <div class="menu menu-sub menu-sub-dropdown menu-column menu-rounded menu-gray-600 menu-state-bg-light-primary fw-bold fs-7 w-175px py-3" data-kt-menu="true">
                                ${editItem}${delItem}
                            </div>
                        </div>
                    `;
                }}
            ]
        });
        refreshMenus();
        akunDt.on('draw', refreshMenus);

        const subDt = subTableEl.DataTable({
            processing: true,
            serverSide: true,
            dom: 'rtip',
            order: [[0, 'desc']],
            ajax: {
                url: subAkunDataUrl,
                dataSrc: 'data',
                data: function(params) {
                    params.q = subSearch?.value || '';
                    params.akun_biaya_id = filterAkun?.value || '';
                }
            },
            columns: [
                { data: 'id' },
                { data: 'name' },
                { data: 'akun_biaya' },
                { data: 'id', orderable:false, searchable:false, className:'text-end', render: (data, type, row)=>{
                    const editItem = `<div class="menu-item px-3"><a href="#" class="menu-link px-3 btn-edit-sub-akun" data-id="${data}" data-name="${row.name}" data-akun="${row.akun_biaya_id}">Edit</a></div>`;
                    const delItem = `<div class="menu-item px-3"><a href="#" class="menu-link px-3 text-danger btn-delete-sub-akun" data-id="${data}">Hapus</a></div>`;
                    return `
                        <div class="text-end">
                            <a href="#" class="btn btn-sm btn-light btn-active-light-primary" data-kt-menu-trigger="click" data-kt-menu-placement="bottom-end">
                                Actions
                                <span class="svg-icon svg-icon-5 m-0">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none">
                                        <path d="M11.4343 12.7344L7.25 8.55005C6.83579 8.13583 6.16421 8.13584 5.75 8.55005C5.33579 8.96426 5.33579 9.63583 5.75 10.05L11.2929 15.5929C11.6834 15.9835 12.3166 15.9835 12.7071 15.5929L18.25 10.05C18.6642 9.63584 18.6642 8.96426 18.25 8.55005C17.8358 8.13584 17.1642 8.13584 16.75 8.55005L12.5657 12.7344C12.2533 13.0468 11.7467 13.0468 11.4343 12.7344Z" fill="black"></path>
                                    </svg>
                                </span>
                            </a>
                            <div class="menu menu-sub menu-sub-dropdown menu-column menu-rounded menu-gray-600 menu-state-bg-light-primary fw-bold fs-7 w-175px py-3" data-kt-menu="true">
                                ${editItem}${delItem}
                            </div>
                        </div>
                    `;
                }}
            ]
        });
        subDt.on('draw', refreshMenus);

        const reloadAkun = () => akunDt.ajax.reload();
        const reloadSubAkun = () => subDt.ajax.reload();

        akunSearch?.addEventListener('keyup', reloadAkun);
        subSearch?.addEventListener('keyup', reloadSubAkun);
        filterApply?.addEventListener('click', reloadSubAkun);
        filterReset?.addEventListener('click', () => {
            if (filterAkun) {
                filterAkun.value = '';
                if (typeof $ !== 'undefined' && $(filterAkun).data('select2')) {
                    $(filterAkun).val('').trigger('change');
                }
            }
            reloadSubAkun();
        });

        const clearAkunErrors = () => {
            const el = document.getElementById('error_akun_name');
            if (el) el.textContent = '';
        };

        const clearSubErrors = () => {
            const nameEl = document.getElementById('error_sub_akun_name');
            const akunEl = document.getElementById('error_sub_akun_akun_biaya_id');
            if (nameEl) nameEl.textContent = '';
            if (akunEl) akunEl.textContent = '';
        };

        const setSelectValue = (el, value) => {
            if (!el) return;
            el.value = value ?? '';
            if (typeof $ !== 'undefined' && $(el).data('select2')) {
                $(el).val(el.value).trigger('change');
            }
        };

        document.getElementById('btn_open_create_akun')?.addEventListener('click', () => {
            akunForm?.reset();
            if (akunId) akunId.value = '';
            clearAkunErrors();
            if (akunTitle) akunTitle.textContent = 'Add Akun Pembayaran';
        });

        document.getElementById('btn_open_import_akun')?.addEventListener('click', () => {
            if (importFile) importFile.value = '';
            if (importError) importError.textContent = '';
            if (importSummary) importSummary.style.display = 'none';
            if (importSkippedList) importSkippedList.innerHTML = '';
            if (importSkippedDetails) importSkippedDetails.style.display = 'none';
            if (importSkippedDownload) importSkippedDownload.style.display = 'none';
            if (importSkippedLink) importSkippedLink.removeAttribute('href');
        });

        importSubmit?.addEventListener('click', async () => {
            if (importError) importError.textContent = '';
            if (importSummary) importSummary.style.display = 'none';
            if (importSkippedList) importSkippedList.innerHTML = '';
            if (importSkippedDetails) importSkippedDetails.style.display = 'none';

            const file = importFile?.files?.[0];
            if (!file) {
                if (importError) importError.textContent = 'Pilih file Excel terlebih dahulu.';
                return;
            }

            const formData = new FormData();
            formData.append('file', file);

            try {
                const res = await fetch(importAkunUrl, {
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
                    if (importError) importError.textContent = 'Respons server tidak valid.';
                    return;
                }
                if (!res.ok) {
                    if (importError) importError.textContent = json.message || 'Gagal import.';
                    return;
                }

                if (importAkunCreated) importAkunCreated.textContent = json.created_akun ?? 0;
                if (importSubAkunCreated) importSubAkunCreated.textContent = json.created_sub_akun ?? 0;
                if (importAkunSkipped) importAkunSkipped.textContent = json.skipped_akun ?? 0;
                if (importSubAkunSkipped) importSubAkunSkipped.textContent = json.skipped_sub_akun ?? 0;
                if (importRowsSkipped) importRowsSkipped.textContent = json.skipped_rows ?? 0;

                if (Array.isArray(json.skipped_details) && json.skipped_details.length && importSkippedList) {
                    json.skipped_details.forEach((row) => {
                        const li = document.createElement('li');
                        li.textContent = `Baris ${row.row}: ${row.reason} (Sub Akun: ${row.sub_akun || '-'}, Akun: ${row.akun || '-'})`;
                        importSkippedList.appendChild(li);
                    });
                    if (importSkippedDetails) importSkippedDetails.style.display = 'block';
                }

                if (importSummary) importSummary.style.display = 'block';
                if (json.skipped_file_url && importSkippedLink && importSkippedDownload) {
                    importSkippedLink.href = json.skipped_file_url;
                    importSkippedDownload.style.display = 'block';
                }
                if (typeof Swal !== 'undefined') {
                    Swal.fire('Berhasil', json.message || 'Import selesai', 'success');
                }

                reloadAkun();
                reloadSubAkun();
            } catch (err) {
                if (importError) importError.textContent = 'Gagal import.';
                if (typeof Swal !== 'undefined') {
                    Swal.fire('Error', 'Gagal import', 'error');
                }
            }
        });

        akunForm?.addEventListener('submit', async (e) => {
            e.preventDefault();
            clearAkunErrors();
            const id = akunId?.value;
            const url = id ? akunUpdateTpl.replace(':id', id) : akunStoreUrl;
            const formData = new FormData(akunForm);
            if (id) formData.append('_method', 'PUT');
            try {
                const res = await fetch(url, {
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
                    console.error('Invalid JSON', text);
                    if (typeof Swal !== 'undefined') Swal.fire('Error', 'Respons server tidak valid', 'error');
                    return;
                }
                if (!res.ok) {
                    if (json?.errors?.name) {
                        const el = document.getElementById('error_akun_name');
                        if (el) el.textContent = json.errors.name.join(', ');
                    } else if (typeof Swal !== 'undefined') {
                        Swal.fire('Error', json.message || 'Gagal menyimpan akun pembayaran', 'error');
                    }
                    return;
                }
                if (json?.akun_biaya) {
                    ensureOption(subAkunSelect, json.akun_biaya.id, json.akun_biaya.name);
                    ensureOption(filterAkun, json.akun_biaya.id, json.akun_biaya.name);
                }
                if (typeof Swal !== 'undefined') Swal.fire('Berhasil', json.message || 'Berhasil', 'success');
                akunModal?.hide();
                reloadAkun();
                reloadSubAkun();
            } catch (err) {
                console.error(err);
                if (typeof Swal !== 'undefined') Swal.fire('Error', 'Gagal menyimpan akun pembayaran', 'error');
            }
        });

        akunTableEl.on('click', '.btn-edit-akun', function(e) {
            e.preventDefault();
            if (!akunForm) return;
            const id = this.getAttribute('data-id');
            const name = this.getAttribute('data-name');
            if (akunId) akunId.value = id;
            if (akunName) akunName.value = name || '';
            clearAkunErrors();
            if (akunTitle) akunTitle.textContent = 'Edit Akun Pembayaran';
            akunModal?.show();
        });

        akunTableEl.on('click', '.btn-delete-akun', async function(e) {
            e.preventDefault();
            const id = this.getAttribute('data-id');
            let confirmed = true;
            if (typeof Swal !== 'undefined') {
                const res = await Swal.fire({
                    title: 'Apakah Anda yakin?',
                    text: 'Akun pembayaran akan dihapus',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: 'Hapus',
                    cancelButtonText: 'Batal',
                    buttonsStyling: false,
                    customClass: {
                        confirmButton: 'btn btn-danger',
                        cancelButton: 'btn btn-light'
                    }
                });
                confirmed = res.isConfirmed;
            }
            if (!confirmed) return;
            try {
                const res = await fetch(akunDeleteTpl.replace(':id', id), {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': csrfToken,
                        'Content-Type': 'application/x-www-form-urlencoded',
                        'Accept': 'application/json',
                    },
                    body: new URLSearchParams({ _method: 'DELETE' }),
                });
                const text = await res.text();
                let json;
                try { json = JSON.parse(text); } catch (err) {
                    console.error('Invalid JSON', text);
                    if (typeof Swal !== 'undefined') Swal.fire('Error', 'Respons server tidak valid', 'error');
                    return;
                }
                if (!res.ok) {
                    if (typeof Swal !== 'undefined') Swal.fire('Error', json.message || 'Gagal menghapus akun pembayaran', 'error');
                    return;
                }
                if (typeof Swal !== 'undefined') Swal.fire('Berhasil', json.message || 'Berhasil', 'success');
                reloadAkun();
                reloadSubAkun();
            } catch (err) {
                console.error(err);
                if (typeof Swal !== 'undefined') Swal.fire('Error', 'Gagal menghapus akun pembayaran', 'error');
            }
        });

        document.getElementById('btn_open_create_sub_akun')?.addEventListener('click', () => {
            subForm?.reset();
            if (subId) subId.value = '';
            setSelectValue(subAkunSelect, '');
            clearSubErrors();
            if (subTitle) subTitle.textContent = 'Add Sub Akun Pembayaran';
        });

        subForm?.addEventListener('submit', async (e) => {
            e.preventDefault();
            clearSubErrors();
            const id = subId?.value;
            const url = id ? subAkunUpdateTpl.replace(':id', id) : subAkunStoreUrl;
            const formData = new FormData(subForm);
            if (id) formData.append('_method', 'PUT');
            try {
                const res = await fetch(url, {
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
                    console.error('Invalid JSON', text);
                    if (typeof Swal !== 'undefined') Swal.fire('Error', 'Respons server tidak valid', 'error');
                    return;
                }
                if (!res.ok) {
                    if (json?.errors) {
                        if (json.errors.name) {
                            const el = document.getElementById('error_sub_akun_name');
                            if (el) el.textContent = json.errors.name.join(', ');
                        }
                        if (json.errors.akun_biaya_id) {
                            const el = document.getElementById('error_sub_akun_akun_biaya_id');
                            if (el) el.textContent = json.errors.akun_biaya_id.join(', ');
                        }
                    } else if (typeof Swal !== 'undefined') {
                        Swal.fire('Error', json.message || 'Gagal menyimpan sub akun pembayaran', 'error');
                    }
                    return;
                }
                if (typeof Swal !== 'undefined') Swal.fire('Berhasil', json.message || 'Berhasil', 'success');
                subModal?.hide();
                reloadSubAkun();
            } catch (err) {
                console.error(err);
                if (typeof Swal !== 'undefined') Swal.fire('Error', 'Gagal menyimpan sub akun pembayaran', 'error');
            }
        });

        subTableEl.on('click', '.btn-edit-sub-akun', function(e) {
            e.preventDefault();
            if (!subForm) return;
            const id = this.getAttribute('data-id');
            const name = this.getAttribute('data-name');
            const akunId = this.getAttribute('data-akun');
            if (subId) subId.value = id;
            if (subName) subName.value = name || '';
            setSelectValue(subAkunSelect, akunId || '');
            clearSubErrors();
            if (subTitle) subTitle.textContent = 'Edit Sub Akun Pembayaran';
            subModal?.show();
        });

        subTableEl.on('click', '.btn-delete-sub-akun', async function(e) {
            e.preventDefault();
            const id = this.getAttribute('data-id');
            let confirmed = true;
            if (typeof Swal !== 'undefined') {
                const res = await Swal.fire({
                    title: 'Apakah Anda yakin?',
                    text: 'Sub akun pembayaran akan dihapus',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: 'Hapus',
                    cancelButtonText: 'Batal',
                    buttonsStyling: false,
                    customClass: {
                        confirmButton: 'btn btn-danger',
                        cancelButton: 'btn btn-light'
                    }
                });
                confirmed = res.isConfirmed;
            }
            if (!confirmed) return;
            try {
                const res = await fetch(subAkunDeleteTpl.replace(':id', id), {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': csrfToken,
                        'Content-Type': 'application/x-www-form-urlencoded',
                        'Accept': 'application/json',
                    },
                    body: new URLSearchParams({ _method: 'DELETE' }),
                });
                const text = await res.text();
                let json;
                try { json = JSON.parse(text); } catch (err) {
                    console.error('Invalid JSON', text);
                    if (typeof Swal !== 'undefined') Swal.fire('Error', 'Respons server tidak valid', 'error');
                    return;
                }
                if (!res.ok) {
                    if (typeof Swal !== 'undefined') Swal.fire('Error', json.message || 'Gagal menghapus sub akun pembayaran', 'error');
                    return;
                }
                if (typeof Swal !== 'undefined') Swal.fire('Berhasil', json.message || 'Berhasil', 'success');
                reloadSubAkun();
            } catch (err) {
                console.error(err);
                if (typeof Swal !== 'undefined') Swal.fire('Error', 'Gagal menghapus sub akun pembayaran', 'error');
            }
        });
    });
</script>
@endpush

@include('layouts.partials.form-submit-confirmation')
