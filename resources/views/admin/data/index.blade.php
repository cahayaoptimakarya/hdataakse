@extends('layouts.admin')

@section('title', 'Data')
@section('page_title', 'Data')

@section('content')
<div class="row g-6 g-xl-9 align-items-stretch">
    <div class="col-xl-7">
        <div class="card h-100">
            <div class="card-header border-0 pt-6 pb-2">
                <div class="card-title">
                    <h2 class="fw-bolder mb-0">Rekap SKU</h2>
                </div>
                <div class="card-toolbar">
                    <a href="{{ route('admin.data.export') }}" class="btn btn-light-primary">
                        <i class="fa-solid fa-file-excel me-2"></i>Export Excel
                    </a>
                </div>
            </div>
            <div class="card-body py-6 px-6">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div class="w-100 w-xl-50">
                        <input type="text" id="sku_search" class="form-control form-control-solid" placeholder="Cari SKU...">
                    </div>
                </div>
                <div class="table-responsive">
                    <table class="table table-row-dashed align-middle" id="sku_table">
                        <thead>
                            <tr class="text-uppercase text-gray-500 fw-bold fs-7">
                                <th style="width:60px;">No</th>
                                <th>SKU</th>
                                <th class="text-end">Jumlah Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($aggregates as $row)
                                <tr>
                                    <td></td>
                                    <td class="fw-bold">{{ $row->sku }}</td>
                                    <td class="text-end fw-bold">{{ number_format($row->total_quantity) }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="3" class="text-center text-muted">Belum ada data.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-5">
        <div class="card h-100">
            <div class="card-header border-0 pt-6 pb-2">
                <div class="card-title">
                    <h3 class="fw-bolder mb-0">Status & Resi Tidak Terintegrasi</h3>
                </div>
                <div class="card-toolbar">
                    <a href="{{ route('admin.data.export-unintegrated') }}" class="btn btn-light-primary">
                        <i class="fa-solid fa-file-excel me-2"></i>Export Excel
                    </a>
                </div>
            </div>
            <div class="card-body py-6 px-8 d-flex flex-column gap-4">
                <div class="d-flex flex-column gap-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <span class="text-muted">Resi terintegrasi</span>
                        <span class="badge badge-light-success fs-7">{{ $integratedCount }}</span>
                    </div>
                    <div class="d-flex justify-content-between align-items-center">
                        <span class="text-muted">Resi tidak terintegrasi</span>
                        <span class="badge badge-light-danger fs-7">{{ $unintegratedCount }}</span>
                    </div>
                </div>
                <div class="table-responsive">
                    <table class="table table-row-dashed align-middle" id="unintegrated_table">
                        <thead>
                            <tr class="text-uppercase text-gray-500 fw-bold fs-7">
                                <th style="width:60px;">No</th>
                                <th>Resi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($unintegrated as $resi)
                                <tr>
                                    <td></td>
                                    <td>{{ $resi }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="2" class="text-center text-muted">Semua resi sudah terintegrasi.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row g-6 g-xl-9 align-items-stretch mt-6">
    <div class="col-xl-7">
        <div class="card h-100">
            <div class="card-header border-0 pt-6 pb-2">
                <div class="card-title">
                    <h2 class="fw-bolder mb-0">Rekap SKU (Instan)</h2>
                </div>
                <div class="card-toolbar">
                    <a href="{{ route('admin.data.export-instan') }}" class="btn btn-light-primary">
                        <i class="fa-solid fa-file-excel me-2"></i>Export Excel
                    </a>
                </div>
            </div>
            <div class="card-body py-6 px-6">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div class="w-100 w-xl-50">
                        <input type="text" id="instan_sku_search" class="form-control form-control-solid" placeholder="Cari SKU...">
                    </div>
                </div>
                <div class="table-responsive">
                    <table class="table table-row-dashed align-middle" id="instan_sku_table">
                        <thead>
                            <tr class="text-uppercase text-gray-500 fw-bold fs-7">
                                <th style="width:60px;">No</th>
                                <th>SKU</th>
                                <th class="text-end">Jumlah Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($instanAggregates as $row)
                                <tr>
                                    <td></td>
                                    <td class="fw-bold">{{ $row->sku }}</td>
                                    <td class="text-end fw-bold">{{ number_format($row->total_quantity) }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="3" class="text-center text-muted">Belum ada data.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-5">
        <div class="card h-100">
            <div class="card-header border-0 pt-6 pb-2">
                <div class="card-title">
                    <h3 class="fw-bolder mb-0">Status & Pesanan Instan Tidak Terintegrasi</h3>
                </div>
            </div>
            <div class="card-body py-6 px-8 d-flex flex-column gap-4">
                <div class="d-flex flex-column gap-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <span class="text-muted">Pesanan instan terintegrasi</span>
                        <span class="badge badge-light-success fs-7">{{ $instanIntegratedCount }}</span>
                    </div>
                    <div class="d-flex justify-content-between align-items-center">
                        <span class="text-muted">Pesanan instan tidak terintegrasi</span>
                        <span class="badge badge-light-danger fs-7">{{ $instanUnintegratedCount }}</span>
                    </div>
                </div>
                <div class="table-responsive">
                    <table class="table table-row-dashed align-middle" id="instan_unintegrated_table">
                        <thead>
                            <tr class="text-uppercase text-gray-500 fw-bold fs-7">
                                <th style="width:60px;">No</th>
                                <th>ID Pesanan</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($instanUnintegrated as $orderId)
                                <tr>
                                    <td></td>
                                    <td>{{ $orderId }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="2" class="text-center text-muted">Semua pesanan instan sudah terintegrasi.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    const tableEl = $('#sku_table');
    const skuSearch = document.getElementById('sku_search');
    if (tableEl.length && $.fn.DataTable) {
        const dt = tableEl.DataTable({
            paging: true,
            searching: true,
            info: false,
            order: [[2, 'desc']],
            pageLength: 10,
            columnDefs: [
                { targets: 0, orderable: false, searchable: false, className: 'text-center', render: () => '' },
            ],
            language: {
                search: "Cari:",
                lengthMenu: "Tampil _MENU_",
                paginate: {
                    previous: "Sebelumnya",
                    next: "Berikutnya"
                }
            }
        });
        dt.on('order.dt search.dt draw.dt', function () {
            dt.column(0, { search: 'applied', order: 'applied' }).nodes().each(function (cell, i) {
                cell.innerHTML = i + 1;
            });
        }).draw();

        skuSearch?.addEventListener('keyup', (e) => {
            dt.search(e.target.value).draw();
        });
    }

    const instanTableEl = $('#instan_sku_table');
    const instanSkuSearch = document.getElementById('instan_sku_search');
    if (instanTableEl.length && $.fn.DataTable) {
        const dtInstan = instanTableEl.DataTable({
            paging: true,
            searching: true,
            info: false,
            order: [[2, 'desc']],
            pageLength: 10,
            columnDefs: [
                { targets: 0, orderable: false, searchable: false, className: 'text-center', render: () => '' },
            ],
            language: {
                search: "Cari:",
                lengthMenu: "Tampil _MENU_",
                paginate: {
                    previous: "Sebelumnya",
                    next: "Berikutnya"
                }
            }
        });
        dtInstan.on('order.dt search.dt draw.dt', function () {
            dtInstan.column(0, { search: 'applied', order: 'applied' }).nodes().each(function (cell, i) {
                cell.innerHTML = i + 1;
            });
        }).draw();

        instanSkuSearch?.addEventListener('keyup', (e) => {
            dtInstan.search(e.target.value).draw();
        });
    }

    const unintegratedEl = $('#unintegrated_table');
    if (unintegratedEl.length && $.fn.DataTable) {
        const dtUn = unintegratedEl.DataTable({
            paging: true,
            searching: true,
            info: false,
            order: [[1, 'asc']],
            pageLength: 10,
            columnDefs: [
                { targets: 0, orderable: false, searchable: false, className: 'text-center', render: () => '' },
            ],
            language: {
                search: "Cari:",
                lengthMenu: "Tampil _MENU_",
                paginate: {
                    previous: "Sebelumnya",
                    next: "Berikutnya"
                }
            }
        });
        dtUn.on('order.dt search.dt draw.dt', function () {
            dtUn.column(0, { search: 'applied', order: 'applied' }).nodes().each(function (cell, i) {
                cell.innerHTML = i + 1;
            });
        }).draw();
    }

    const instanUnintegratedEl = $('#instan_unintegrated_table');
    if (instanUnintegratedEl.length && $.fn.DataTable) {
        const dtInstanUn = instanUnintegratedEl.DataTable({
            paging: true,
            searching: true,
            info: false,
            order: [[1, 'asc']],
            pageLength: 10,
            columnDefs: [
                { targets: 0, orderable: false, searchable: false, className: 'text-center', render: () => '' },
            ],
            language: {
                search: "Cari:",
                lengthMenu: "Tampil _MENU_",
                paginate: {
                    previous: "Sebelumnya",
                    next: "Berikutnya"
                }
            }
        });
        dtInstanUn.on('order.dt search.dt draw.dt', function () {
            dtInstanUn.column(0, { search: 'applied', order: 'applied' }).nodes().each(function (cell, i) {
                cell.innerHTML = i + 1;
            });
        }).draw();
    }
});
</script>
@endpush
