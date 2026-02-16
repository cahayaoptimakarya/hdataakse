<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;

class LaporanJurnalUmumController extends Controller
{
    public function index()
    {
        $rows = DB::table('jurnal_umum as ju')
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
            ->orderBy('sab.name')
            ->get();

        $divisions = $rows->map(fn ($r) => ['id' => $r->division_id, 'name' => $r->division])
            ->unique('id')
            ->sortBy('name')
            ->values();

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
                            'sub_akun' => $subItems->first()->sub_akun,
                            'cells' => $cells,
                        ];
                    })
                    ->values();

                $totalsByDivision = $divisions->map(function ($div) use ($akunItems) {
                    return [
                        'debet' => (float) $akunItems->where('division_id', $div['id'])->sum('total_debet'),
                        'kredit' => (float) $akunItems->where('division_id', $div['id'])->sum('total_kredit'),
                    ];
                });

                return [
                    'akun' => $akunItems->first()->akun,
                    'sub_groups' => $subGroups,
                    'totals_by_division' => $totalsByDivision,
                ];
            })
            ->values();

        $grandByDivision = $divisions->map(function ($div) use ($rows) {
            return [
                'debet' => (float) $rows->where('division_id', $div['id'])->sum('total_debet'),
                'kredit' => (float) $rows->where('division_id', $div['id'])->sum('total_kredit'),
            ];
        });

        return view('admin.keuangan.laporan-jurnal-umum.index', [
            'divisions' => $divisions,
            'akun_groups' => $akunGroups,
            'grand_by_division' => $grandByDivision,
            'grand_total_debet' => (float) $rows->sum('total_debet'),
            'grand_total_kredit' => (float) $rows->sum('total_kredit'),
        ]);
    }
}
