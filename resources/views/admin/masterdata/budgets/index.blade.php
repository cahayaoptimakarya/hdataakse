@extends('layouts.admin')

@section('title', 'Budgets')
@section('page_title', 'Budgets')

@section('content')
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
                <input type="text" class="form-control form-control-solid w-250px ps-14" placeholder="Search budgets" data-kt-filter="search" />
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
                            <select id="filter_budget_division" class="form-select form-select-solid fw-bolder" data-placeholder="Select option" data-allow-clear="true">
                                <option value="">Semua</option>
                                @foreach($divisions as $division)
                                    <option value="{{ $division->id }}">{{ $division->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-10">
                            <label class="form-label fs-6 fw-bold">Akun Biaya:</label>
                            <select id="filter_budget_akun" class="form-select form-select-solid fw-bolder" data-placeholder="Select option" data-allow-clear="true">
                                <option value="">Semua</option>
                                @foreach($akunBiaya as $akun)
                                    <option value="{{ $akun->id }}">{{ $akun->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="d-flex justify-content-end">
                            <button type="button" class="btn btn-light btn-active-light-primary me-2" id="filter_budgets_reset">Reset</button>
                            <button type="button" class="btn btn-primary" id="filter_budgets_apply">Apply</button>
                        </div>
                    </div>
                </div>
                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modal_budget_form" id="btn_open_create_budget">
                    Add Budget
                </button>
            </div>
        </div>
    </div>
    <div class="card-body py-6">
        <div class="table-responsive">
            <table class="table align-middle table-row-dashed fs-6 gy-5" id="budgets_table">
                <thead>
                    <tr class="text-start text-gray-400 fw-bolder fs-7 text-uppercase gs-0">
                        <th>ID</th>
                        <th>Divisi</th>
                        <th>Akun Biaya</th>
                        <th>Jumlah</th>
                        <th class="text-end">Aksi</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
    </div>
</div>

<!--begin::Modal-->
<div class="modal fade" id="modal_budget_form" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered mw-650px">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="fw-bolder" id="modal_budget_title">Add Budget</h2>
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
                <form class="form" id="budget_form">
                    @csrf
                    <input type="hidden" name="budget_id" id="budget_id" />
                    <div class="fv-row mb-7">
                        <label class="required fs-6 fw-bold form-label mb-2">Divisi</label>
                        <select name="division_id" id="budget_division_id" class="form-select form-select-solid" data-control="select2" data-placeholder="Pilih divisi" required>
                            <option value="">Pilih divisi</option>
                            @foreach($divisions as $division)
                                <option value="{{ $division->id }}">{{ $division->name }}</option>
                            @endforeach
                        </select>
                        <div class="invalid-feedback" id="error_division_id"></div>
                    </div>
                    <div class="fv-row mb-7">
                        <label class="required fs-6 fw-bold form-label mb-2">Akun Biaya</label>
                        <select name="akun_biaya_id" id="budget_akun_id" class="form-select form-select-solid" data-control="select2" data-placeholder="Pilih akun biaya" required>
                            <option value="">Pilih akun biaya</option>
                            @foreach($akunBiaya as $akun)
                                <option value="{{ $akun->id }}">{{ $akun->name }}</option>
                            @endforeach
                        </select>
                        <div class="invalid-feedback" id="error_akun_biaya_id"></div>
                    </div>
                    <div class="fv-row mb-7">
                        <label class="required fs-6 fw-bold form-label mb-2">Jumlah</label>
                        <input type="number" class="form-control form-control-solid" name="amount" id="budget_amount" min="0" step="0.01" required />
                        <div class="invalid-feedback" id="error_amount"></div>
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
    const dataUrl   = '{{ route('admin.masterdata.budgets.data') }}';
    const storeUrl  = '{{ route('admin.masterdata.budgets.store') }}';
    const updateTpl = '{{ route('admin.masterdata.budgets.update', ':id') }}';
    const deleteTpl = '{{ route('admin.masterdata.budgets.destroy', ':id') }}';

    const formatAmount = (value) => {
        const num = Number(value ?? 0);
        return `Rp ${new Intl.NumberFormat('id-ID').format(isNaN(num) ? 0 : num)}`;
    };

    document.addEventListener('DOMContentLoaded', () => {
        const tableEl = $('#budgets_table');
        const searchInput = document.querySelector('[data-kt-filter="search"]');
        const applyBtn = document.getElementById('filter_budgets_apply');
        const resetBtn = document.getElementById('filter_budgets_reset');
        const divisionFilter = document.getElementById('filter_budget_division');
        const akunFilter = document.getElementById('filter_budget_akun');
        const form = document.getElementById('budget_form');
        const modalEl = document.getElementById('modal_budget_form');
        const modal = modalEl ? new bootstrap.Modal(modalEl) : null;
        const formDivision = document.getElementById('budget_division_id');
        const formAkun = document.getElementById('budget_akun_id');
        const formAmount = document.getElementById('budget_amount');
        const formId = document.getElementById('budget_id');
        const titleEl = document.getElementById('modal_budget_title');

        const select2Safe = (el, placeholder) => {
            if (el && typeof $ !== 'undefined' && $.fn.select2) {
                $(el).select2({ placeholder, allowClear: true, width: '100%' })
                    .on('select2:opening select2:closing select2:close', function(e){ e.stopPropagation(); });
            }
        };

        select2Safe(divisionFilter, 'Semua');
        select2Safe(akunFilter, 'Semua');
        select2Safe(formDivision, 'Pilih divisi');
        select2Safe(formAkun, 'Pilih akun biaya');

        if (!tableEl.length || !$.fn.DataTable) {
            console.error('DataTables unavailable');
            return;
        }

        const refreshMenus = () => { if (window.KTMenu) KTMenu.createInstances(); };

        const dt = tableEl.DataTable({
            processing: true,
            serverSide: true,
            dom: 'rtip',
            order: [[0, 'desc']],
            ajax: {
                url: dataUrl,
                dataSrc: 'data',
                data: function(params) {
                    params.q = searchInput?.value || '';
                    params.division_id = divisionFilter?.value || '';
                    params.akun_biaya_id = akunFilter?.value || '';
                }
            },
            columns: [
                { data: 'id' },
                { data: 'division' },
                { data: 'akun_biaya' },
                { data: 'amount', render: (data) => formatAmount(data) },
                { data: 'id', orderable:false, searchable:false, className:'text-end', render: (data, type, row)=>{
                    const editItem = `<div class="menu-item px-3"><a href="#" class="menu-link px-3 btn-edit" data-id="${data}" data-division="${row.division_id}" data-akun="${row.akun_biaya_id}" data-amount="${row.amount}">Edit</a></div>`;
                    const delItem = `<div class="menu-item px-3"><a href="#" class="menu-link px-3 text-danger btn-delete" data-id="${data}">Hapus</a></div>`;
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
        dt.on('draw', refreshMenus);

        const reloadTable = () => dt.ajax.reload();

        searchInput?.addEventListener('keyup', reloadTable);
        applyBtn?.addEventListener('click', reloadTable);
        resetBtn?.addEventListener('click', () => {
            if (divisionFilter) {
                divisionFilter.value = '';
                if (typeof $ !== 'undefined' && $(divisionFilter).data('select2')) {
                    $(divisionFilter).val('').trigger('change');
                }
            }
            if (akunFilter) {
                akunFilter.value = '';
                if (typeof $ !== 'undefined' && $(akunFilter).data('select2')) {
                    $(akunFilter).val('').trigger('change');
                }
            }
            reloadTable();
        });

        document.getElementById('btn_open_create_budget')?.addEventListener('click', () => {
            form?.reset();
            if (formId) formId.value = '';
            setSelectValue(formDivision, '');
            setSelectValue(formAkun, '');
            clearErrors();
            if (titleEl) titleEl.textContent = 'Add Budget';
        });

        const clearErrors = () => {
            ['error_division_id','error_akun_biaya_id','error_amount'].forEach(id => {
                const el = document.getElementById(id);
                if (el) el.textContent = '';
            });
        };

        const setSelectValue = (el, value) => {
            if (!el) return;
            el.value = value ?? '';
            if (typeof $ !== 'undefined' && $(el).data('select2')) {
                $(el).val(el.value).trigger('change');
            }
        };

        form?.addEventListener('submit', async (e) => {
            e.preventDefault();
            clearErrors();
            const id = formId.value;
            const url = id ? updateTpl.replace(':id', id) : storeUrl;
            const formData = new FormData(form);
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
                try { json = JSON.parse(text); } catch (parseErr) {
                    console.error('Invalid JSON', text);
                    if (typeof Swal !== 'undefined') Swal.fire('Error', 'Respons server tidak valid', 'error');
                    return;
                }
                if (!res.ok) {
                    if (json?.errors) {
                        Object.entries(json.errors).forEach(([key, msgs])=>{
                            const errEl = document.getElementById(`error_${key}`);
                            if (errEl) errEl.textContent = msgs.join(', ');
                        });
                    } else if (typeof Swal !== 'undefined') {
                        Swal.fire('Error', json.message || 'Gagal menyimpan budget', 'error');
                    }
                    return;
                }
                if (typeof Swal !== 'undefined') Swal.fire('Berhasil', json.message || 'Berhasil', 'success');
                modal?.hide();
                reloadTable();
            } catch (err) {
                console.error(err);
                if (typeof Swal !== 'undefined') Swal.fire('Error', 'Gagal menyimpan budget', 'error');
            }
        });

        tableEl.on('click', '.btn-edit', function(e) {
            e.preventDefault();
            const id = this.getAttribute('data-id');
            const divisionId = this.getAttribute('data-division');
            const akunId = this.getAttribute('data-akun');
            const amount = this.getAttribute('data-amount');
            if (!form) return;
            formId.value = id;
            formAmount.value = amount ?? '';
            setSelectValue(formDivision, divisionId || '');
            setSelectValue(formAkun, akunId || '');
            clearErrors();
            if (titleEl) titleEl.textContent = 'Edit Budget';
            modal?.show();
        });

        tableEl.on('click', '.btn-delete', async function(e) {
            e.preventDefault();
            const id = this.getAttribute('data-id');
            let confirmed = true;
            if (typeof Swal !== 'undefined') {
                const res = await Swal.fire({
                    title: 'Apakah Anda yakin?',
                    text: 'Budget akan dihapus',
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
                const res = await fetch(deleteTpl.replace(':id', id), {
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
                try { json = JSON.parse(text); } catch (parseErr) {
                    console.error('Invalid JSON', text);
                    if (typeof Swal !== 'undefined') Swal.fire('Error', 'Respons server tidak valid', 'error');
                    return;
                }
                if (!res.ok) {
                    if (typeof Swal !== 'undefined') Swal.fire('Error', json.message || 'Gagal menghapus budget', 'error');
                    return;
                }
                if (typeof Swal !== 'undefined') Swal.fire('Berhasil', json.message || 'Berhasil', 'success');
                reloadTable();
            } catch (err) {
                console.error(err);
                if (typeof Swal !== 'undefined') Swal.fire('Error', 'Gagal menghapus budget', 'error');
            }
        });
    });
</script>
@endpush

@include('layouts.partials.form-submit-confirmation')
