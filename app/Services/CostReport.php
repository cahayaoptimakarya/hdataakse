<?php

namespace App\Services;

use App\Support\Periode;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Angka realisasi vs budget per akun × divisi, dipakai dashboard dan laporan.
 * Realisasi = jumlah kolom kredit jurnal (sama dengan laporan sebelumnya).
 * Akun yang ditandai "dikecualikan" (misalnya kas) tidak dihitung sebagai biaya.
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
            ->where('ab.dikecualikan', false);
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
            ->where('dikecualikan', false)
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
        $akun = $akunIds ?: DB::table('akun_biaya')->where('dikecualikan', false)->pluck('id')->all();
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

    /** Realisasi saja per bulan (1..12), satu query. Dipakai untuk pembanding tahun lalu. */
    public static function monthlyRealisasi(int $tahun, array $divisionIds = [], array $akunIds = []): array
    {
        $q = (new self(new Periode($tahun)))->baseJurnal()
            ->select('ju.tanggal', DB::raw('SUM(ju.kredit) as total'))->groupBy('ju.tanggal');
        if ($divisionIds) {
            $q->whereIn('d.id', $divisionIds);
        }
        if ($akunIds) {
            $q->whereIn('ab.id', $akunIds);
        }
        $out = array_fill(1, 12, 0.0);
        foreach ($q->get() as $r) {
            $out[(int) substr((string) $r->tanggal, 5, 2)] += (float) $r->total;
        }
        return $out;
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
        $akunList = DB::table('akun_biaya')->where('dikecualikan', false)
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

    /**
     * Bandingkan beberapa periode berdampingan.
     *
     * @param  Periode[]  $periodes   periode pertama adalah pembanding dasar
     * @param  string     $kelompok   akun | divisi | toko
     * @return array{rows: Collection, totals: array}
     *   rows: {key, name, sub (nama induk/divisi), level (0 induk, 1 kategori), akun_id, sub_akun_id, division_id, sub_divisi_id, r: [i => realisasi], b: [i => budget|null]}
     */
    public static function compare(array $periodes, string $kelompok, array $divisionIds = [], array $akunIds = []): array
    {
        $divs = $divisionIds ?: DB::table('divisions')->pluck('id')->all();
        $akunList = DB::table('akun_biaya')->where('dikecualikan', false)
            ->when($akunIds, fn ($x) => $x->whereIn('id', $akunIds))->pluck('id')->all();
        $tokoIds = DB::table('sub_divisions')->whereIn('division_id', $divs)->pluck('id')->all();

        $rows = [];
        $put = function (string $key, array $meta, int $i, float $r) use (&$rows) {
            $rows[$key] ??= $meta + ['r' => [], 'b' => []];
            $rows[$key]['r'][$i] = ($rows[$key]['r'][$i] ?? 0) + $r;
        };
        $putBudget = function (string $key, array $meta, int $i, float $b) use (&$rows) {
            $rows[$key] ??= $meta + ['r' => [], 'b' => []];
            $rows[$key]['b'][$i] = ($rows[$key]['b'][$i] ?? 0) + $b;
        };

        foreach (array_values($periodes) as $i => $p) {
            $q = (new self($p))->baseJurnal();
            if ($divisionIds) {
                $q->whereIn('d.id', $divisionIds);
            }
            if ($akunIds) {
                $q->whereIn('ab.id', $akunIds);
            }

            if ($kelompok === 'divisi') {
                foreach ($q->select('d.id', 'd.name', DB::raw('SUM(ju.kredit) as total'))->groupBy('d.id', 'd.name')->get() as $r) {
                    $put('d'.$r->id, ['name' => $r->name, 'sub' => null, 'level' => 0, 'division_id' => $r->id], $i, (float) $r->total);
                }
                foreach ($p->budgetMap('budgets', 'division_id', $divs, $akunList) as $perDiv) {
                    foreach ($perDiv as $divId => $amount) {
                        $putBudget('d'.$divId, ['level' => 0, 'division_id' => $divId], $i, $amount);
                    }
                }
            } elseif ($kelompok === 'toko') {
                foreach ($q->select('sd.id', 'sd.name', 'd.name as division', DB::raw('SUM(ju.kredit) as total'))->groupBy('sd.id', 'sd.name', 'd.name')->get() as $r) {
                    $put('t'.$r->id, ['name' => $r->name, 'sub' => $r->division, 'level' => 0, 'sub_divisi_id' => $r->id], $i, (float) $r->total);
                }
                foreach ($p->budgetMap('sub_divisi_budgets', 'sub_divisi_id', $tokoIds, $akunList) as $perToko) {
                    foreach ($perToko as $tokoId => $amount) {
                        $putBudget('t'.$tokoId, ['level' => 0, 'sub_divisi_id' => $tokoId], $i, $amount);
                    }
                }
            } else {
                $data = $q->select('ab.id as akun_id', 'ab.name as akun', 'sab.id as sub_id', 'sab.name as sub', DB::raw('SUM(ju.kredit) as total'))
                    ->groupBy('ab.id', 'ab.name', 'sab.id', 'sab.name')->get();
                foreach ($data as $r) {
                    $put('a'.$r->akun_id, ['name' => $r->akun, 'sub' => null, 'level' => 0, 'akun_id' => $r->akun_id], $i, (float) $r->total);
                    $put('a'.$r->akun_id.'-'.$r->sub_id, ['name' => $r->sub, 'sub' => $r->akun, 'level' => 1, 'akun_id' => $r->akun_id, 'sub_akun_id' => $r->sub_id], $i, (float) $r->total);
                }
                foreach ($p->budgetMap('budgets', 'division_id', $divs, $akunList) as $akunId => $perDiv) {
                    $putBudget('a'.$akunId, ['level' => 0, 'akun_id' => $akunId], $i, array_sum($perDiv));
                }
            }
        }

        // Lengkapi nama untuk baris yang hanya punya budget.
        $names = [
            'a' => DB::table('akun_biaya')->pluck('name', 'id'),
            'd' => DB::table('divisions')->pluck('name', 'id'),
            't' => DB::table('sub_divisions')->pluck('name', 'id'),
        ];
        $n = count($periodes);
        $out = collect($rows)->map(function ($row, $key) use ($names, $n) {
            $row['key'] = $key;
            $row['name'] ??= $names[$key[0]][(int) substr($key, 1)] ?? '?';
            $row['sub'] ??= null;
            foreach (range(0, $n - 1) as $i) {
                $row['r'][$i] = $row['r'][$i] ?? 0.0;
                $row['b'][$i] = $row['level'] === 0 ? ($row['b'][$i] ?? 0.0) : null;
            }
            ksort($row['r']);
            ksort($row['b']);
            return (object) $row;
        });

        // Urutkan induk menurut realisasi periode dasar terbesar, kategori di bawah induknya.
        $parents = $out->where('level', 0)->sortByDesc(fn ($r) => [$r->r[0], array_sum($r->r)])->values();
        $sorted = collect();
        foreach ($parents as $parent) {
            $sorted->push($parent);
            if ($kelompok === 'akun') {
                $out->where('level', 1)->where('akun_id', $parent->akun_id)
                    ->sortByDesc(fn ($r) => [$r->r[0], array_sum($r->r)])->each(fn ($c) => $sorted->push($c));
            }
        }

        $totals = ['r' => [], 'b' => []];
        foreach (range(0, $n - 1) as $i) {
            $totals['r'][$i] = $parents->sum(fn ($r) => $r->r[$i]);
            // Budget toko biasanya hanya diisi untuk sebagian toko, jadi totalnya tidak dibandingkan.
            $totals['b'][$i] = $kelompok === 'toko' ? 0.0 : $parents->sum(fn ($r) => $r->b[$i] ?? 0);
        }

        return ['rows' => $sorted, 'totals' => $totals];
    }

    /** Perubahan persen dari $dasar ke $nilai; null bila dasar 0. */
    public static function change(float $dasar, float $nilai): ?float
    {
        return $dasar != 0 ? ($nilai - $dasar) / abs($dasar) * 100 : null;
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
