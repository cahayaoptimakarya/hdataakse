<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\JurnalImportBatch;
use App\Services\CostReport;
use App\Support\Periode;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        // Default: tahun berjalan, Januari s/d bulan ini.
        $periode = $request->filled('tahun')
            ? Periode::fromRequest($request)
            : new Periode((int) now()->year, 1, (int) now()->month);
        if ($periode->isSemua()) {
            $periode = new Periode((int) now()->year, 1, (int) now()->month);
        }

        $report = new CostReport($periode);
        $matrix = $report->matrix();
        $positions = $report->positions($matrix);

        $tahunLalu = new Periode($periode->tahun - 1, $periode->bulanDari, $periode->bulanSampai);
        $realisasiLalu = (new CostReport($tahunLalu))->matrix()['total_realisasi'];

        return view('admin.dashboard', [
            'periode' => $periode,
            'tahunOptions' => Periode::tahunOptions(),
            'namaBulan' => Periode::NAMA_BULAN,
            'matrix' => $matrix,
            'positions' => $positions,
            'overCount' => $positions->filter(fn ($p) => CostReport::status($p->pct, $p->realisasi) === 'bad')->count(),
            'warnCount' => $positions->filter(fn ($p) => CostReport::status($p->pct, $p->realisasi) === 'warn')->count(),
            'realisasiLalu' => $realisasiLalu,
            'monthly' => CostReport::monthly($periode->tahun),
            'budgetSetahun' => collect((new Periode($periode->tahun))->budgetMap(
                'budgets', 'division_id', DB::table('divisions')->pluck('id')->all(), DB::table('akun_biaya')->where('id', '!=', 1)->whereRaw('LOWER(name) != ?', ['kas'])->pluck('id')->all()
            ))->flatten()->sum(),
            'batches' => JurnalImportBatch::with('user:id,name')->orderByDesc('id')->limit(6)->get(),
            'undatedCount' => DB::table('jurnal_umum')->whereNull('tanggal')->count(),
        ]);
    }
}
