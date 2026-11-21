<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Exports\LaporanDivisiExport;
use App\Exports\LaporanExport;
use App\Services\CostReport;
use App\Support\Audit;
use App\Support\Periode;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;

class LaporanJurnalUmumController extends Controller
{
    public const TABS = ['ringkasan', 'toko', 'tren', 'lewat'];

    public function index(Request $request)
    {
        $periode = Periode::fromRequest($request);
        $tab = $request->input('tab') === 'divisi' ? 'toko' : $request->input('tab');
        $tab = in_array($tab, self::TABS, true) ? $tab : 'ringkasan';
        $satuan = in_array($request->input('satuan'), ['penuh', 'ribu', 'juta'], true) ? $request->input('satuan') : 'juta';
        $divisionIds = $this->ids($request, 'division_ids');
        $akunIds = $this->ids($request, 'akun_ids');

        $akunOptions = DB::table('akun_biaya')
            ->where('dikecualikan', false)
            ->orderBy('name')
            ->get(['id', 'name']);

        $data = [
            'tab' => $tab,
            'satuan' => $satuan,
            'periode' => $periode,
            'tahun_options' => Periode::tahunOptions(),
            'nama_bulan' => Periode::NAMA_BULAN,
            'undated_count' => $periode->isSemua() ? 0 : DB::table('jurnal_umum')->whereNull('tanggal')->count(),
            'budget_editable' => !$periode->isSemua() && $periode->jumlahBulan() === 12,
            'division_options' => DB::table('divisions')->orderBy('name')->get(['id', 'name']),
            'akun_options' => $akunOptions,
            'selected_division_ids' => $divisionIds,
            'selected_akun_ids' => $akunIds,
        ];

        if (in_array($tab, ['ringkasan', 'lewat'], true)) {
            $report = new CostReport($periode);
            $data['matrix'] = $report->matrix($divisionIds, $akunIds);
            $data['positions'] = $report->positions($data['matrix']);
        } elseif ($tab === 'tren') {
            $data['tren_tahun'] = $periode->tahun ?? (int) now()->year;
            $data['tren'] = CostReport::monthlyByAkun($data['tren_tahun'], $divisionIds, $akunIds);
            $data['tren_lalu'] = CostReport::monthlyRealisasi($data['tren_tahun'] - 1, $divisionIds, $akunIds);
        } else {
            $data += $this->tokoTabData($request);
        }

        return view('admin.keuangan.laporan-jurnal-umum.index', $data);
    }

    private function ids(Request $request, string $key): array
    {
        $ids = $request->input($key, []);
        return collect(is_array($ids) ? $ids : [$ids])
            ->map(fn ($id) => (int) $id)->filter(fn ($id) => $id > 0)->unique()->values()->all();
    }

    private function tokoTabData(Request $request): array
    {
        $divisionReport = $this->buildDivisionDetailData($request);

        $subDivisionOptions = DB::table('sub_divisions as sd')
            ->join('divisions as d', 'd.id', '=', 'sd.division_id')
            ->when($divisionReport['selected_division_ids'], fn ($q, $ids) => $q->whereIn('d.id', $ids))
            ->orderBy('d.name')->orderBy('sd.name')
            ->get(['sd.id', 'sd.name', 'sd.division_id', 'd.name as division_name']);

        $subAkunOptions = DB::table('sub_akun_biaya as sab')
            ->join('akun_biaya as ab', 'ab.id', '=', 'sab.akun_biaya_id')
            ->where('ab.dikecualikan', false)
            ->when($divisionReport['selected_akun_ids'], fn ($q, $ids) => $q->whereIn('ab.id', $ids))
            ->orderBy('ab.name')->orderBy('sab.name')
            ->get(['sab.id', 'sab.name', 'sab.akun_biaya_id', 'ab.name as akun_name']);

        return [
            'division_detail_groups' => $divisionReport['division_groups'],
            'selected_division_ids_divisi' => $divisionReport['selected_division_ids'],
            'sub_divisi_budget_map' => $divisionReport['budget_map'],
            'sub_division_options' => $subDivisionOptions,
            'selected_sub_division_ids_divisi' => $divisionReport['selected_sub_division_ids'],
            'selected_akun_ids_divisi' => $divisionReport['selected_akun_ids'],
            'sub_akun_options_divisi' => $subAkunOptions,
            'selected_sub_akun_ids_divisi' => $divisionReport['selected_sub_akun_ids'],
        ];
    }

