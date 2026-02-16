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

        @if($grouped->isEmpty())
            <div class="text-muted">Belum ada data jurnal umum.</div>
        @else
            <div class="table-responsive">
                <table class="table align-middle table-row-dashed fs-6 gy-5">
                    <thead>
                        <tr class="text-start text-gray-400 fw-bolder fs-7 text-uppercase gs-0">
                            <th>Akun Pembayaran / Sub Akun</th>
                            <th class="text-end">Total Debet</th>
                            <th class="text-end">Total Kredit</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($grouped as $group)
                            <tr class="table-primary">
                                <td colspan="3" class="fw-bolder">Divisi: {{ $group['division'] }}</td>
                            </tr>
                            @foreach($group['akun_groups'] as $akunGroup)
                                <tr class="table-secondary">
                                    <td colspan="3" class="fw-bolder">{{ $akunGroup['akun'] }}</td>
                                </tr>
                                @foreach($akunGroup['rows'] as $row)
                                    <tr>
                                        <td class="ps-6 fw-bold">{{ $row->sub_akun }}</td>
                                        <td class="text-end">{{ $formatRupiah($row->total_debet) }}</td>
                                        <td class="text-end">{{ $formatRupiah($row->total_kredit) }}</td>
                                    </tr>
                                @endforeach
                                <tr class="table-light">
                                    <td class="fw-bold">Total {{ $akunGroup['akun'] }}</td>
                                    <td class="text-end fw-bold">{{ $formatRupiah($akunGroup['total_debet']) }}</td>
                                    <td class="text-end fw-bold">{{ $formatRupiah($akunGroup['total_kredit']) }}</td>
                                </tr>
                            @endforeach
                            <tr class="table-light">
                                <td class="fw-bold">Total {{ $group['division'] }}</td>
                                <td class="text-end fw-bold">{{ $formatRupiah($group['total_debet']) }}</td>
                                <td class="text-end fw-bold">{{ $formatRupiah($group['total_kredit']) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr class="fw-bolder">
                            <td>Grand Total</td>
                            <td class="text-end">{{ $formatRupiah($grand_total_debet) }}</td>
                            <td class="text-end">{{ $formatRupiah($grand_total_kredit) }}</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        @endif
    </div>
</div>
@endsection
