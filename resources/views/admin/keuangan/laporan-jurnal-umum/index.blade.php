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

        @if($akun_groups->isEmpty())
            <div class="text-muted">Belum ada data jurnal umum.</div>
        @else
            <div class="table-responsive">
                <table class="table align-middle table-row-dashed fs-6 gy-5">
                    <thead>
                        <tr class="text-start text-gray-400 fw-bolder fs-7 text-uppercase gs-0">
                            <th rowspan="2">Biaya</th>
                            <th colspan="{{ max(1, $divisions->count()) }}" class="text-center">Divisi</th>
                            <th rowspan="2" class="text-end">Total</th>
                        </tr>
                        <tr class="text-start text-gray-400 fw-bolder fs-7 text-uppercase gs-0">
                            @foreach($divisions as $div)
                                <th class="text-end">{{ $div['name'] }}</th>
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
