{{-- Satu baris periode pembanding: tahun + rentang bulan. $bp = Periode atau null (template). --}}
<div class="ui-banding-row d-flex flex-wrap align-items-center gap-2">
    <input type="hidden" name="banding[]" value="{{ $bp ? $bp->tahun.'-'.$bp->bulanDari.'-'.$bp->bulanSampai : '' }}">
    <select class="form-select form-select-solid form-select-sm no-ui" data-f="tahun" style="width:96px">
        @foreach(range(max(max($tahun_options), $bp->tahun ?? 0), min(min($tahun_options), $bp->tahun ?? 9999) - 2) as $th)
            <option value="{{ $th }}" @selected($bp && $bp->tahun === $th)>{{ $th }}</option>
        @endforeach
    </select>
    <select class="form-select form-select-solid form-select-sm no-ui" data-f="dari" style="width:130px" aria-label="Dari bulan">
        @foreach($nama_bulan as $no => $nama)
            <option value="{{ $no }}" @selected($bp && $bp->bulanDari === $no)>{{ $nama }}</option>
        @endforeach
    </select>
    <span class="ui-muted">s/d</span>
    <select class="form-select form-select-solid form-select-sm no-ui" data-f="sampai" style="width:130px" aria-label="Sampai bulan">
        @foreach($nama_bulan as $no => $nama)
            <option value="{{ $no }}" @selected($bp && $bp->bulanSampai === $no)>{{ $nama }}</option>
        @endforeach
    </select>
    <button type="button" class="btn btn-sm btn-icon btn-light" data-remove title="Hapus periode"><i class="fa-solid fa-xmark"></i></button>
</div>
