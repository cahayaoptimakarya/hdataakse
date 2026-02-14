@extends('layouts.admin')

@section('title', 'Divisi')
@section('page_title', 'Divisi')

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
                <input type="text" class="form-control form-control-solid w-250px ps-14" placeholder="Search divisi" data-kt-filter="division-search" />
            </div>
        </div>
        <div class="card-toolbar">
            <div class="d-flex justify-content-end gap-2" data-kt-user-table-toolbar="base">
                <button type="button" class="btn btn-light-primary" data-bs-toggle="modal" data-bs-target="#modal_import_divisi" id="btn_open_import_divisi">
                    Import Excel
                </button>
                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modal_division_form" id="btn_open_create_division">
                    Add Divisi
                </button>
            </div>
        </div>
    </div>
    <div class="card-body py-6">
        <div class="table-responsive">
            <table class="table align-middle table-row-dashed fs-6 gy-5" id="divisions_table">
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
                <input type="text" class="form-control form-control-solid w-250px ps-14" placeholder="Search sub divisi" data-kt-filter="sub-division-search" />
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
                            <label class="form-label fs-6 fw-bold">Divisi:</label>
                            <select id="filter_sub_division_division" class="form-select form-select-solid fw-bolder" data-placeholder="Select option" data-allow-clear="true">
                                <option value="">Semua</option>
                                @foreach($divisions as $division)
                                    <option value="{{ $division->id }}">{{ $division->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="d-flex justify-content-end">
                            <button type="button" class="btn btn-light btn-active-light-primary me-2" id="filter_sub_divisions_reset">Reset</button>
                            <button type="button" class="btn btn-primary" id="filter_sub_divisions_apply">Apply</button>
                        </div>
                    </div>
                </div>
                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modal_sub_division_form" id="btn_open_create_sub_division">
                    Add Sub Divisi
                </button>
            </div>
        </div>
    </div>
    <div class="card-body py-6">
        <div class="table-responsive">
            <table class="table align-middle table-row-dashed fs-6 gy-5" id="sub_divisions_table">
                <thead>
                    <tr class="text-start text-gray-400 fw-bolder fs-7 text-uppercase gs-0">
                        <th>ID</th>
                        <th>Nama</th>
                        <th>Divisi</th>
                        <th class="text-end">Aksi</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
    </div>
</div>

<!--begin::Modal-->
<div class="modal fade" id="modal_division_form" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered mw-650px">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="fw-bolder" id="modal_division_title">Add Divisi</h2>
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
                <form class="form" id="division_form">
                    @csrf
                    <input type="hidden" name="division_id" id="division_id" />
                    <div class="fv-row mb-7">
                        <label class="required fs-6 fw-bold form-label mb-2">Nama</label>
                        <input type="text" class="form-control form-control-solid" name="name" id="division_name" required />
                        <div class="invalid-feedback" id="error_division_name"></div>
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
<div class="modal fade" id="modal_import_divisi" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered mw-650px">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="fw-bolder">Import Sub Divisi + Divisi (Excel)</h2>
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
                        Kolom A: <strong>Sub Divisi</strong>, Kolom B: <strong>Divisi</strong>. Baris header opsional.
                    </div>
                </div>
                <div class="mb-7">
                    <label class="required fs-6 fw-bold form-label mb-2">File Excel</label>
                    <input type="file" class="form-control form-control-solid" id="import_divisi_file" accept=".xlsx,.xls" />
                    <div class="invalid-feedback d-block" id="error_import_divisi_file"></div>
                </div>
                <div class="text-end">
                    <button type="button" class="btn btn-light me-3" data-bs-dismiss="modal">Batal</button>
                    <button type="button" class="btn btn-primary" id="btn_import_divisi_submit">Import</button>
                </div>
                <div class="mt-6 border rounded p-4" id="import_divisi_summary" style="display:none;">
                    <div class="fw-bold mb-2">Ringkasan Import</div>
                    <div class="d-flex flex-wrap gap-6">
                        <div><span class="text-muted">Divisi dibuat:</span> <strong id="import_div_created">0</strong></div>
                        <div><span class="text-muted">Sub divisi dibuat:</span> <strong id="import_sub_created">0</strong></div>
                        <div><span class="text-muted">Divisi skip:</span> <strong id="import_div_skipped">0</strong></div>
                        <div><span class="text-muted">Sub divisi skip:</span> <strong id="import_sub_skipped">0</strong></div>
                        <div><span class="text-muted">Baris skip (duplikat/kosong):</span> <strong id="import_rows_skipped">0</strong></div>
                    </div>
                    <div class="mt-4" id="import_divisi_skipped_details" style="display:none;">
                        <div class="fw-bold mb-2">Detail Data Skip (max 20)</div>
                        <ul class="mb-0" id="import_divisi_skipped_list"></ul>
                    </div>
                    <div class="mt-3" id="import_divisi_skipped_download" style="display:none;">
                        <a href="#" class="btn btn-sm btn-light-primary" id="import_divisi_download_link" target="_blank" rel="noopener">
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
<div class="modal fade" id="modal_sub_division_form" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered mw-650px">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="fw-bolder" id="modal_sub_division_title">Add Sub Divisi</h2>
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
                <form class="form" id="sub_division_form">
                    @csrf
                    <input type="hidden" name="sub_division_id" id="sub_division_id" />
                    <div class="fv-row mb-7">
                        <label class="required fs-6 fw-bold form-label mb-2">Divisi</label>
                        <select name="division_id" id="sub_division_division_id" class="form-select form-select-solid" data-control="select2" data-placeholder="Pilih divisi" required>
                            <option value="">Pilih divisi</option>
                            @foreach($divisions as $division)
                                <option value="{{ $division->id }}">{{ $division->name }}</option>
                            @endforeach
                        </select>
                        <div class="invalid-feedback" id="error_sub_division_division_id"></div>
                    </div>
                    <div class="fv-row mb-7">
                        <label class="required fs-6 fw-bold form-label mb-2">Nama</label>
                        <input type="text" class="form-control form-control-solid" name="name" id="sub_division_name" required />
                        <div class="invalid-feedback" id="error_sub_division_name"></div>
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
    const divisionDataUrl = '{{ route('admin.keuangan.divisi.data') }}';
    const divisionStoreUrl = '{{ route('admin.keuangan.divisi.store') }}';
    const divisionUpdateTpl = '{{ route('admin.keuangan.divisi.update', ':id') }}';
    const divisionDeleteTpl = '{{ route('admin.keuangan.divisi.destroy', ':id') }}';

    const importDivisiUrl = '{{ route('admin.keuangan.divisi.import') }}';
    const subDivisionDataUrl = '{{ route('admin.keuangan.sub-divisi.data') }}';
    const subDivisionStoreUrl = '{{ route('admin.keuangan.sub-divisi.store') }}';
    const subDivisionUpdateTpl = '{{ route('admin.keuangan.sub-divisi.update', ':id') }}';
    const subDivisionDeleteTpl = '{{ route('admin.keuangan.sub-divisi.destroy', ':id') }}';

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
        const divisionTableEl = $('#divisions_table');
        const subTableEl = $('#sub_divisions_table');
        const divisionSearch = document.querySelector('[data-kt-filter="division-search"]');
        const subSearch = document.querySelector('[data-kt-filter="sub-division-search"]');
        const filterDivision = document.getElementById('filter_sub_division_division');
        const filterApply = document.getElementById('filter_sub_divisions_apply');
        const filterReset = document.getElementById('filter_sub_divisions_reset');

        const divisionForm = document.getElementById('division_form');
        const divisionModalEl = document.getElementById('modal_division_form');
        const divisionModal = divisionModalEl ? new bootstrap.Modal(divisionModalEl) : null;
        const divisionId = document.getElementById('division_id');
        const divisionName = document.getElementById('division_name');
        const divisionTitle = document.getElementById('modal_division_title');

        const subForm = document.getElementById('sub_division_form');
        const subModalEl = document.getElementById('modal_sub_division_form');
        const subModal = subModalEl ? new bootstrap.Modal(subModalEl) : null;
        const subId = document.getElementById('sub_division_id');
        const subName = document.getElementById('sub_division_name');
        const subDivisionSelect = document.getElementById('sub_division_division_id');
        const subTitle = document.getElementById('modal_sub_division_title');

        const importModalEl = document.getElementById('modal_import_divisi');
        const importModal = importModalEl ? new bootstrap.Modal(importModalEl) : null;
        const importFile = document.getElementById('import_divisi_file');
        const importError = document.getElementById('error_import_divisi_file');
        const importSubmit = document.getElementById('btn_import_divisi_submit');
        const importSummary = document.getElementById('import_divisi_summary');
        const importDivCreated = document.getElementById('import_div_created');
        const importSubCreated = document.getElementById('import_sub_created');
        const importDivSkipped = document.getElementById('import_div_skipped');
        const importSubSkipped = document.getElementById('import_sub_skipped');
        const importRowsSkipped = document.getElementById('import_rows_skipped');
        const importSkippedDetails = document.getElementById('import_divisi_skipped_details');
        const importSkippedList = document.getElementById('import_divisi_skipped_list');
        const importSkippedDownload = document.getElementById('import_divisi_skipped_download');
        const importSkippedLink = document.getElementById('import_divisi_download_link');

        const select2Safe = (el, placeholder) => {
            if (el && typeof $ !== 'undefined' && $.fn.select2) {
                $(el).select2({ placeholder, allowClear: true, width: '100%' })
                    .on('select2:opening select2:closing select2:close', function(e){ e.stopPropagation(); });
            }
        };

        select2Safe(filterDivision, 'Semua');
        select2Safe(subDivisionSelect, 'Pilih divisi');

        if (!divisionTableEl.length || !$.fn.DataTable || !subTableEl.length) {
            console.error('DataTables unavailable');
            return;
        }

        const refreshMenus = () => { if (window.KTMenu) KTMenu.createInstances(); };

        const divisionDt = divisionTableEl.DataTable({
            processing: true,
            serverSide: true,
            dom: 'rtip',
            order: [[0, 'desc']],
            ajax: {
                url: divisionDataUrl,
                dataSrc: 'data',
                data: function(params) {
                    params.q = divisionSearch?.value || '';
                }
            },
            columns: [
                { data: 'id' },
                { data: 'name' },
                { data: 'id', orderable:false, searchable:false, className:'text-end', render: (data, type, row)=>{
                    const editItem = `<div class="menu-item px-3"><a href="#" class="menu-link px-3 btn-edit-division" data-id="${data}" data-name="${row.name}">Edit</a></div>`;
                    const delItem = `<div class="menu-item px-3"><a href="#" class="menu-link px-3 text-danger btn-delete-division" data-id="${data}">Hapus</a></div>`;
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
        divisionDt.on('draw', refreshMenus);

        const subDt = subTableEl.DataTable({
            processing: true,
            serverSide: true,
            dom: 'rtip',
            order: [[0, 'desc']],
            ajax: {
                url: subDivisionDataUrl,
                dataSrc: 'data',
                data: function(params) {
                    params.q = subSearch?.value || '';
                    params.division_id = filterDivision?.value || '';
                }
            },
            columns: [
                { data: 'id' },
                { data: 'name' },
                { data: 'division' },
                { data: 'id', orderable:false, searchable:false, className:'text-end', render: (data, type, row)=>{
                    const editItem = `<div class="menu-item px-3"><a href="#" class="menu-link px-3 btn-edit-sub-division" data-id="${data}" data-name="${row.name}" data-division="${row.division_id}">Edit</a></div>`;
                    const delItem = `<div class="menu-item px-3"><a href="#" class="menu-link px-3 text-danger btn-delete-sub-division" data-id="${data}">Hapus</a></div>`;
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

        const reloadDivisions = () => divisionDt.ajax.reload();
        const reloadSubDivisions = () => subDt.ajax.reload();

        divisionSearch?.addEventListener('keyup', reloadDivisions);
        subSearch?.addEventListener('keyup', reloadSubDivisions);
        filterApply?.addEventListener('click', reloadSubDivisions);
        filterReset?.addEventListener('click', () => {
            if (filterDivision) {
                filterDivision.value = '';
                if (typeof $ !== 'undefined' && $(filterDivision).data('select2')) {
                    $(filterDivision).val('').trigger('change');
                }
            }
            reloadSubDivisions();
        });

        const clearDivisionErrors = () => {
            const el = document.getElementById('error_division_name');
            if (el) el.textContent = '';
        };

        const clearSubErrors = () => {
            const nameEl = document.getElementById('error_sub_division_name');
            const divEl = document.getElementById('error_sub_division_division_id');
            if (nameEl) nameEl.textContent = '';
            if (divEl) divEl.textContent = '';
        };

        const setSelectValue = (el, value) => {
            if (!el) return;
            el.value = value ?? '';
            if (typeof $ !== 'undefined' && $(el).data('select2')) {
                $(el).val(el.value).trigger('change');
            }
        };

        document.getElementById('btn_open_create_division')?.addEventListener('click', () => {
            divisionForm?.reset();
            if (divisionId) divisionId.value = '';
            clearDivisionErrors();
            if (divisionTitle) divisionTitle.textContent = 'Add Divisi';
        });

        document.getElementById('btn_open_import_divisi')?.addEventListener('click', () => {
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
                const res = await fetch(importDivisiUrl, {
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

                if (importDivCreated) importDivCreated.textContent = json.created_divisi ?? 0;
                if (importSubCreated) importSubCreated.textContent = json.created_sub_divisi ?? 0;
                if (importDivSkipped) importDivSkipped.textContent = json.skipped_divisi ?? 0;
                if (importSubSkipped) importSubSkipped.textContent = json.skipped_sub_divisi ?? 0;
                if (importRowsSkipped) importRowsSkipped.textContent = json.skipped_rows ?? 0;

                if (Array.isArray(json.skipped_details) && json.skipped_details.length && importSkippedList) {
                    json.skipped_details.forEach((row) => {
                        const li = document.createElement('li');
                        li.textContent = `Baris ${row.row}: ${row.reason} (Sub Divisi: ${row.sub_divisi || '-'}, Divisi: ${row.divisi || '-'})`;
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

                reloadDivisions();
                reloadSubDivisions();
            } catch (err) {
                if (importError) importError.textContent = 'Gagal import.';
                if (typeof Swal !== 'undefined') {
                    Swal.fire('Error', 'Gagal import', 'error');
                }
            }
        });

        divisionForm?.addEventListener('submit', async (e) => {
            e.preventDefault();
            clearDivisionErrors();
            const id = divisionId?.value;
            const url = id ? divisionUpdateTpl.replace(':id', id) : divisionStoreUrl;
            const formData = new FormData(divisionForm);
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
                        const el = document.getElementById('error_division_name');
                        if (el) el.textContent = json.errors.name.join(', ');
                    } else if (typeof Swal !== 'undefined') {
                        Swal.fire('Error', json.message || 'Gagal menyimpan divisi', 'error');
                    }
                    return;
                }
                if (json?.division) {
                    ensureOption(subDivisionSelect, json.division.id, json.division.name);
                    ensureOption(filterDivision, json.division.id, json.division.name);
                }
                if (typeof Swal !== 'undefined') Swal.fire('Berhasil', json.message || 'Berhasil', 'success');
                divisionModal?.hide();
                reloadDivisions();
                reloadSubDivisions();
            } catch (err) {
                console.error(err);
                if (typeof Swal !== 'undefined') Swal.fire('Error', 'Gagal menyimpan divisi', 'error');
            }
        });

        divisionTableEl.on('click', '.btn-edit-division', function(e) {
            e.preventDefault();
            if (!divisionForm) return;
            const id = this.getAttribute('data-id');
            const name = this.getAttribute('data-name');
            if (divisionId) divisionId.value = id;
            if (divisionName) divisionName.value = name || '';
            clearDivisionErrors();
            if (divisionTitle) divisionTitle.textContent = 'Edit Divisi';
            divisionModal?.show();
        });

        divisionTableEl.on('click', '.btn-delete-division', async function(e) {
            e.preventDefault();
            const id = this.getAttribute('data-id');
            let confirmed = true;
            if (typeof Swal !== 'undefined') {
                const res = await Swal.fire({
                    title: 'Apakah Anda yakin?',
                    text: 'Divisi akan dihapus',
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
                const res = await fetch(divisionDeleteTpl.replace(':id', id), {
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
                    if (typeof Swal !== 'undefined') Swal.fire('Error', json.message || 'Gagal menghapus divisi', 'error');
                    return;
                }
                if (typeof Swal !== 'undefined') Swal.fire('Berhasil', json.message || 'Berhasil', 'success');
                reloadDivisions();
                reloadSubDivisions();
            } catch (err) {
                console.error(err);
                if (typeof Swal !== 'undefined') Swal.fire('Error', 'Gagal menghapus divisi', 'error');
            }
        });

        document.getElementById('btn_open_create_sub_division')?.addEventListener('click', () => {
            subForm?.reset();
            if (subId) subId.value = '';
            setSelectValue(subDivisionSelect, '');
            clearSubErrors();
            if (subTitle) subTitle.textContent = 'Add Sub Divisi';
        });

        subForm?.addEventListener('submit', async (e) => {
            e.preventDefault();
            clearSubErrors();
            const id = subId?.value;
            const url = id ? subDivisionUpdateTpl.replace(':id', id) : subDivisionStoreUrl;
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
                            const el = document.getElementById('error_sub_division_name');
                            if (el) el.textContent = json.errors.name.join(', ');
                        }
                        if (json.errors.division_id) {
                            const el = document.getElementById('error_sub_division_division_id');
                            if (el) el.textContent = json.errors.division_id.join(', ');
                        }
                    } else if (typeof Swal !== 'undefined') {
                        Swal.fire('Error', json.message || 'Gagal menyimpan sub divisi', 'error');
                    }
                    return;
                }
                if (typeof Swal !== 'undefined') Swal.fire('Berhasil', json.message || 'Berhasil', 'success');
                subModal?.hide();
                reloadSubDivisions();
            } catch (err) {
                console.error(err);
                if (typeof Swal !== 'undefined') Swal.fire('Error', 'Gagal menyimpan sub divisi', 'error');
            }
        });

        subTableEl.on('click', '.btn-edit-sub-division', function(e) {
            e.preventDefault();
            if (!subForm) return;
            const id = this.getAttribute('data-id');
            const name = this.getAttribute('data-name');
            const divisionId = this.getAttribute('data-division');
            if (subId) subId.value = id;
            if (subName) subName.value = name || '';
            setSelectValue(subDivisionSelect, divisionId || '');
            clearSubErrors();
            if (subTitle) subTitle.textContent = 'Edit Sub Divisi';
            subModal?.show();
        });

        subTableEl.on('click', '.btn-delete-sub-division', async function(e) {
            e.preventDefault();
            const id = this.getAttribute('data-id');
            let confirmed = true;
            if (typeof Swal !== 'undefined') {
                const res = await Swal.fire({
                    title: 'Apakah Anda yakin?',
                    text: 'Sub divisi akan dihapus',
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
                const res = await fetch(subDivisionDeleteTpl.replace(':id', id), {
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
                    if (typeof Swal !== 'undefined') Swal.fire('Error', json.message || 'Gagal menghapus sub divisi', 'error');
                    return;
                }
                if (typeof Swal !== 'undefined') Swal.fire('Berhasil', json.message || 'Berhasil', 'success');
                reloadSubDivisions();
            } catch (err) {
                console.error(err);
                if (typeof Swal !== 'undefined') Swal.fire('Error', 'Gagal menghapus sub divisi', 'error');
            }
        });
    });
</script>
@endpush

@include('layouts.partials.form-submit-confirmation')
