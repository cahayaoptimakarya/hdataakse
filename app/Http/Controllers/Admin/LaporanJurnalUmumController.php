<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Exports\LaporanDivisiExport;
use App\Exports\LaporanExport;
use App\Models\SubAkunBiaya;
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

        return view('admin.keuangan.laporan-jurnal-umum.index', [
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

    public function saveSubDivisiBudget(Request $request)
    {
        $validated = $request->validate([
            'sub_divisi_id' => ['required', 'integer', 'exists:sub_divisions,id'],
            'akun_biaya_id' => ['required', 'integer', 'exists:akun_biaya,id'],
            'amount' => ['nullable', 'numeric', 'min:0'],
        ]);

        DB::beginTransaction();
        try {
            $subDivisiId = (int) $validated['sub_divisi_id'];
            $akunBiayaId = (int) $validated['akun_biaya_id'];
            $amount = $validated['amount'];

            if ($amount === null || $amount === '') {
                DB::table('sub_divisi_budgets')
                    ->where('sub_divisi_id', $subDivisiId)
                    ->where('akun_biaya_id', $akunBiayaId)
                    ->delete();
                DB::commit();

                return response()->json([
                    'message' => 'Budget dihapus',
                    'amount' => null,
                ]);
            }

            $now = now();
            $exists = DB::table('sub_divisi_budgets')
                ->where('sub_divisi_id', $subDivisiId)
                ->where('akun_biaya_id', $akunBiayaId)
                ->exists();

            if ($exists) {
                DB::table('sub_divisi_budgets')
                    ->where('sub_divisi_id', $subDivisiId)
                    ->where('akun_biaya_id', $akunBiayaId)
                    ->update([
                        'amount' => $amount,
                        'updated_at' => $now,
                    ]);
            } else {
                DB::table('sub_divisi_budgets')->insert([
                    'sub_divisi_id' => $subDivisiId,
                    'akun_biaya_id' => $akunBiayaId,
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

        $budgetRows = [];
        if (!empty($divisionIds) && !empty($akunIds)) {
            $budgetRows = DB::table('budgets')
                ->whereIn('division_id', $divisionIds)
                ->whereIn('akun_biaya_id', $akunIds)
                ->get(['division_id', 'akun_biaya_id', 'amount']);
        }

        $budgetMap = [];
        foreach ($budgetRows as $row) {
            $budgetMap[$row->akun_biaya_id][$row->division_id] = (float) $row->amount;
        }

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

        $rows = $rowsQuery->get();

        $akunIds = $rows->pluck('akun_id')->unique()->values()->all();
        $subDivisiIds = $rows->pluck('sub_divisi_id')->unique()->values()->all();
        $budgetRows = [];
        if (!empty($subDivisiIds) && !empty($akunIds)) {
            $budgetRows = DB::table('sub_divisi_budgets')
                ->whereIn('sub_divisi_id', $subDivisiIds)
                ->whereIn('akun_biaya_id', $akunIds)
                ->get(['sub_divisi_id', 'akun_biaya_id', 'amount']);
        }
        $budgetMap = [];
        foreach ($budgetRows as $row) {
            $budgetMap[$row->akun_biaya_id][$row->sub_divisi_id] = (float) $row->amount;
        }

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
            'budget_map' => $budgetMap,
        ];
    }
}
