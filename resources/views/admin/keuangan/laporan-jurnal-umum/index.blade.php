@extends('layouts.admin')

@section('title', 'Laporan')
@section('page_title', 'Laporan')

@section('content')
<div class="card">
    <div class="card-header border-0 pt-6">
        <div class="card-title">
            <h2 class="fw-bolder mb-0">Laporan</h2>
        </div>
    </div>
    <div class="card-body py-6">
        @php
            $formatRupiah = function ($value) {
                return number_format((float) $value, 2, ',', '.');
            };
        @endphp

        <form method="GET" class="mb-5">
            <div class="row g-3 align-items-end">
                <div class="col-12 col-md-6 col-lg-4">
                    <label class="form-label fw-bold">Divisi</label>
                    <select name="division_ids[]" class="form-select form-select-solid" multiple data-control="select2" data-placeholder="Pilih divisi">
                        @foreach($division_options as $div)
                            <option value="{{ $div->id }}" @selected(in_array($div->id, $selected_division_ids ?? [], true))>
                                {{ $div->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-12 col-md-6 col-lg-4">
                    <label class="form-label fw-bold">Akun Biaya</label>
                    <select name="akun_ids[]" class="form-select form-select-solid" multiple data-control="select2" data-placeholder="Pilih akun biaya">
                        @foreach($akun_options as $akun)
                            <option value="{{ $akun->id }}" @selected(in_array($akun->id, $selected_akun_ids ?? [], true))>
                                {{ $akun->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-auto">
                    <button type="submit" class="btn btn-primary">Terapkan</button>
                </div>
                <div class="col-auto">
                    <a href="{{ route('admin.keuangan.laporan.index') }}" class="btn btn-light">Reset</a>
                </div>
                <div class="col-auto">
                    <a href="{{ route('admin.keuangan.laporan.export', request()->query()) }}" class="btn btn-light-success">Export Excel</a>
                </div>
                <div class="col-12">
                    <div class="form-text">Bisa pilih lebih dari satu divisi dan akun biaya.</div>
                </div>
            </div>
        </form>

        @if($akun_groups->isEmpty())
            <div class="text-muted">
                @if(!empty($selected_division_ids) || !empty($selected_akun_ids))
                    Tidak ada data untuk filter yang dipilih.
                @else
                    Belum ada data jurnal umum.
                @endif
            </div>
        @else
            <div class="table-responsive">
                <table class="table align-middle table-row-dashed fs-6 gy-5 table-bordered">
                    <thead>
                        <tr class="text-start text-gray-400 fw-bolder fs-7 text-uppercase gs-0 align-middle">
                            <th rowspan="2" class="align-middle">Biaya</th>
                            <th colspan="{{ max(1, $divisions->count()) }}" class="text-center align-middle">Divisi</th>
                            <th rowspan="2" class="text-end align-middle">Total</th>
                        </tr>
                        <tr class="text-start text-gray-400 fw-bolder fs-7 text-uppercase gs-0">
                            @foreach($divisions as $div)
                                <th class="text-end text-nowrap">{{ $div['name'] }}</th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($akun_groups as $akunGroup)
                            <tr class="table-secondary">
                                <td colspan="{{ $divisions->count() + 2 }}" class="fw-bolder">{{ $akunGroup['akun'] }}</td>
                            </tr>
                            @foreach($akunGroup['sub_groups'] as $sub)
                                <tr>
                                    <td class="ps-6 fw-bold">
                                        <button type="button" class="btn btn-link p-0 fw-bold sub-akun-link"
                                            data-sub-akun-id="{{ $sub['sub_akun_id'] }}"
                                            data-sub-akun-name="{{ $sub['sub_akun'] }}">
                                            {{ $sub['sub_akun'] }}
                                        </button>
                                    </td>
                                    @foreach($sub['cells'] as $cell)
                                        <td class="text-end">{{ $formatRupiah($cell['kredit']) }}</td>
                                    @endforeach
                                    <td class="text-end">
                                        {{ $formatRupiah($sub['cells']->sum('kredit')) }}
                                    </td>
                                </tr>
                            @endforeach
                            <tr class="table-light">
                                <td class="fw-bold">Total {{ $akunGroup['akun'] }}</td>
                                @foreach($divisions as $div)
                                    @php
                                        $totals = $akunGroup['totals_by_division'][$div['id']] ?? ['kredit' => 0];
                                    @endphp
                                    <td class="text-end fw-bold">{{ $formatRupiah($totals['kredit']) }}</td>
                                @endforeach
                                @php
                                    $totalAkun = collect($akunGroup['totals_by_division'])->sum('kredit');
                                @endphp
                                <td class="text-end fw-bold">{{ $formatRupiah($totalAkun) }}</td>
                            </tr>
                            <tr class="table-warning budget-row" data-akun="{{ $akunGroup['akun_id'] }}" data-akun-name="{{ $akunGroup['akun'] }}">
                                <td class="fw-bold">Budget</td>
                                @foreach($divisions as $div)
                                    @php
                                        $budgetVal = $budget_map[$akunGroup['akun_id']][$div['id']] ?? null;
                                        $actualVal = $akunGroup['totals_by_division'][$div['id']]['kredit'] ?? 0;
                                    @endphp
                                    <td
                                        class="text-end budget-cell"
                                        data-akun="{{ $akunGroup['akun_id'] }}"
                                        data-division="{{ $div['id'] }}"
                                        data-division-name="{{ $div['name'] }}"
                                        data-actual="{{ $actualVal }}"
                                        data-budget="{{ $budgetVal !== null ? $budgetVal : '' }}"
                                    >
                                        <div class="d-flex justify-content-end align-items-center gap-2">
                                            <span class="budget-value">-</span>
                                            <button type="button" class="btn btn-icon btn-sm btn-light budget-edit-btn" data-akun="{{ $akunGroup['akun_id'] }}" data-division="{{ $div['id'] }}" title="Edit budget">
                                                <i class="fa-solid fa-pen"></i>
                                            </button>
                                        </div>
                                    </td>
                                @endforeach
                                <td class="text-end fw-bold budget-total" data-akun="{{ $akunGroup['akun_id'] }}">-</td>
                            </tr>
                            <tr class="table-light selisih-row" data-akun="{{ $akunGroup['akun_id'] }}">
                                <td class="fw-bold">Selisih</td>
                                @foreach($divisions as $div)
                                    <td class="text-end selisih-cell" data-akun="{{ $akunGroup['akun_id'] }}" data-division="{{ $div['id'] }}">-</td>
                                @endforeach
                                <td class="text-end fw-bold selisih-total" data-akun="{{ $akunGroup['akun_id'] }}">-</td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr class="fw-bolder">
                            <td>Grand Total</td>
                            @foreach($divisions as $div)
                                @php
                                    $grand = $grand_by_division[$div['id']] ?? ['kredit' => 0];
                                @endphp
                                <td class="text-end">{{ $formatRupiah($grand['kredit']) }}</td>
                            @endforeach
                            <td class="text-end">{{ $formatRupiah($grand_total_kredit) }}</td>
                        </tr>
                        <tr class="table-warning fw-bolder">
                            <td>Grand Budget</td>
                            @foreach($divisions as $div)
                                <td class="text-end grand-budget-cell" data-division="{{ $div['id'] }}" data-division-name="{{ $div['name'] }}" data-budget="">
                                    <div class="d-flex justify-content-end align-items-center gap-2">
                                        <span class="grand-budget-value">-</span>
                                        <button type="button" class="btn btn-icon btn-sm btn-light grand-budget-edit-btn" data-division="{{ $div['id'] }}" title="Edit grand budget">
                                            <i class="fa-solid fa-pen"></i>
                                        </button>
                                    </div>
                                </td>
                            @endforeach
                            <td class="text-end fw-bolder grand-budget-total">-</td>
                        </tr>
                        <tr class="table-light fw-bolder">
                            <td>Grand Selisih</td>
                            @foreach($divisions as $div)
                                <td class="text-end grand-selisih-cell" data-division="{{ $div['id'] }}">-</td>
                            @endforeach
                            <td class="text-end grand-selisih-total">-</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        @endif
    </div>
</div>
@endsection

@push('styles')
<style>
    .budget-cell .budget-edit-btn { opacity: 0; transition: opacity .15s ease-in-out; }
    .budget-cell:hover .budget-edit-btn { opacity: 1; }
    .grand-budget-cell .grand-budget-edit-btn { opacity: 0; transition: opacity .15s ease-in-out; }
    .grand-budget-cell:hover .grand-budget-edit-btn { opacity: 1; }
    .sub-akun-link { text-decoration: none; }
    .sub-akun-link:hover { text-decoration: underline; }
</style>
@endpush

<!-- Modal Budget -->
<div class="modal fade" id="budget_modal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered mw-650px">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="fw-bolder" id="budget_modal_title">Edit Budget</h2>
                <div class="btn btn-icon btn-sm btn-active-icon-primary" data-bs-dismiss="modal">
                    <span class="svg-icon svg-icon-1">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none">
                            <rect opacity="0.5" x="6" y="17.3137" width="16" height="2" rx="1" transform="rotate(-45 6 17.3137)" fill="black" />
                            <rect x="7.41422" y="6" width="16" height="2" rx="1" transform="rotate(45 7.41422 6)" fill="black" />
                        </svg>
                    </span>
                </div>
            </div>
            <div class="modal-body">
                <div class="table-responsive">
                    <table class="table align-middle table-row-dashed fs-6 gy-5 mb-0">
                        <thead>
                            <tr class="text-start text-gray-400 fw-bolder fs-7 text-uppercase gs-0">
                                <th>Divisi</th>
                                <th class="text-end">Budget</th>
                            </tr>
                        </thead>
                        <tbody id="budget_modal_body"></tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                <button type="button" class="btn btn-primary" id="budget_modal_save">Simpan</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Grand Budget -->
<div class="modal fade" id="grand_budget_modal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered mw-650px">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="fw-bolder" id="grand_budget_modal_title">Edit Grand Budget</h2>
                <div class="btn btn-icon btn-sm btn-active-icon-primary" data-bs-dismiss="modal">
                    <span class="svg-icon svg-icon-1">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none">
                            <rect opacity="0.5" x="6" y="17.3137" width="16" height="2" rx="1" transform="rotate(-45 6 17.3137)" fill="black" />
                            <rect x="7.41422" y="6" width="16" height="2" rx="1" transform="rotate(45 7.41422 6)" fill="black" />
                        </svg>
                    </span>
                </div>
            </div>
            <div class="modal-body">
                <div class="table-responsive">
                    <table class="table align-middle table-row-dashed fs-6 gy-5 mb-0">
                        <thead>
                            <tr class="text-start text-gray-400 fw-bolder fs-7 text-uppercase gs-0">
                                <th>Divisi</th>
                                <th class="text-end">Budget</th>
                            </tr>
                        </thead>
                        <tbody id="grand_budget_modal_body"></tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                <button type="button" class="btn btn-primary" id="grand_budget_modal_save">Simpan</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Sub Akun Jurnal -->
<div class="modal fade" id="sub_akun_modal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered mw-1000px">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="fw-bolder" id="sub_akun_modal_title">Detail Jurnal</h2>
                <div class="btn btn-icon btn-sm btn-active-icon-primary" data-bs-dismiss="modal">
                    <span class="svg-icon svg-icon-1">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none">
                            <rect opacity="0.5" x="6" y="17.3137" width="16" height="2" rx="1" transform="rotate(-45 6 17.3137)" fill="black" />
                            <rect x="7.41422" y="6" width="16" height="2" rx="1" transform="rotate(45 7.41422 6)" fill="black" />
                        </svg>
                    </span>
                </div>
            </div>
            <div class="modal-body">
                <div class="table-responsive">
                    <table class="table align-middle table-row-dashed fs-6 gy-5 mb-0">
                        <thead>
                            <tr class="text-start text-gray-400 fw-bolder fs-7 text-uppercase gs-0">
                                <th>Tanggal</th>
                                <th>Divisi</th>
                                <th>Toko</th>
                                <th>Keterangan</th>
                                <th class="text-end">Debet</th>
                                <th class="text-end">Kredit</th>
                            </tr>
                        </thead>
                        <tbody id="sub_akun_modal_body"></tbody>
                        <tfoot>
                            <tr class="fw-bolder">
                                <td colspan="4" class="text-end">Total</td>
                                <td class="text-end" id="sub_akun_total_debet">0</td>
                                <td class="text-end" id="sub_akun_total_kredit">0</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', () => {
        const formatRupiah = (value) => {
            const num = Number(value ?? 0);
            const safe = Number.isFinite(num) ? num : 0;
            return new Intl.NumberFormat('id-ID', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(safe);
        };

        const toNumber = (value) => {
            if (value === null || value === undefined) return 0;
            let cleaned = String(value).replace(/[^0-9.,-]/g, '');
            if (cleaned === '') return 0;
            const hasComma = cleaned.includes(',');
            const hasDot = cleaned.includes('.');
            if (hasComma && hasDot) {
                if (cleaned.lastIndexOf(',') > cleaned.lastIndexOf('.')) {
                    cleaned = cleaned.replace(/\./g, '').replace(',', '.');
                } else {
                    cleaned = cleaned.replace(/,/g, '');
                }
            } else if (hasComma && !hasDot) {
                cleaned = cleaned.replace(',', '.');
            }
            const num = Number(cleaned);
            return Number.isFinite(num) ? num : 0;
        };

        const initBudgetMask = (input) => {
            if (!window.Inputmask) return;
            Inputmask({
                alias: 'numeric',
                groupSeparator: '.',
                radixPoint: ',',
                digits: 2,
                digitsOptional: false,
                autoGroup: true,
                rightAlign: true,
                prefix: 'Rp ',
                allowMinus: false,
                placeholder: '0',
                clearMaskOnLostFocus: false,
            }).mask(input);
        };

        const getMaskedValue = (input) => {
            if (input?.inputmask) {
                return input.inputmask.unmaskedvalue();
            }
            return input.value ?? '';
        };

        const parseNumber = (value) => {
            const num = Number(value);
            return Number.isFinite(num) ? num : 0;
        };

        const updateGrand = () => {
            const divisionIds = new Set();
            document.querySelectorAll('.grand-budget-cell').forEach((cell) => {
                if (cell.dataset.division) divisionIds.add(cell.dataset.division);
            });

            let grandBudgetTotal = 0;
            let grandActualTotal = 0;

            divisionIds.forEach((divisionId) => {
                let actualSum = 0;
                document.querySelectorAll(`.budget-cell[data-division="${divisionId}"]`).forEach((cell) => {
                    actualSum += parseNumber(cell.dataset.actual);
                });

                const budgetCell = document.querySelector(`.grand-budget-cell[data-division="${divisionId}"]`);
                const rawBudget = budgetCell?.dataset.budget;
                const hasBudget = rawBudget !== '' && rawBudget !== undefined;
                const budgetVal = hasBudget ? parseNumber(rawBudget) : 0;

                const valueEl = budgetCell?.querySelector('.grand-budget-value');
                if (valueEl) {
                    valueEl.textContent = hasBudget ? formatRupiah(budgetVal) : '-';
                }

                const diffEl = document.querySelector(`.grand-selisih-cell[data-division="${divisionId}"]`);
                if (diffEl) diffEl.textContent = formatRupiah(budgetVal - actualSum);

                grandBudgetTotal += budgetVal;
                grandActualTotal += actualSum;
            });

            const budgetTotalEl = document.querySelector('.grand-budget-total');
            if (budgetTotalEl) budgetTotalEl.textContent = formatRupiah(grandBudgetTotal);

            const diffTotalEl = document.querySelector('.grand-selisih-total');
            if (diffTotalEl) diffTotalEl.textContent = formatRupiah(grandBudgetTotal - grandActualTotal);
        };

        const updateAkun = (akunId) => {
            let budgetSum = 0;
            let diffSum = 0;

            const cells = document.querySelectorAll(`.budget-cell[data-akun="${akunId}"]`);
            cells.forEach((cell) => {
                const rawBudget = cell.dataset.budget;
                const hasBudget = rawBudget !== '' && rawBudget !== undefined;
                const budget = hasBudget ? parseNumber(rawBudget) : 0;
                const actual = parseNumber(cell.dataset.actual);
                const diff = budget - actual;

                budgetSum += budget;
                diffSum += diff;

                const valueEl = cell.querySelector('.budget-value');
                if (valueEl) {
                    valueEl.textContent = hasBudget ? formatRupiah(budget) : '-';
                }

                const diffEl = document.querySelector(`.selisih-cell[data-akun="${akunId}"][data-division="${cell.dataset.division}"]`);
                if (diffEl) {
                    diffEl.textContent = formatRupiah(diff);
                }
            });

            const totalEl = document.querySelector(`.budget-total[data-akun="${akunId}"]`);
            if (totalEl) {
                totalEl.textContent = formatRupiah(budgetSum);
            }

            const diffTotalEl = document.querySelector(`.selisih-total[data-akun="${akunId}"]`);
            if (diffTotalEl) {
                diffTotalEl.textContent = formatRupiah(diffSum);
            }

            updateGrand();
        };

        const akunIds = new Set();
        document.querySelectorAll('.budget-cell').forEach((cell) => {
            const akunId = cell.dataset.akun;
            if (akunId) akunIds.add(akunId);
        });
        akunIds.forEach((id) => updateAkun(id));
        updateGrand();

        const modalEl = document.getElementById('budget_modal');
        const modalBody = document.getElementById('budget_modal_body');
        const modalTitle = document.getElementById('budget_modal_title');
        const modalSave = document.getElementById('budget_modal_save');
        const modal = modalEl && window.bootstrap ? new bootstrap.Modal(modalEl) : null;

        const subAkunModalEl = document.getElementById('sub_akun_modal');
        const subAkunModalBody = document.getElementById('sub_akun_modal_body');
        const subAkunModalTitle = document.getElementById('sub_akun_modal_title');
        const subAkunTotalDebet = document.getElementById('sub_akun_total_debet');
        const subAkunTotalKredit = document.getElementById('sub_akun_total_kredit');
        const subAkunModal = subAkunModalEl && window.bootstrap ? new bootstrap.Modal(subAkunModalEl) : null;

        const subAkunUrlTemplate = '{{ route('admin.keuangan.laporan.sub-akun-jurnal', ':id') }}';

        const grandModalEl = document.getElementById('grand_budget_modal');
        const grandModalBody = document.getElementById('grand_budget_modal_body');
        const grandModalTitle = document.getElementById('grand_budget_modal_title');
        const grandModalSave = document.getElementById('grand_budget_modal_save');
        const grandModal = grandModalEl && window.bootstrap ? new bootstrap.Modal(grandModalEl) : null;

        const openBudgetModal = (akunId, divisionId) => {
            if (!modalEl || !modalBody) return;
            const row = document.querySelector(`.budget-row[data-akun="${akunId}"]`);
            const akunName = row?.dataset.akunName || 'Budget';
            const cell = document.querySelector(`.budget-cell[data-akun="${akunId}"][data-division="${divisionId}"]`);
            const divisionName = cell?.dataset.divisionName || '-';
            modalTitle.textContent = `Edit Budget - ${akunName} (${divisionName})`;
            modalBody.innerHTML = '';

            if (cell) {
                const rawBudgetVal = cell.dataset.budget ?? '';
                const tr = document.createElement('tr');
                tr.innerHTML = `
                    <td>${divisionName}</td>
                    <td class="text-end">
                        <input type="text" inputmode="numeric" class="form-control form-control-sm text-end budget-input-mask" data-division="${divisionId}" placeholder="Rp 0,00">
                    </td>
                `;
                modalBody.appendChild(tr);
                const input = tr.querySelector('input');
                if (input) {
                    initBudgetMask(input);
                    if (rawBudgetVal !== '') {
                        if (input.inputmask?.setValue) {
                            input.inputmask.setValue(rawBudgetVal);
                        } else {
                            input.value = rawBudgetVal;
                        }
                    }
                }
            }

            modalEl.dataset.akun = akunId;
            modalEl.dataset.division = divisionId;
            modal?.show();
        };

        const openGrandBudgetModal = (divisionId) => {
            if (!grandModalEl || !grandModalBody) return;
            const cell = document.querySelector(`.grand-budget-cell[data-division="${divisionId}"]`);
            const divisionName = cell?.dataset.divisionName || '-';
            const rawBudgetVal = cell?.dataset.budget ?? '';

            grandModalTitle.textContent = `Edit Grand Budget (${divisionName})`;
            grandModalBody.innerHTML = '';

            const tr = document.createElement('tr');
            tr.innerHTML = `
                <td>${divisionName}</td>
                <td class="text-end">
                    <input type="text" inputmode="numeric" class="form-control form-control-sm text-end grand-budget-input-mask" data-division="${divisionId}" placeholder="Rp 0,00">
                </td>
            `;
            grandModalBody.appendChild(tr);
            const input = tr.querySelector('input');
            if (input) {
                initBudgetMask(input);
                if (rawBudgetVal !== '') {
                    if (input.inputmask?.setValue) {
                        input.inputmask.setValue(rawBudgetVal);
                    } else {
                        input.value = rawBudgetVal;
                    }
                }
            }

            grandModalEl.dataset.division = divisionId;
            grandModal?.show();
        };

        document.querySelectorAll('.budget-edit-btn').forEach((btn) => {
            btn.addEventListener('click', (e) => {
                e.preventDefault();
                const akunId = btn.dataset.akun;
                const divisionId = btn.dataset.division;
                if (akunId && divisionId) openBudgetModal(akunId, divisionId);
            });
        });

        document.querySelectorAll('.sub-akun-link').forEach((btn) => {
            btn.addEventListener('click', async (e) => {
                e.preventDefault();
                const subAkunId = btn.dataset.subAkunId;
                const subAkunName = btn.dataset.subAkunName || '';
                if (!subAkunId || !subAkunModalEl || !subAkunModalBody) return;

                const query = window.location.search || '';
                const url = subAkunUrlTemplate.replace(':id', subAkunId) + query;

                subAkunModalTitle.textContent = `Detail Jurnal - ${subAkunName}`;
                subAkunModalBody.innerHTML = `
                    <tr>
                        <td colspan="6" class="text-center text-muted">Loading...</td>
                    </tr>
                `;
                if (subAkunTotalDebet) subAkunTotalDebet.textContent = '0';
                if (subAkunTotalKredit) subAkunTotalKredit.textContent = '0';
                subAkunModal?.show();

                try {
                    const res = await fetch(url, { headers: { 'Accept': 'application/json' } });
                    const json = await res.json();
                    if (!res.ok) throw new Error(json.message || 'Gagal mengambil data');

                    const rows = Array.isArray(json.rows) ? json.rows : [];
                    if (!rows.length) {
                        subAkunModalBody.innerHTML = `
                            <tr>
                                <td colspan="6" class="text-center text-muted">Tidak ada data.</td>
                            </tr>
                        `;
                    } else {
                        subAkunModalBody.innerHTML = '';
                        rows.forEach((row) => {
                            const tr = document.createElement('tr');
                            tr.innerHTML = `
                                <td>${row.tanggal ?? '-'}</td>
                                <td>${row.division ?? '-'}</td>
                                <td>${row.toko ?? '-'}</td>
                                <td>${row.keterangan ?? '-'}</td>
                                <td class="text-end">${formatRupiah(row.debet ?? 0)}</td>
                                <td class="text-end">${formatRupiah(row.kredit ?? 0)}</td>
                            `;
                            subAkunModalBody.appendChild(tr);
                        });
                    }

                    if (subAkunTotalDebet) subAkunTotalDebet.textContent = formatRupiah(json.total_debet ?? 0);
                    if (subAkunTotalKredit) subAkunTotalKredit.textContent = formatRupiah(json.total_kredit ?? 0);
                } catch (err) {
                    subAkunModalBody.innerHTML = `
                        <tr>
                            <td colspan="6" class="text-center text-danger">Gagal memuat data.</td>
                        </tr>
                    `;
                }
            });
        });

        document.querySelectorAll('.grand-budget-edit-btn').forEach((btn) => {
            btn.addEventListener('click', (e) => {
                e.preventDefault();
                const divisionId = btn.dataset.division;
                if (divisionId) openGrandBudgetModal(divisionId);
            });
        });

        modalSave?.addEventListener('click', () => {
            const akunId = modalEl?.dataset.akun;
            const divisionId = modalEl?.dataset.division;
            if (!akunId || !divisionId) return;
            modalBody?.querySelectorAll('input[data-division]').forEach((input) => {
                const value = getMaskedValue(input);
                const cell = document.querySelector(`.budget-cell[data-akun="${akunId}"][data-division="${divisionId}"]`);
                if (cell) {
                    cell.dataset.budget = value !== '' ? toNumber(value) : '';
                }
            });
            updateAkun(akunId);
            modal?.hide();
        });

        grandModalSave?.addEventListener('click', () => {
            const divisionId = grandModalEl?.dataset.division;
            if (!divisionId) return;
            const input = grandModalBody?.querySelector('input[data-division]');
            const value = input ? getMaskedValue(input) : '';
            const cell = document.querySelector(`.grand-budget-cell[data-division="${divisionId}"]`);
            if (cell) {
                cell.dataset.budget = value !== '' ? toNumber(value) : '';
            }
            updateGrand();
            grandModal?.hide();
        });
    });
</script>
@endpush