    /**
     * Rincian untuk panel detail: satu akun atau satu kategori, opsional per divisi atau toko.
     * Berisi ringkasan per toko, per kategori, per bulan, dan transaksi terbaru.
     */
    public function detail(Request $request)
    {
        $v = $request->validate([
            'akun_id' => ['nullable', 'integer'],
            'sub_akun_id' => ['nullable', 'integer'],
            'division_id' => ['nullable', 'integer'],
            'sub_divisi_id' => ['nullable', 'integer'],
        ]);
        $periode = Periode::fromRequest($request);

        $base = DB::table('jurnal_umum as ju')
            ->join('sub_divisions as sd', 'sd.id', '=', 'ju.sub_divisi_id')
            ->join('divisions as d', 'd.id', '=', 'sd.division_id')
            ->join('sub_akun_biaya as sab', 'sab.id', '=', 'ju.sub_akun_biaya_id')
            ->join('akun_biaya as ab', 'ab.id', '=', 'sab.akun_biaya_id')
            ->where('ab.dikecualikan', false);
        $periode->applyToJurnal($base);
        foreach (['akun_id' => 'ab.id', 'sub_akun_id' => 'sab.id', 'division_id' => 'd.id', 'sub_divisi_id' => 'sd.id'] as $key => $col) {
            if (!empty($v[$key])) {
                $base->where($col, $v[$key]);
            }
        }

        $perToko = (clone $base)
            ->select('sd.id', 'sd.name', 'd.name as division', DB::raw('SUM(ju.kredit) as total'), DB::raw('COUNT(*) as jumlah'))
            ->groupBy('sd.id', 'sd.name', 'd.name')->orderByDesc('total')->get();
        $perKategori = (clone $base)
            ->select('sab.id', 'sab.name', 'ab.name as akun', DB::raw('SUM(ju.kredit) as total'), DB::raw('COUNT(*) as jumlah'))
            ->groupBy('sab.id', 'sab.name', 'ab.name')->orderByDesc('total')->get();
        $perTanggal = (clone $base)->whereNotNull('ju.tanggal')
            ->select('ju.tanggal', DB::raw('SUM(ju.kredit) as total'))->groupBy('ju.tanggal')->get();
        $perBulan = [];
        foreach ($perTanggal as $r) {
            $key = substr((string) $r->tanggal, 0, 7);
            $perBulan[$key] = ($perBulan[$key] ?? 0) + (float) $r->total;
        }
        ksort($perBulan);
        $jumlah = (clone $base)->count();
        $rows = (clone $base)
            ->select('ju.tanggal', 'ju.keterangan', 'sd.name as toko', 'd.name as division', 'sab.name as kategori', 'ab.name as akun', 'ju.debet', 'ju.kredit')
            ->orderByRaw('ju.tanggal IS NULL')->orderByDesc('ju.tanggal')->orderByDesc('ju.id')
            ->limit(100)->get();

        // Budget: per toko bila toko dipilih, selain itu per divisi. Hanya untuk tingkat akun.
        $budget = null;
        $akunId = $v['akun_id'] ?? (!empty($v['sub_akun_id']) ? DB::table('sub_akun_biaya')->where('id', $v['sub_akun_id'])->value('akun_biaya_id') : null);
        if ($akunId && empty($v['sub_akun_id'])) {
            if (!empty($v['sub_divisi_id'])) {
                $budget = $periode->budgetMap('sub_divisi_budgets', 'sub_divisi_id', [$v['sub_divisi_id']], [$akunId])[$akunId][$v['sub_divisi_id']] ?? null;
            } else {
                $divIds = !empty($v['division_id']) ? [$v['division_id']] : DB::table('divisions')->pluck('id')->all();
                $map = $periode->budgetMap('budgets', 'division_id', $divIds, [$akunId]);
                $budget = isset($map[$akunId]) ? array_sum($map[$akunId]) : null;
            }
        }

        $title = collect([
            !empty($v['sub_akun_id']) ? DB::table('sub_akun_biaya')->where('id', $v['sub_akun_id'])->value('name') : null,
            $akunId ? DB::table('akun_biaya')->where('id', $akunId)->value('name') : 'Semua akun',
        ])->filter()->implode(' · ');
        $scope = !empty($v['sub_divisi_id'])
            ? DB::table('sub_divisions')->where('id', $v['sub_divisi_id'])->value('name')
            : (!empty($v['division_id']) ? DB::table('divisions')->where('id', $v['division_id'])->value('name') : 'Semua divisi');

        $total = (float) $perToko->sum('total');

        return response()->json([
            'title' => $title,
            'scope' => $scope.' · '.$periode->label(),
            'total' => $total,
            'budget' => $budget,
            'pct' => $budget ? CostReport::pct($total, $budget) : null,
            'jumlah' => $jumlah,
            'per_toko' => $perToko,
            'per_kategori' => $perKategori,
            'per_bulan' => $perBulan,
            'rows' => $rows,
            'jurnal_url' => route('admin.keuangan.jurnal-umum.index', array_filter([
                'tanggal_dari' => $periode->tanggalAwal(),
                'tanggal_sampai' => $periode->tanggalAkhir(),
                'division_id' => $v['division_id'] ?? null,
                'sub_divisi_id' => $v['sub_divisi_id'] ?? null,
                'akun_id' => empty($v['sub_akun_id']) ? $akunId : null,
                'sub_akun_id' => $v['sub_akun_id'] ?? null,
            ])),
        ]);
    }

