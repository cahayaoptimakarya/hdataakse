@php use App\Services\CostReport; use App\Support\Fmt; @endphp
<div class="ui-muted fs-7 mb-3">
    Budget per toko adalah budget tahunan.
    @if($budget_editable)
        Arahkan kursor ke angka akun lalu klik <i class="fa-solid fa-pen fs-8"></i> untuk mengubah budget toko.
    @else
        Pilih periode Januari – Desember untuk mengubah budget toko dari halaman ini.
    @endif
</div>

@forelse($division_detail_groups as $division)
    @php
        $tokos = collect($division['sub_divisions']);
        $divR = 0; $divB = 0;
        // Bila divisi ini belum memakai budget per toko sama sekali, jangan tandai semua sel "tanpa budget".
        $pakaiBudget = collect($division['akun_groups'])->contains(fn ($ag) => $tokos->contains(fn ($t) => isset($sub_divisi_budget_map[$ag['akun_id']][$t['id']])));
    @endphp
    <div class="ui-panel mb-4">
        <div class="hd">
            <h3>{{ $division['division'] }}</h3>
            <span class="ui-sp"></span>
            <small>{{ $tokos->count() }} toko · dalam {{ $satuanLabel }}@unless($pakaiBudget) · belum ada budget per toko @endunless</small>
        </div>
        <div class="table-responsive">
            <table class="ui-mx">
                <thead>
                    <tr>
                        <th>Akun / Kategori</th>
                        @foreach($tokos as $t)<th>{{ $t['name'] }}</th>@endforeach
                        <th>Total</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($division['akun_groups'] as $ag)
                        @php
                            $rowR = 0; $rowB = 0;
                        @endphp
                        <tr class="ak">
                            <td>{{ $ag['akun'] }}</td>
                            @foreach($tokos as $t)
                                @php
                                    $r = $ag['totals_by_sub_divisi'][$t['id']]['kredit'] ?? 0;
                                    $b = $sub_divisi_budget_map[$ag['akun_id']][$t['id']] ?? 0;
                                    $rowR += $r; $rowB += $b;
                                @endphp
                                <td class="cell clk position-relative toko-cell" data-detail='@json(['akun_id' => $ag['akun_id'], 'sub_divisi_id' => $t['id']])'>
                                    {!! $cell($r, $pakaiBudget ? $b : null) !!}
                                    @if($budget_editable)
                                        <button type="button" class="btn btn-icon btn-sm btn-light budget-edit no-detail" title="Ubah budget toko"
                                            data-sub-divisi="{{ $t['id'] }}" data-akun="{{ $ag['akun_id'] }}" data-budget="{{ $b ?: '' }}"
                                            data-label="{{ $ag['akun'] }} · {{ $t['name'] }}"><i class="fa-solid fa-pen fs-8"></i></button>
                                    @endif
                                </td>
                            @endforeach
                            @php $divR += $rowR; $divB += $rowB; @endphp
                            <td class="cell">{!! $cell($rowR, $pakaiBudget ? $rowB : null) !!}</td>
                        </tr>
                        @foreach($ag['sub_groups'] as $sub)
                            <tr class="sub">
                                <td>{{ $sub['sub_akun'] }}</td>
                                @foreach($tokos as $i => $t)
                                    @php $v = $sub['cells'][$i]['kredit'] ?? 0; @endphp
                                    <td class="cell {{ $v ? 'clk' : '' }}" @if($v) data-detail='@json(['sub_akun_id' => $sub['sub_akun_id'], 'sub_divisi_id' => $t['id']])' @endif>{!! $cell($v, null) !!}</td>
                                @endforeach
                                <td class="cell">{!! $cell($sub['cells']->sum('kredit'), null) !!}</td>
                            </tr>
                        @endforeach
                    @endforeach
                    <tr class="gt">
                        <td>Total {{ $division['division'] }}</td>
                        @foreach($tokos as $t)
                            @php
                                $b = collect($division['akun_groups'])->sum(fn ($ag) => $sub_divisi_budget_map[$ag['akun_id']][$t['id']] ?? 0);
                            @endphp
                            <td class="cell">{!! $cell($division['grand_by_sub_divisi'][$t['id']]['kredit'] ?? 0, $pakaiBudget ? $b : null) !!}</td>
                        @endforeach
                        <td class="cell">{!! $cell($divR, $pakaiBudget ? $divB : null) !!}</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
@empty
    <div class="ui-panel p-5 ui-muted">Tidak ada data untuk periode dan filter ini.</div>
@endforelse

@if($budget_editable)
    <div class="modal fade" id="budget_toko_modal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered mw-450px">
            <div class="modal-content">
                <div class="modal-header py-4">
                    <h3 class="modal-title fs-5">Budget toko {{ $periode->tahun }}</h3>
                    <button type="button" class="btn btn-sm btn-icon btn-light" data-bs-dismiss="modal" aria-label="Tutup"><i class="fa-solid fa-xmark"></i></button>
                </div>
                <div class="modal-body">
                    <div class="ui-muted fs-7 mb-3" id="bt_label"></div>
                    <label class="form-label">Budget setahun (Rp)</label>
                    <input type="text" inputmode="numeric" class="form-control form-control-solid text-end" id="bt_amount" placeholder="Kosongkan untuk menghapus budget">
                </div>
                <div class="modal-footer py-3">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                    <button type="button" class="btn btn-primary" id="bt_save">Simpan</button>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
    document.addEventListener('DOMContentLoaded', () => {
        const modalEl = document.getElementById('budget_toko_modal');
        const modal = new bootstrap.Modal(modalEl);
        const input = document.getElementById('bt_amount');
        const fmt = (v) => v === '' ? '' : new Intl.NumberFormat('id-ID', { maximumFractionDigits: 0 }).format(Number(v));
        let current = null;
        input.addEventListener('input', () => {
            const digits = input.value.replace(/\D/g, '');
            input.value = digits ? fmt(digits) : '';
        });
        document.querySelectorAll('.budget-edit').forEach((btn) => btn.addEventListener('click', (e) => {
            e.stopPropagation();
            current = btn.dataset;
            document.getElementById('bt_label').textContent = btn.dataset.label;
            input.value = fmt(btn.dataset.budget ? Math.round(Number(btn.dataset.budget)) : '');
            modal.show();
            setTimeout(() => input.focus(), 300);
        }));
        document.getElementById('bt_save').addEventListener('click', async () => {
            const digits = input.value.replace(/\D/g, '');
            const res = await fetch(@json(route('admin.keuangan.laporan.sub-divisi-budget')), {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content },
                body: JSON.stringify({ sub_divisi_id: current.subDivisi, akun_biaya_id: current.akun, tahun: {{ (int) $periode->tahun }}, amount: digits === '' ? null : Number(digits) }),
            });
            const json = await res.json().catch(() => ({}));
            if (!res.ok) {
                Swal.fire({ icon: 'error', text: json.message || 'Gagal menyimpan budget.' });
                return;
            }
            window.location.reload();
        });
    });
    </script>
    @endpush
@endif
