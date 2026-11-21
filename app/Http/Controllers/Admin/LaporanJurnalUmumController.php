<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Exports\LaporanDivisiExport;
use App\Exports\LaporanExport;
use App\Models\SubAkunBiaya;
use App\Support\Periode;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;

class LaporanJurnalUmumController extends Controller
{
    public function index(Request $request)
    {
        $report = $this->buildReportData($request);
        $divisionReport = $this->buildDivisionDetailData($request);

        $divisionOptions = DB::table('divisions')
            ->orderBy('name')
            ->get(['id', 'name']);

        $akunOptions = DB::table('akun_biaya')
            ->where('id', '!=', 1)
            ->whereRaw('LOWER(name) != ?', ['kas'])
            ->orderBy('name')
            ->get(['id', 'name']);

        $akunOptionsDivisi = $akunOptions;

        $subDivisionOptionsQuery = DB::table('sub_divisions as sd')
            ->join('divisions as d', 'd.id', '=', 'sd.division_id');
        if (!empty($divisionReport['selected_division_ids'])) {
            $subDivisionOptionsQuery->whereIn('d.id', $divisionReport['selected_division_ids']);
        }
        $subDivisionOptions = $subDivisionOptionsQuery
            ->orderBy('d.name')
            ->orderBy('sd.name')
            ->get([
                'sd.id',
                'sd.name',
                'sd.division_id',
                'd.name as division_name',
            ]);

        $subAkunOptionsQuery = DB::table('sub_akun_biaya as sab')
            ->join('akun_biaya as ab', 'ab.id', '=', 'sab.akun_biaya_id')
            ->where('ab.id', '!=', 1)
            ->whereRaw('LOWER(ab.name) != ?', ['kas']);
        if (!empty($divisionReport['selected_akun_ids'])) {
            $subAkunOptionsQuery->whereIn('ab.id', $divisionReport['selected_akun_ids']);
        }
        $subAkunOptions = $subAkunOptionsQuery
            ->orderBy('ab.name')
            ->orderBy('sab.name')
            ->get([
                'sab.id',
                'sab.name',
                'sab.akun_biaya_id',
                'ab.name as akun_name',
            ]);

        $periode = Periode::fromRequest($request);
        $undatedCount = $periode->isSemua()
            ? 0
            : DB::table('jurnal_umum')->whereNull('tanggal')->count();

        return view('admin.keuangan.laporan-jurnal-umum.index', [
            'periode' => $periode,
            'tahun_options' => Periode::tahunOptions(),
            'nama_bulan' => Periode::NAMA_BULAN,
            'undated_count' => $undatedCount,
            'budget_editable' => !$periode->isSemua() && $periode->jumlahBulan() === 12,
            'divisions' => $report['divisions'],
            'akun_groups' => $report['akun_groups'],
            'grand_by_division' => $report['grand_by_division'],
            'grand_total_debet' => $report['grand_total_debet'],
            'grand_total_kredit' => $report['grand_total_kredit'],
            'division_options' => $divisionOptions,
            'selected_division_ids' => $report['selected_division_ids'],
            'akun_options' => $akunOptions,
            'selected_akun_ids' => $report['selected_akun_ids'],
            'budget_map' => $report['budget_map'],
            'division_detail_groups' => $divisionReport['division_groups'],
            'selected_division_ids_divisi' => $divisionReport['selected_division_ids'],
            'sub_divisi_budget_map' => $divisionReport['budget_map'],
            'sub_division_options' => $subDivisionOptions,
            'selected_sub_division_ids_divisi' => $divisionReport['selected_sub_division_ids'],
            'akun_options_divisi' => $akunOptionsDivisi,
            'selected_akun_ids_divisi' => $divisionReport['selected_akun_ids'],
            'sub_akun_options_divisi' => $subAkunOptions,
            'selected_sub_akun_ids_divisi' => $divisionReport['selected_sub_akun_ids'],
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

    public function subAkunJurnal(Request $request, SubAkunBiaya $subAkunBiaya)
    {
        $selectedDivisionIds = $request->input('division_ids', []);
        if (!is_array($selectedDivisionIds)) {
            $selectedDivisionIds = [$selectedDivisionIds];
        }
        $selectedDivisionIdsDivisi = $request->input('division_ids_divisi', []);
        if (!is_array($selectedDivisionIdsDivisi)) {
            $selectedDivisionIdsDivisi = [$selectedDivisionIdsDivisi];
        }
        $selectedDivisionIds = collect(array_merge($selectedDivisionIds, $selectedDivisionIdsDivisi))
            ->map(fn ($id) => (int) $id)
            ->filter(fn ($id) => $id > 0)
            ->unique()
            ->values()
            ->all();

        $legacyDivisionId = $request->integer('division_id');
        if ($legacyDivisionId > 0) {
            $selectedDivisionIds[] = $legacyDivisionId;
        }
        $legacyDivisionIdDivisi = $request->integer('division_id_divisi');
        if ($legacyDivisionIdDivisi > 0) {
            $selectedDivisionIds[] = $legacyDivisionIdDivisi;
        }
        $selectedDivisionIds = collect($selectedDivisionIds)
            ->map(fn ($id) => (int) $id)
            ->filter(fn ($id) => $id > 0)
            ->unique()
            ->values()
            ->all();

        $query = DB::table('jurnal_umum as ju')
            ->join('sub_divisions as sd', 'sd.id', '=', 'ju.sub_divisi_id')
            ->join('divisions as d', 'd.id', '=', 'sd.division_id')
            ->join('sub_akun_biaya as sab', 'sab.id', '=', 'ju.sub_akun_biaya_id')
            ->join('akun_biaya as ab', 'ab.id', '=', 'sab.akun_biaya_id')
            ->where('ju.sub_akun_biaya_id', $subAkunBiaya->id)
            ->select(
                'ju.id',
                'ju.tanggal',
                'ju.keterangan',
                'sd.name as toko',
                'd.name as division',
                'ju.debet',
                'ju.kredit',
                'ab.name as akun',
                'sab.name as sub_akun'
            )
            ->orderBy('ju.tanggal', 'desc')
            ->orderBy('ju.id', 'desc');

        if (!empty($selectedDivisionIds)) {
            $query->whereIn('d.id', $selectedDivisionIds);
        }
        $periode = Periode::fromRequest($request);
        $periode->applyToJurnal($query);

        $rows = $query->get();

        $summaryQuery = DB::table('jurnal_umum as ju')
            ->join('sub_divisions as sd', 'sd.id', '=', 'ju.sub_divisi_id')
            ->join('divisions as d', 'd.id', '=', 'sd.division_id')
            ->where('ju.sub_akun_biaya_id', $subAkunBiaya->id)
            ->select(
                'd.id as division_id',
                'd.name as division',
                'sd.id as sub_divisi_id',
                'sd.name as sub_divisi',
                DB::raw('SUM(ju.debet) as total_debet'),
                DB::raw('SUM(ju.kredit) as total_kredit')
            )
            ->groupBy('d.id', 'd.name', 'sd.id', 'sd.name')
            ->orderBy('d.name')
            ->orderBy('sd.name');

        if (!empty($selectedDivisionIds)) {
            $summaryQuery->whereIn('d.id', $selectedDivisionIds);
        }
        $periode->applyToJurnal($summaryQuery);

        $summaryRows = $summaryQuery->get();

        $subAkunBiaya->loadMissing('akunBiaya');

        return response()->json([
            'akun' => $subAkunBiaya->akunBiaya?->name,
            'sub_akun' => $subAkunBiaya->name,
            'rows' => $rows,
            'summary_rows' => $summaryRows,
            'summary_total_debet' => (float) $summaryRows->sum('total_debet'),
            'summary_total_kredit' => (float) $summaryRows->sum('total_kredit'),
            'total_debet' => (float) $rows->sum('debet'),
            'total_kredit' => (float) $rows->sum('kredit'),
        ]);
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

        DB::beginTransaction();
        try {
            if ($amount === null || $amount === '') {
                $base()->whereNull('bulan')->delete();
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
            ->where('ab.id', '!=', 1)
            ->whereRaw('LOWER(ab.name) != ?', ['kas'])
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
        $selectedDivisionIds = $request->input('division_ids_divisi', []);
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

        $selectedAkunIds = $request->input('akun_ids_divisi', []);
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

        $selectedSubDivisionIds = $request->input('sub_division_ids_divisi', []);
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

        $selectedSubAkunIds = $request->input('sub_akun_ids_divisi', []);
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
            ->where('ab.id', '!=', 1)
            ->whereRaw('LOWER(ab.name) != ?', ['kas'])
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