    public function export(Request $request)
    {
        $report = $this->buildReportData($request);

        $export = new LaporanExport([
            'divisions' => $report['divisions'],
            'akun_groups' => $report['akun_groups'],
            'grand_by_division' => $report['grand_by_division'],
            'grand_total_kredit' => $report['grand_total_kredit'],
            'budget_map' => $report['budget_map'],
        ]);

        $filename = 'laporan-'.now()->format('Ymd_His').'.xlsx';
        return Excel::download($export, $filename);
    }

    public function exportDivisi(Request $request)
    {
        $report = $this->buildDivisionDetailData($request);

        $export = new LaporanDivisiExport([
            'division_groups' => $report['division_groups'],
            'budget_map' => $report['budget_map'],
        ]);

        $filename = 'laporan-per-divisi-'.now()->format('Ymd_His').'.xlsx';
        return Excel::download($export, $filename);
    }

    /**
     * Simpan budget tahunan sub divisi × akun dari halaman laporan.
     * Hanya untuk periode satu tahun penuh (tampilan laporan = nilai tahunan).
     */
    public function saveSubDivisiBudget(Request $request)
    {
        $validated = $request->validate([
            'sub_divisi_id' => ['required', 'integer', 'exists:sub_divisions,id'],
            'akun_biaya_id' => ['required', 'integer', 'exists:akun_biaya,id'],
            'tahun' => ['required', 'integer', 'min:2000', 'max:2100'],
            'amount' => ['nullable', 'numeric', 'min:0'],
        ]);

        $subDivisiId = (int) $validated['sub_divisi_id'];
        $akunBiayaId = (int) $validated['akun_biaya_id'];
        $tahun = (int) $validated['tahun'];
        $amount = $validated['amount'] ?? null;

        $base = fn () => DB::table('sub_divisi_budgets')
            ->where('sub_divisi_id', $subDivisiId)
            ->where('akun_biaya_id', $akunBiayaId)
            ->where('tahun', $tahun);

        if ($base()->whereNotNull('bulan')->exists()) {
            return response()->json([
                'message' => 'Sub divisi dan akun ini memakai budget bulanan di tahun '.$tahun.', jadi tidak bisa diubah dari sini.',
            ], 422);
        }

        $lama = $base()->whereNull('bulan')->value('amount');
        $label = DB::table('sub_divisions')->where('id', $subDivisiId)->value('name').' · '
            .DB::table('akun_biaya')->where('id', $akunBiayaId)->value('name').' · Tahunan '.$tahun;
        $logChange = fn ($baru) => Audit::log($baru === null ? 'hapus' : ($lama === null ? 'tambah' : 'ubah'), 'Budget toko', $subDivisiId,
            'Budget toko '.$label.($baru === null ? ' dihapus' : ' disimpan'), ['amount' => [$lama, $baru]]);

        DB::beginTransaction();
        try {
            if ($amount === null || $amount === '') {
                $base()->whereNull('bulan')->delete();
                $logChange(null);
                DB::commit();

                return response()->json([
                    'message' => 'Budget dihapus',
                    'amount' => null,
                ]);
            }

            $now = now();
            if ($base()->whereNull('bulan')->exists()) {
                $base()->whereNull('bulan')->update([
                    'amount' => $amount,
                    'updated_at' => $now,
                ]);
            } else {
                DB::table('sub_divisi_budgets')->insert([
                    'sub_divisi_id' => $subDivisiId,
                    'akun_biaya_id' => $akunBiayaId,
                    'tahun' => $tahun,
                    'bulan' => null,
                    'amount' => $amount,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }

            $logChange((float) $amount);
            DB::commit();

            return response()->json([
                'message' => 'Budget disimpan',
                'amount' => $amount,
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Gagal menyimpan budget',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    private function buildReportData(Request $request): array
    {
        $selectedDivisionIds = $request->input('division_ids', []);
        if (!is_array($selectedDivisionIds)) {
            $selectedDivisionIds = [$selectedDivisionIds];
        }
        $legacyDivisionId = $request->integer('division_id');
        if ($legacyDivisionId > 0) {
            $selectedDivisionIds[] = $legacyDivisionId;
        }
        $selectedDivisionIds = collect($selectedDivisionIds)
            ->map(fn ($id) => (int) $id)
            ->filter(fn ($id) => $id > 0)
            ->unique()
            ->values()
            ->all();

        $selectedAkunIds = $request->input('akun_ids', []);
        if (!is_array($selectedAkunIds)) {
            $selectedAkunIds = [$selectedAkunIds];
        }
        $legacyAkunId = $request->integer('akun_id');
        if ($legacyAkunId > 0) {
            $selectedAkunIds[] = $legacyAkunId;
        }
        $selectedAkunIds = collect($selectedAkunIds)
            ->map(fn ($id) => (int) $id)
            ->filter(fn ($id) => $id > 0)
            ->unique()
            ->values()
            ->all();

        $rowsQuery = DB::table('jurnal_umum as ju')
            ->join('sub_divisions as sd', 'sd.id', '=', 'ju.sub_divisi_id')
            ->join('divisions as d', 'd.id', '=', 'sd.division_id')
            ->join('sub_akun_biaya as sab', 'sab.id', '=', 'ju.sub_akun_biaya_id')
            ->join('akun_biaya as ab', 'ab.id', '=', 'sab.akun_biaya_id')
            ->where('ab.dikecualikan', false)
            ->select(
                'd.id as division_id',
                'd.name as division',
                'ab.id as akun_id',
                'ab.name as akun',
                'sab.id as sub_akun_id',
                'sab.name as sub_akun',
                DB::raw('SUM(ju.debet) as total_debet'),
                DB::raw('SUM(ju.kredit) as total_kredit')
            )
            ->groupBy('d.id', 'd.name', 'ab.id', 'ab.name', 'sab.id', 'sab.name')
            ->orderBy('d.name')
            ->orderBy('ab.name')
            ->orderBy('sab.name');

        if (!empty($selectedDivisionIds)) {
            $rowsQuery->whereIn('d.id', $selectedDivisionIds);
        }
        if (!empty($selectedAkunIds)) {
            $rowsQuery->whereIn('ab.id', $selectedAkunIds);
        }
        $periode = Periode::fromRequest($request);
        $periode->applyToJurnal($rowsQuery);

        $rows = $rowsQuery->get();

        $divisionOptions = DB::table('divisions')
            ->orderBy('name')
            ->get(['id', 'name']);

        if (!empty($selectedDivisionIds)) {
            $divisions = $divisionOptions
                ->whereIn('id', $selectedDivisionIds)
                ->map(fn ($d) => ['id' => $d->id, 'name' => $d->name])
                ->values();
        } else {
            $divisions = $rows->map(fn ($r) => ['id' => $r->division_id, 'name' => $r->division])
                ->unique('id')
                ->sortBy('name')
                ->values();
        }

        $akunGroups = $rows->groupBy('akun_id')
            ->sortBy(fn ($items) => $items->first()->akun)
            ->map(function ($akunItems) use ($divisions) {
                $subGroups = $akunItems->groupBy('sub_akun_id')
                    ->sortBy(fn ($items) => $items->first()->sub_akun)
                    ->map(function ($subItems) use ($divisions) {
                        $byDivision = $subItems->keyBy('division_id');
                        $cells = $divisions->map(function ($div) use ($byDivision) {
                            $cell = $byDivision->get($div['id']);
                            return [
                                'debet' => (float) ($cell->total_debet ?? 0),
                                'kredit' => (float) ($cell->total_kredit ?? 0),
                            ];
                        });

                        return [
                            'sub_akun_id' => (int) $subItems->first()->sub_akun_id,
                            'sub_akun' => $subItems->first()->sub_akun,
                            'cells' => $cells,
                        ];
                    })
                    ->values();

                $totalsByDivision = $divisions->mapWithKeys(function ($div) use ($akunItems) {
                    return [
                        $div['id'] => [
                            'debet' => (float) $akunItems->where('division_id', $div['id'])->sum('total_debet'),
                            'kredit' => (float) $akunItems->where('division_id', $div['id'])->sum('total_kredit'),
                        ],
                    ];
                });

                return [
                    'akun_id' => (int) $akunItems->first()->akun_id,
                    'akun' => $akunItems->first()->akun,
                    'sub_groups' => $subGroups,
                    'totals_by_division' => $totalsByDivision,
                ];
            })
            ->values();

        $grandByDivision = $divisions->mapWithKeys(function ($div) use ($rows) {
            return [
                $div['id'] => [
                    'debet' => (float) $rows->where('division_id', $div['id'])->sum('total_debet'),
                    'kredit' => (float) $rows->where('division_id', $div['id'])->sum('total_kredit'),
                ],
            ];
        });

        $divisionIds = $divisions->pluck('id')->all();
        $akunIds = $akunGroups->pluck('akun_id')->all();

        $budgetMap = $periode->budgetMap('budgets', 'division_id', $divisionIds, $akunIds);

        return [
            'divisions' => $divisions,
            'akun_groups' => $akunGroups,
            'grand_by_division' => $grandByDivision,
            'grand_total_debet' => (float) $rows->sum('total_debet'),
            'grand_total_kredit' => (float) $rows->sum('total_kredit'),
            'budget_map' => $budgetMap,
            'selected_division_ids' => $selectedDivisionIds,
            'selected_akun_ids' => $selectedAkunIds,
        ];
    }

    private function buildDivisionDetailData(Request $request): array
    {
        $selectedDivisionIds = array_merge((array) $request->input('division_ids_divisi', []), (array) $request->input('division_ids', []));
        if (!is_array($selectedDivisionIds)) {
            $selectedDivisionIds = [$selectedDivisionIds];
        }
        $legacyDivisionId = $request->integer('division_id_divisi');
        if ($legacyDivisionId > 0) {
            $selectedDivisionIds[] = $legacyDivisionId;
        }
        $selectedDivisionIds = collect($selectedDivisionIds)
            ->map(fn ($id) => (int) $id)
            ->filter(fn ($id) => $id > 0)
            ->unique()
            ->values()
            ->all();

        $selectedAkunIds = array_merge((array) $request->input('akun_ids_divisi', []), (array) $request->input('akun_ids', []));
        if (!is_array($selectedAkunIds)) {
            $selectedAkunIds = [$selectedAkunIds];
        }
        $legacyAkunId = $request->integer('akun_id_divisi');
        if ($legacyAkunId > 0) {
            $selectedAkunIds[] = $legacyAkunId;
        }
        $selectedAkunIds = collect($selectedAkunIds)
            ->map(fn ($id) => (int) $id)
            ->filter(fn ($id) => $id > 0)
            ->unique()
            ->values()
            ->all();

        $selectedSubDivisionIds = array_merge((array) $request->input('sub_division_ids_divisi', []), (array) $request->input('sub_division_ids', []));
        if (!is_array($selectedSubDivisionIds)) {
            $selectedSubDivisionIds = [$selectedSubDivisionIds];
        }
        $legacySubDivisionId = $request->integer('sub_division_id_divisi');
        if ($legacySubDivisionId > 0) {
            $selectedSubDivisionIds[] = $legacySubDivisionId;
        }
        $selectedSubDivisionIds = collect($selectedSubDivisionIds)
            ->map(fn ($id) => (int) $id)
            ->filter(fn ($id) => $id > 0)
            ->unique()
            ->values()
            ->all();

        $selectedSubAkunIds = array_merge((array) $request->input('sub_akun_ids_divisi', []), (array) $request->input('sub_akun_ids', []));
        if (!is_array($selectedSubAkunIds)) {
            $selectedSubAkunIds = [$selectedSubAkunIds];
        }
        $legacySubAkunId = $request->integer('sub_akun_id_divisi');
        if ($legacySubAkunId > 0) {
            $selectedSubAkunIds[] = $legacySubAkunId;
        }
        $selectedSubAkunIds = collect($selectedSubAkunIds)
            ->map(fn ($id) => (int) $id)
            ->filter(fn ($id) => $id > 0)
            ->unique()
            ->values()
            ->all();

        if (!empty($selectedDivisionIds) && !empty($selectedSubDivisionIds)) {
            $selectedSubDivisionIds = DB::table('sub_divisions')
                ->whereIn('id', $selectedSubDivisionIds)
                ->whereIn('division_id', $selectedDivisionIds)
                ->pluck('id')
                ->map(fn ($id) => (int) $id)
                ->all();
        }
        if (!empty($selectedAkunIds) && !empty($selectedSubAkunIds)) {
            $selectedSubAkunIds = DB::table('sub_akun_biaya')
                ->whereIn('id', $selectedSubAkunIds)
                ->whereIn('akun_biaya_id', $selectedAkunIds)
                ->pluck('id')
                ->map(fn ($id) => (int) $id)
                ->all();
        }

        $rowsQuery = DB::table('jurnal_umum as ju')
            ->join('sub_divisions as sd', 'sd.id', '=', 'ju.sub_divisi_id')
            ->join('divisions as d', 'd.id', '=', 'sd.division_id')
            ->join('sub_akun_biaya as sab', 'sab.id', '=', 'ju.sub_akun_biaya_id')
            ->join('akun_biaya as ab', 'ab.id', '=', 'sab.akun_biaya_id')
            ->where('ab.dikecualikan', false)
            ->select(
                'd.id as division_id',
                'd.name as division',
                'sd.id as sub_divisi_id',
                'sd.name as sub_divisi',
                'ab.id as akun_id',
                'ab.name as akun',
                'sab.id as sub_akun_id',
                'sab.name as sub_akun',
                DB::raw('SUM(ju.kredit) as total_kredit')
            )
            ->groupBy('d.id', 'd.name', 'sd.id', 'sd.name', 'ab.id', 'ab.name', 'sab.id', 'sab.name')
            ->orderBy('d.name')
            ->orderBy('ab.name')
            ->orderBy('sab.name')
            ->orderBy('sd.name');

        if (!empty($selectedDivisionIds)) {
            $rowsQuery->whereIn('d.id', $selectedDivisionIds);
        }
        if (!empty($selectedAkunIds)) {
            $rowsQuery->whereIn('ab.id', $selectedAkunIds);
        }
        if (!empty($selectedSubDivisionIds)) {
            $rowsQuery->whereIn('sd.id', $selectedSubDivisionIds);
        }
        if (!empty($selectedSubAkunIds)) {
            $rowsQuery->whereIn('sab.id', $selectedSubAkunIds);
        }
        $periode = Periode::fromRequest($request);
        $periode->applyToJurnal($rowsQuery);

        $rows = $rowsQuery->get();

        $akunIds = $rows->pluck('akun_id')->unique()->values()->all();
        $subDivisiIds = $rows->pluck('sub_divisi_id')->unique()->values()->all();
        $budgetMap = $periode->budgetMap('sub_divisi_budgets', 'sub_divisi_id', $subDivisiIds, $akunIds);

        $divisionGroups = $rows->groupBy('division_id')
            ->map(function ($divisionRows) {
                $divisionName = $divisionRows->first()->division;
                $subDivisions = $divisionRows->map(fn ($row) => [
                    'id' => (int) $row->sub_divisi_id,
                    'name' => $row->sub_divisi,
                ])
                    ->unique('id')
                    ->sortBy('name')
                    ->values();

                $akunGroups = $divisionRows->groupBy('akun_id')
                    ->sortBy(fn ($items) => $items->first()->akun)
                    ->map(function ($akunItems) use ($subDivisions) {
                        $subGroups = $akunItems->groupBy('sub_akun_id')
                            ->sortBy(fn ($items) => $items->first()->sub_akun)
                            ->map(function ($subItems) use ($subDivisions) {
                                $bySubDivisi = $subItems->keyBy('sub_divisi_id');
                                $cells = $subDivisions->map(function ($subDiv) use ($bySubDivisi) {
                                    $cell = $bySubDivisi->get($subDiv['id']);
                                    return [
                                        'kredit' => (float) ($cell->total_kredit ?? 0),
                                    ];
                                });

                                return [
                                    'sub_akun_id' => (int) $subItems->first()->sub_akun_id,
                                    'sub_akun' => $subItems->first()->sub_akun,
                                    'cells' => $cells,
                                ];
                            })
                            ->values();

                        $totalsBySubDivisi = $subDivisions->mapWithKeys(function ($subDiv) use ($akunItems) {
                            return [
                                $subDiv['id'] => [
                                    'kredit' => (float) $akunItems->where('sub_divisi_id', $subDiv['id'])->sum('total_kredit'),
                                ],
                            ];
                        });

                        return [
                            'akun_id' => (int) $akunItems->first()->akun_id,
                            'akun' => $akunItems->first()->akun,
                            'sub_groups' => $subGroups,
                            'totals_by_sub_divisi' => $totalsBySubDivisi,
                        ];
                    })
                    ->values();

                $grandBySubDivisi = $subDivisions->mapWithKeys(function ($subDiv) use ($divisionRows) {
                    return [
                        $subDiv['id'] => [
                            'kredit' => (float) $divisionRows->where('sub_divisi_id', $subDiv['id'])->sum('total_kredit'),
                        ],
                    ];
                });

                return [
                    'division_id' => (int) $divisionRows->first()->division_id,
                    'division' => $divisionName,
                    'sub_divisions' => $subDivisions,
                    'akun_groups' => $akunGroups,
                    'grand_by_sub_divisi' => $grandBySubDivisi,
                    'grand_total_kredit' => (float) $divisionRows->sum('total_kredit'),
                ];
            })
            ->sortBy('division')
            ->values();

        return [
            'division_groups' => $divisionGroups,
            'selected_division_ids' => $selectedDivisionIds,
            'selected_sub_division_ids' => $selectedSubDivisionIds,
            'selected_akun_ids' => $selectedAkunIds,
            'selected_sub_akun_ids' => $selectedSubAkunIds,
            'budget_map' => $budgetMap,
        ];
    }
}
