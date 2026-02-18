@extends('layouts.admin')

@section('title', 'Laporan Jurnal Umum')
@section('page_title', 'Laporan Jurnal Umum')

@section('content')
<div class="card">
    <div class="card-header border-0 pt-6">
        <div class="card-title">
            <h2 class="fw-bolder mb-0">Laporan Jurnal Umum</h2>
        </div>
    </div>
    <div class="card-body py-6">
        @php
            $formatRupiah = function ($value) {
                return 'Rp '.number_format((float) $value, 2, ',', '.');
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
                    <a href="{{ route('admin.keuangan.laporan-jurnal-umum.index') }}" class="btn btn-light">Reset</a>
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
                                    <td class="ps-6 fw-bold">{{ $sub['sub_akun'] }}</td>
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
                                @foreach($akunGroup['totals_by_division'] as $cell)
                                    <td class="text-end fw-bold">{{ $formatRupiah($cell['kredit']) }}</td>
                                @endforeach
                                <td class="text-end fw-bold">
                                    {{ $formatRupiah($akunGroup['totals_by_division']->sum('kredit')) }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr class="fw-bolder">
                            <td>Grand Total</td>
                            @foreach($grand_by_division as $cell)
                                <td class="text-end">{{ $formatRupiah($cell['kredit']) }}</td>
                            @endforeach
                            <td class="text-end">{{ $formatRupiah($grand_total_kredit) }}</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        @endif
    </div>
</div>
@endsection
