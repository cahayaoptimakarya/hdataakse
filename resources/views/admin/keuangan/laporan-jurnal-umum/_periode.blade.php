{{-- Filter periode laporan: tahun + rentang bulan. --}}
<div class="col-6 col-md-4 col-lg-auto">
    <label class="form-label">Tahun</label>
    <select name="tahun" class="form-select form-select-solid form-select-sm periode-tahun">
        @foreach($tahun_options as $th)
            <option value="{{ $th }}" @selected($periode->tahun === $th)>{{ $th }}</option>
        @endforeach
        <option value="semua" @selected($periode->isSemua())>Semua periode</option>
    </select>
</div>
<div class="col-6 col-md-4 col-lg-auto">
    <label class="form-label">Dari bulan</label>
    <select name="bulan_dari" class="form-select form-select-solid form-select-sm" @disabled($periode->isSemua())>
        @foreach($nama_bulan as $no => $nama)
            <option value="{{ $no }}" @selected($periode->bulanDari === $no)>{{ $nama }}</option>
        @endforeach
    </select>
</div>
<div class="col-6 col-md-4 col-lg-auto">
    <label class="form-label">Sampai bulan</label>
    <select name="bulan_sampai" class="form-select form-select-solid form-select-sm" @disabled($periode->isSemua())>
        @foreach($nama_bulan as $no => $nama)
            <option value="{{ $no }}" @selected($periode->bulanSampai === $no)>{{ $nama }}</option>
        @endforeach
    </select>
</div>
