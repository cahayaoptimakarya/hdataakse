<?php

namespace App\Services;

use App\Support\Periode;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Angka realisasi vs budget per akun × divisi, dipakai dashboard dan laporan.
 * Realisasi = jumlah kolom kredit jurnal (sama dengan laporan sebelumnya).
 * Akun id 1 / "kas" tidak dihitung sebagai biaya.
 */
class CostReport
{
    public function __construct(private Periode $periode)
    {
    }

    public function periode(): Periode
    {
        return $this->periode;
    }

    private function baseJurnal()
    {
        $q = DB::table('jurnal_umum as ju')
            ->join('sub_divisions as sd', 'sd.id', '=', 'ju.sub_divisi_id')
            ->join('divisions as d', 'd.id', '=', 'sd.division_id')
            ->join('sub_akun_biaya as sab', 'sab.id', '=', 'ju.sub_akun_biaya_id')
            ->join('akun_biaya as ab', 'ab.id', '=', 'sab.akun_biaya_id')
            ->where('ab.id', '!=', 1)
            ->whereRaw('LOWER(ab.name) != ?', ['kas']);
        $this->periode->applyToJurnal($q);

        return $q;
    }

    /**
     * Matriks akun × divisi.
     *
     * @return array{
     *   divisions: Collection, akun: Collection, cells: array, budget: array,
     *   div_totals: array, akun_totals: array, total_realisasi: float, total_budget: float
     * }
     */
    public function matrix(array $divisionIds = [], array $akunIds = []): array
    {
        $q = $this->baseJurnal()
            ->select(
                'd.id as division_id', 'ab.id as akun_id', 'sab.id as sub_akun_id',
                DB::raw('SUM(ju.kredit) as realisasi'), DB::raw('COUNT(*) as jumlah')
            )
            ->groupBy('d.id', 'ab.id', 'sab.id');
        if ($divisionIds) {
            $q->whereIn('d.id', $divisionIds);
        }
        if ($akunIds) {
            $q->whereIn('ab.id', $akunIds);
        }
        $rows = $q->get();

        $divisions = DB::table('divisions')
            ->when($divisionIds, fn ($x) => $x->whereIn('id', $divisionIds))
            ->orderBy('name')->get(['id', 'name']);
        $akunList = DB::table('akun_biaya')
            ->where('id', '!=', 1)->whereRaw('LOWER(name) != ?', ['kas'])
            ->when($akunIds, fn ($x) => $x->whereIn('id', $akunIds))
            ->orderBy('name')->get(['id', 'name']);
        $subAkun = DB::table('sub_akun_biaya')->whereIn('akun_biaya_id', $akunList->pluck('id'))
            ->orderBy('name')->get(['id', 'name', 'akun_biaya_id'])->groupBy('akun_biaya_id');

        $budget = $this->periode->budgetMap('budgets', 'division_id', $divisions->pluck('id')->all(), $akunList->pluck('id')->all());

        // cells[akun][sub|'_'][div] = realisasi
        $cells = [];
        foreach ($rows as $r) {
            $cells[$r->akun_id][$r->sub_akun_id][$r->division_id] = (float) $r->realisasi;
            $cells[$r->akun_id]['_'][$r->division_id] = ($cells[$r->akun_id]['_'][$r->division_id] ?? 0) + (float) $r->realisasi;
        }

        // Tampilkan hanya akun yang punya realisasi atau budget, dan divisi yang punya salah satunya.
        $akun = $akunList->filter(fn ($a) => isset($cells[$a->id]) || isset($budget[$a->id]))->values()
            ->map(function ($a) use ($subAkun, $cells) {
                $a->subs = collect($subAkun[$a->id] ?? [])->filter(fn ($s) => isset($cells[$a->id][$s->id]))->values();
                return $a;
            });
        if (!$divisionIds) {
            $used = collect($rows)->pluck('division_id')->merge(collect($budget)->flatMap(fn ($m) => array_keys($m)))->unique();
            $divisions = $divisions->whereIn('id', $used)->values();
        }

        $divTotals = [];
        $akunTotals = [];
        foreach ($akun as $a) {
            foreach ($divisions as $d) {
                $r = $cells[$a->id]['_'][$d->id] ?? 0;
                $b = $budget[$a->id][$d->id] ?? 0;
                $divTotals[$d->id]['r'] = ($divTotals[$d->id]['r'] ?? 0) + $r;
                $divTotals[$d->id]['b'] = ($divTotals[$d->id]['b'] ?? 0) + $b;
                $akunTotals[$a->id]['r'] = ($akunTotals[$a->id]['r'] ?? 0) + $r;
                $akunTotals[$a->id]['b'] = ($akunTotals[$a->id]['b'] ?? 0) + $b;
            }
        }

        return [
            'divisions' => $divisions,
            'akun' => $akun,
            'cells' => $cells,
            'budget' => $budget,
            'div_totals' => $divTotals,
            'akun_totals' => $akunTotals,
            'total_realisasi' => array_sum(array_column($divTotals, 'r')),
            'total_budget' => array_sum(array_column($divTotals, 'b')),
        ];
    }

