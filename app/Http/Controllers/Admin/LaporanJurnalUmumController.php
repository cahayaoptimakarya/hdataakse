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

        $grouped = $rows->groupBy('division_id')->map(function ($items) {
            $akunGroups = $items->groupBy('akun_id')->map(function ($akunItems) {
                return [
                    'akun' => $akunItems->first()->akun,
                    'rows' => $akunItems,
                    'total_debet' => $akunItems->sum('total_debet'),
                    'total_kredit' => $akunItems->sum('total_kredit'),
                ];
            });

            return [
                'division' => $items->first()->division,
                'akun_groups' => $akunGroups,
                'total_debet' => $items->sum('total_debet'),
                'total_kredit' => $items->sum('total_kredit'),
            ];
        });

        return view('admin.keuangan.laporan-jurnal-umum.index', [
            'grouped' => $grouped,
            'grand_total_debet' => $rows->sum('total_debet'),
            'grand_total_kredit' => $rows->sum('total_kredit'),
        ]);
    }
}
