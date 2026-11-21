{{-- Filter periode laporan: tahun + rentang bulan. --}}
<div class="col-12 col-md-4 col-lg-2">
    <label class="form-label fw-bold">Tahun</label>
    <select name="tahun" class="form-select form-select-solid periode-tahun">
        @foreach($tahun_options as $th)
            <option value="{{ $th }}" @selected($periode->tahun === $th)>{{ $th }}</option>
        @endforeach
        <option value="semua" @selected($periode->isSemua())>Semua periode</option>
    </select>
</div>
<div class="col-6 col-md-4 col-lg-2">
    <label class="form-label fw-bold">Dari bulan</label>
    <select name="bulan_dari" class="form-select form-select-solid" @disabled($periode->isSemua())>
        @foreach($nama_bulan as $no => $nama)
            <option value="{{ $no }}" @selected($periode->bulanDari === $no)>{{ $nama }}</option>
        @endforeach
    </select>
</div>
<div class="col-6 col-md-4 col-lg-2">
    <label class="form-label fw-bold">Sampai bulan</label>
    <select name="bulan_sampai" class="form-select form-select-solid" @disabled($periode->isSemua())>
        @foreach($nama_bulan as $no => $nama)
            <option value="{{ $no }}" @selected($periode->bulanSampai === $no)>{{ $nama }}</option>
        @endforeach
    </select>
</div>
<div class="w-100 d-none d-lg-block"></div>