    /** Pos divisi × akun, urut dari persentase serapan tertinggi. */
    public function positions(array $matrix): Collection
    {
        $out = collect();
        foreach ($matrix['akun'] as $a) {
            foreach ($matrix['divisions'] as $d) {
                $r = $matrix['cells'][$a->id]['_'][$d->id] ?? 0;
                $b = $matrix['budget'][$a->id][$d->id] ?? 0;
                if ($r == 0 && $b == 0) {
                    continue;
                }
                $out->push((object) [
                    'akun_id' => $a->id, 'akun' => $a->name, 'division_id' => $d->id, 'division' => $d->name,
                    'realisasi' => $r, 'budget' => $b, 'pct' => self::pct($r, $b),
                ]);
            }
        }

        return $out->sortByDesc(fn ($p) => $p->pct ?? INF)->values();
    }

    /**
     * Realisasi dan budget per bulan dalam satu tahun (untuk grafik tren).
     *
     * @return array<int, array{realisasi: float, budget: float}>  kunci 1..12
     */
    public static function monthly(int $tahun, array $divisionIds = [], array $akunIds = []): array
    {
        $months = [];
        $divs = $divisionIds ?: DB::table('divisions')->pluck('id')->all();
        $akun = $akunIds ?: DB::table('akun_biaya')->where('id', '!=', 1)->whereRaw('LOWER(name) != ?', ['kas'])->pluck('id')->all();
        foreach (range(1, 12) as $m) {
            $p = new Periode($tahun, $m, $m);
            $q = (new self($p))->baseJurnal();
            if ($divisionIds) {
                $q->whereIn('d.id', $divisionIds);
            }
            if ($akunIds) {
                $q->whereIn('ab.id', $akunIds);
            }
            $budget = collect($p->budgetMap('budgets', 'division_id', $divs, $akun))->flatten()->sum();
            $months[$m] = ['realisasi' => (float) $q->sum('ju.kredit'), 'budget' => (float) $budget];
        }

        return $months;
    }

    /**
     * Realisasi dan budget per akun per bulan dalam satu tahun.
     *
     * @return array{akun: Collection, r: array, b: array}  r/b[akun_id][1..12]
     */
    public static function monthlyByAkun(int $tahun, array $divisionIds = [], array $akunIds = []): array
    {
        $q = (new self(new Periode($tahun)))->baseJurnal()
            ->select('ab.id as akun_id', 'ju.tanggal', DB::raw('SUM(ju.kredit) as total'))
            ->groupBy('ab.id', 'ju.tanggal');
        if ($divisionIds) {
            $q->whereIn('d.id', $divisionIds);
        }
        if ($akunIds) {
            $q->whereIn('ab.id', $akunIds);
        }
        $r = [];
        foreach ($q->get() as $row) {
            $m = (int) substr((string) $row->tanggal, 5, 2);
            $r[$row->akun_id][$m] = ($r[$row->akun_id][$m] ?? 0) + (float) $row->total;
        }

        $divs = $divisionIds ?: DB::table('divisions')->pluck('id')->all();
        $akunList = DB::table('akun_biaya')->where('id', '!=', 1)->whereRaw('LOWER(name) != ?', ['kas'])
            ->when($akunIds, fn ($x) => $x->whereIn('id', $akunIds))
            ->orderBy('name')->get(['id', 'name']);
        $b = [];
        foreach (range(1, 12) as $m) {
            foreach ((new Periode($tahun, $m, $m))->budgetMap('budgets', 'division_id', $divs, $akunList->pluck('id')->all()) as $akunId => $perDiv) {
                $b[$akunId][$m] = array_sum($perDiv);
            }
        }

        return [
            'akun' => $akunList->filter(fn ($a) => isset($r[$a->id]) || isset($b[$a->id]))->values(),
            'r' => $r,
            'b' => $b,
        ];
    }

    public static function pct(float $realisasi, float $budget): ?float
    {
        return $budget > 0 ? $realisasi / $budget * 100 : null;
    }

    /** ok | warn | bad | none */
    public static function status(?float $pct, float $realisasi = 0): string
    {
        if ($pct === null) {
            return $realisasi > 0 ? 'bad' : 'none';
        }
        return $pct > 100 ? 'bad' : ($pct >= 80 ? 'warn' : 'ok');
    }
}
