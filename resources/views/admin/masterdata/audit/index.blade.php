@extends('layouts.admin')

@section('title', 'Log Aktivitas')

@php
    $badge = ['tambah' => 'b-ok', 'ubah' => 'b-blue', 'hapus' => 'b-bad', 'import' => 'b-blue', 'batal' => 'b-warn', 'salin' => 'b-blue', 'kirim' => 'b-gray'];
    $fmtVal = function ($v) {
        if ($v === null || $v === '') return '–';
        if (is_bool($v)) return $v ? 'ya' : 'tidak';
        if (is_numeric($v) && abs((float) $v) >= 1000) return number_format((float) $v, 0, ',', '.');
        return is_scalar($v) ? (string) $v : json_encode($v);
    };
@endphp

@section('content')
    <form method="GET" class="ui-filter mb-4">
        <div class="row g-3 align-items-end">
            <div class="col-12 col-lg-4">
                <label class="form-label">Cari</label>
                <input type="text" name="q" value="{{ $filters['q'] }}" class="form-control form-control-solid form-control-sm" placeholder="Nama divisi, akun, file…">
            </div>
            <div class="col-6 col-lg-2">
                <label class="form-label">Aktivitas</label>
                <select name="action" class="form-select form-select-solid form-select-sm">
                    <option value="">Semua</option>
                    @foreach($actions as $a)<option value="{{ $a }}" @selected($filters['action'] === $a)>{{ ucfirst($a) }}</option>@endforeach
                </select>
            </div>
            <div class="col-6 col-lg-2">
                <label class="form-label">Data</label>
                <select name="subject_type" class="form-select form-select-solid form-select-sm">
                    <option value="">Semua</option>
                    @foreach($types as $t)<option value="{{ $t }}" @selected($filters['subject_type'] === $t)>{{ $t }}</option>@endforeach
                </select>
            </div>
            <div class="col-6 col-lg-2">
                <label class="form-label">Pengguna</label>
                <select name="user_id" class="form-select form-select-solid form-select-sm">
                    <option value="">Semua</option>
                    @foreach($users as $u)<option value="{{ $u->id }}" @selected($filters['user_id'] === $u->id)>{{ $u->name }}</option>@endforeach
                </select>
            </div>
            <div class="col-6 col-lg-2">
                <label class="form-label">Dari</label>
                <input type="date" name="dari" value="{{ $filters['dari'] }}" class="form-control form-control-solid form-control-sm">
            </div>
            <div class="col-6 col-lg-2">
                <label class="form-label">Sampai</label>
                <input type="date" name="sampai" value="{{ $filters['sampai'] }}" class="form-control form-control-solid form-control-sm">
            </div>
            <div class="col-12 d-flex gap-2 justify-content-end">
                <a href="{{ route('admin.masterdata.audit.index') }}" class="btn btn-light btn-sm">Reset</a>
                <button class="btn btn-primary btn-sm">Terapkan</button>
            </div>
        </div>
    </form>

    <div class="ui-panel">
        <div class="hd"><h3>Log aktivitas</h3><span class="ui-sp"></span><small>Perubahan budget, master data, pengguna, hak akses, dan import jurnal</small></div>
        @if($logs->isEmpty())
            <div class="p-5 ui-muted">Belum ada aktivitas yang tercatat.</div>
        @else
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead><tr><th class="ps-5" style="width:150px">Waktu</th><th style="width:160px">Pengguna</th><th>Aktivitas</th><th class="pe-5">Perubahan</th></tr></thead>
                    <tbody>
                        @foreach($logs as $log)
                            <tr>
                                <td class="ps-5 text-nowrap">{{ $log->created_at->format('d/m/Y H:i') }}</td>
                                <td>{{ $log->user?->name ?? 'Sistem' }}</td>
                                <td>
                                    <span class="badge {{ $badge[$log->action] ?? 'b-gray' }} me-2">{{ ucfirst($log->action) }}</span>{{ $log->description }}
                                </td>
                                <td class="pe-5 fs-7">
                                    @foreach(($log->changes ?? []) as $field => $pair)
                                        <div class="text-nowrap"><span class="ui-muted">{{ $field }}:</span>
                                            @if($log->action === 'ubah' || ($pair[0] ?? null) !== null && ($pair[1] ?? null) !== null)
                                                {{ $fmtVal($pair[0] ?? null) }} → <b>{{ $fmtVal($pair[1] ?? null) }}</b>
                                            @else
                                                {{ $fmtVal($pair[1] ?? $pair[0] ?? null) }}
                                            @endif
                                        </div>
                                    @endforeach
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="d-flex align-items-center px-5 py-4">
                <div class="ui-muted fs-7">Menampilkan {{ $logs->firstItem() }}–{{ $logs->lastItem() }} dari {{ number_format($logs->total(), 0, ',', '.') }} aktivitas</div>
                <span class="ui-sp"></span>
                <div class="ui-pager">{{ $logs->onEachSide(1)->links('pagination::bootstrap-5') }}</div>
            </div>
        @endif
    </div>
@endsection
