@php use App\Services\CostReport; use App\Support\Fmt; @endphp
@php
    $list = $positions->filter(fn ($p) => in_array(CostReport::status($p->pct, $p->realisasi), ['bad', 'warn'], true));
@endphp
<div class="ui-panel">
    <div class="hd">
        <h3>Pos melebihi atau mendekati budget</h3>
        <span class="ui-sp"></span>
        <small>{{ $periode->label() }} · serapan 80% ke atas, atau ada realisasi tanpa budget</small>
    </div>
    @if($list->isEmpty())
        <div class="p-5 ui-muted">Tidak ada pos yang mendekati atau melebihi budget. 👍</div>
    @else
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead>
                    <tr>
                        <th class="ps-5">Akun</th><th>Divisi</th>
                        <th class="text-end">Realisasi</th><th class="text-end">Budget</th><th class="text-end">Selisih</th>
                        <th style="width:200px">Serapan</th><th class="pe-5"></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($list as $p)
                        @php $s = CostReport::status($p->pct, $p->realisasi); @endphp
                        <tr>
                            <td class="ps-5 fw-semibold">{{ $p->akun }}</td>
                            <td>{{ $p->division }}</td>
                            <td class="text-end">{{ Fmt::rp($p->realisasi) }}</td>
                            <td class="text-end">{{ $p->budget > 0 ? Fmt::rp($p->budget) : '–' }}</td>
                            <td class="text-end {{ $p->realisasi > $p->budget ? 'c-bad' : '' }}">{{ Fmt::rp($p->budget - $p->realisasi) }}</td>
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    <div class="ui-bar flex-grow-1 mt-0"><i style="width:{{ min(100, $p->pct ?? 100) }}%;background:{{ Fmt::color($s) }}"></i></div>
                                    <span class="badge b-{{ $s }}">{{ $p->pct === null ? 'Tanpa budget' : Fmt::pct($p->pct) }}</span>
                                </div>
                            </td>
                            <td class="pe-5 text-end"><button type="button" class="btn btn-light btn-sm" data-detail='@json(['akun_id' => $p->akun_id, 'division_id' => $p->division_id])'>Detail</button></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>
