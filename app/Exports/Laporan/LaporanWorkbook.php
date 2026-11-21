<?php

namespace App\Exports\Laporan;

use App\Services\CostReport;
use App\Support\Periode;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Chart\Chart;
use PhpOffice\PhpSpreadsheet\Chart\DataSeries;
use PhpOffice\PhpSpreadsheet\Chart\DataSeriesValues;
use PhpOffice\PhpSpreadsheet\Chart\Legend;
use PhpOffice\PhpSpreadsheet\Chart\PlotArea;
use PhpOffice\PhpSpreadsheet\Chart\Title;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\PageSetup;
use PhpOffice\PhpSpreadsheet\Worksheet\Table;
use PhpOffice\PhpSpreadsheet\Worksheet\Table\TableStyle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Export Excel untuk tab laporan Ringkasan, Per toko, Tren bulanan, dan Pos melebihi budget.
 * Setiap file berisi sheet utama tab tersebut, sheet "Data" (data mentah per bulan untuk PivotTable),
 * dan sheet "Panduan".
 */
class LaporanWorkbook
{
    use ExcelKit;

    private const BULAN = [1 => 'Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];

    public function __construct(
        private string $tab,
        private Periode $periode,
        private array $divisionIds = [],
        private array $akunIds = [],
        private array $tokoIds = [],
        private array $kategoriIds = [],
        private ?string $dibuatOleh = null,
    ) {
        if ($tokoIds) {
            $this->infoTambahan[] = 'Toko: '.DB::table('sub_divisions')->whereIn('id', $tokoIds)->orderBy('name')->pluck('name')->implode(', ');
        }
        if ($kategoriIds) {
            $this->infoTambahan[] = 'Kategori: '.DB::table('sub_akun_biaya')->whereIn('id', $kategoriIds)->orderBy('name')->pluck('name')->implode(', ');
        }
    }

    public function filename(): string
    {
        $nama = ['ringkasan' => 'ringkasan-akun-divisi', 'toko' => 'per-toko', 'tren' => 'tren-bulanan', 'lewat' => 'pos-melebihi-budget'][$this->tab] ?? 'laporan';
        $periode = $this->tab === 'tren' ? (string) $this->trenTahun() : preg_replace('/[^a-z0-9]+/', '-', strtolower($this->periode->label()));

        return 'laporan-'.$nama.'-'.trim($periode, '-').'.xlsx';
    }

    public function build(): Spreadsheet
    {
        $book = new Spreadsheet();
        $book->getProperties()->setCreator('Kontrol Biaya')->setTitle('Laporan realisasi vs budget');
        $book->getDefaultStyle()->getFont()->setName('Calibri')->setSize(10);
        $ws = $book->getActiveSheet();

        match ($this->tab) {
            'toko' => $this->sheetToko($ws),
            'tren' => $this->sheetTren($ws),
            'lewat' => $this->sheetLewat($ws),
            default => $this->sheetRingkasan($ws, $book),
        };
        $dataPeriode = $this->tab === 'tren' ? new Periode($this->trenTahun()) : $this->periode;
        $this->sheetData($book->createSheet(), $dataPeriode);
        $this->panduanSheet($book->createSheet(), $this->panduanLines());
        $book->setActiveSheetIndex(0);

        return $book;
    }

    private function trenTahun(): int
    {
        return $this->periode->tahun ?? (int) now()->year;
    }

    private function L(int $c): string
    {
        return Coordinate::stringFromColumnIndex($c);
    }

    // ============================================================ Ringkasan

    private function sheetRingkasan(Worksheet $ws, Spreadsheet $book): void
    {
        $report = new CostReport($this->periode);
        $m = $report->matrix($this->divisionIds, $this->akunIds);
        $positions = $report->positions($m);
        $divs = $m['divisions']->values();

        // ---- Sheet 1: Ringkasan per divisi
        $ws->setTitle('Ringkasan');
        $ws->setShowGridlines(false);
        $this->judulPeriode($ws, 'Ringkasan realisasi vs budget', 'H');

        $tr = $m['total_realisasi'];
        $tb = $m['total_budget'];
        $over = $positions->filter(fn ($p) => CostReport::status($p->pct, $p->realisasi) === 'bad')->count();
        $kpi = [['Realisasi', $tr, static::$RP], ['Budget', $tb, static::$RP], ['% Serapan', $tb > 0 ? $tr / $tb : null, static::$PCT], ['Sisa budget', max(0, $tb - $tr), static::$RP], ['Pos melebihi budget', $over, '0 "pos"']];
        foreach ($kpi as $i => [$label, $value, $fmt]) {
            $col = $this->L(1 + $i * 2);
            $col2 = $this->L(2 + $i * 2);
            $ws->setCellValue($col.'5', $label)->mergeCells("{$col}5:{$col2}5");
            $ws->setCellValue($col.'6', $value ?? '–')->mergeCells("{$col}6:{$col2}6");
            $ws->getStyle($col.'6')->getNumberFormat()->setFormatCode($fmt);
            $ws->getStyle("{$col}5:{$col2}6")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
            $ws->getStyle("{$col}5")->getFont()->setSize(9)->getColor()->setRGB('64748B');
            $ws->getStyle("{$col}6")->getFont()->setBold(true)->setSize(14);
            $ws->getStyle("{$col}5:{$col2}6")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('F8FAFC');
        }
        $this->serapanRules($ws, 'E6');

        $h = 8;
        $ws->fromArray(['Divisi', '', 'Realisasi (Rp)', 'Budget (Rp)', '% Serapan', 'Sisa budget (Rp)', 'Porsi realisasi', 'Status'], null, "A$h");
        $ws->mergeCells("A$h:B$h");
        $this->styleHeader($ws, "A$h:H$h");
        $r = $h;
        foreach ($divs as $d) {
            $r++;
            $ws->setCellValue("A$r", $d->name)->mergeCells("A$r:B$r");
            $ws->setCellValue("C$r", round($m['div_totals'][$d->id]['r'] ?? 0, 2));
            $ws->setCellValue("D$r", round($m['div_totals'][$d->id]['b'] ?? 0, 2));
            $ws->setCellValue("E$r", "=IF(D$r>0,C$r/D$r,\"\")");
            $ws->setCellValue("F$r", "=IF(D$r>0,D$r-C$r,\"\")");
            $this->statusFormula($ws, "H$r", "C$r", "D$r");
        }
        $first = $h + 1;
        $last = max($first, $r);
        $t = $last + 1;
        for ($x = $first; $x <= $last; $x++) {
            $ws->setCellValue("G$x", "=IF(\$C\$$t>0,C$x/\$C\$$t,\"\")");
        }
        $ws->setCellValue("A$t", 'TOTAL')->mergeCells("A$t:B$t");
        $ws->setCellValue("C$t", "=SUBTOTAL(109,C$first:C$last)");
        $ws->setCellValue("D$t", "=SUBTOTAL(109,D$first:D$last)");
        $ws->setCellValue("E$t", "=IF(D$t>0,C$t/D$t,\"\")");
        $ws->setCellValue("F$t", "=IF(D$t>0,D$t-C$t,\"\")");
        $this->totalRow($ws, "A$t:H$t");
        $ws->getStyle("C$first:D$t")->getNumberFormat()->setFormatCode(static::$RP);
        $ws->getStyle("F$first:F$t")->getNumberFormat()->setFormatCode(static::$RP);
        $ws->getStyle("E$first:E$t")->getNumberFormat()->setFormatCode(static::$PCT);
        $ws->getStyle("G$first:G$t")->getNumberFormat()->setFormatCode('0.0%');
        $this->serapanRules($ws, "E$first:E$last");
        $this->statusRules($ws, "H$first:H$last");
        $this->dataBar($ws, "G$first:G$last");
        $this->gridBorders($ws, "A$h:H$t");
        $this->setWidths($ws, ['A' => 16, 'B' => 14, 'C' => 17, 'D' => 17, 'E' => 11, 'F' => 17, 'G' => 14, 'H' => 15, 'I' => 14, 'J' => 14]);

        if ($divs->isNotEmpty()) {
            $this->barChart($ws, 'grafik_divisi', 'Realisasi vs budget per divisi', "A$first:A$last", "C$h", "C$first:C$last", "D$h", "D$first:D$last", 'A'.($t + 2), 'H'.($t + 22));
        }

        // Lima pos dengan serapan tertinggi.
        $p0 = $t + 24;
        $ws->setCellValue("A$p0", 'Pos dengan serapan tertinggi');
        $ws->getStyle("A$p0")->getFont()->setBold(true)->setSize(11);
        $ph = $p0 + 1;
        $ws->fromArray(['Akun', '', 'Divisi', 'Realisasi (Rp)', 'Budget (Rp)', '% Serapan', 'Status'], null, "A$ph");
        $ws->mergeCells("A$ph:B$ph");
        $this->styleHeader($ws, "A$ph:G$ph");
        $pr = $ph;
        foreach ($positions->take(8) as $p) {
            $pr++;
            $ws->setCellValue("A$pr", $p->akun)->mergeCells("A$pr:B$pr");
            $ws->fromArray([$p->division, round($p->realisasi, 2), round($p->budget, 2)], null, "C$pr");
            $ws->setCellValue("F$pr", "=IF(E$pr>0,D$pr/E$pr,\"\")");
            $this->statusFormula($ws, "G$pr", "D$pr", "E$pr");
        }
        if ($pr > $ph) {
            $ws->getStyle('D'.($ph + 1).":E$pr")->getNumberFormat()->setFormatCode(static::$RP);
            $ws->getStyle('F'.($ph + 1).":F$pr")->getNumberFormat()->setFormatCode(static::$PCT);
            $this->serapanRules($ws, 'F'.($ph + 1).":F$pr");
            $this->statusRules($ws, 'G'.($ph + 1).":G$pr");
        }
        $this->gridBorders($ws, "A$ph:G".max($ph, $pr));
        $this->print($ws, PageSetup::ORIENTATION_PORTRAIT);

        // ---- Sheet 2: matriks akun × divisi
        $this->sheetMatriks($book->createSheet(), $m);
    }

    private function sheetMatriks(Worksheet $ws, array $m): void
    {
        $ws->setTitle('Akun × Divisi');
        $divs = $m['divisions']->values();
        $nd = $divs->count();
        $colD = fn ($i) => 3 + $i * 3;
        $colT = 3 + $nd * 3;
        $lastL = $this->L($colT + 2);
        $this->judulPeriode($ws, 'Realisasi vs budget per akun dan divisi', $lastL);

        $h1 = 5;
        $h2 = 6;
        $ws->setCellValue("A$h1", 'Akun / kategori')->mergeCells("A$h1:A$h2");
        $ws->setCellValue("B$h1", 'Tingkat')->mergeCells("B$h1:B$h2");
        foreach ($divs as $i => $d) {
            $c = $colD($i);
            $ws->setCellValue($this->L($c).$h1, $d->name)->mergeCells($this->L($c).$h1.':'.$this->L($c + 2).$h1);
            $ws->fromArray(['Realisasi', 'Budget', '% Serapan'], null, $this->L($c).$h2);
        }
        $ws->setCellValue($this->L($colT).$h1, 'TOTAL')->mergeCells($this->L($colT).$h1.':'.$lastL.$h1);
        $ws->fromArray(['Realisasi', 'Budget', '% Serapan'], null, $this->L($colT).$h2);
        $this->styleHeader($ws, "A$h1:$lastL$h2");
        $ws->getStyle($this->L($colT).$h1.":$lastL$h2")->getFill()->getStartColor()->setRGB('BFDBFE');

        $r = $h2;
        foreach ($m['akun'] as $a) {
            $r++;
            $this->matriksRow($ws, $r, $a->name, 'Akun', $divs, $colD, $colT,
                fn ($d) => $m['cells'][$a->id]['_'][$d->id] ?? 0, fn ($d) => $m['budget'][$a->id][$d->id] ?? 0);
            $ws->getStyle("A$r:$lastL$r")->getFont()->setBold(true);
            $ws->getStyle("A$r:$lastL$r")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB(static::$C_SUB);
            foreach ($a->subs as $s) {
                $r++;
                $this->matriksRow($ws, $r, '    '.$s->name, 'Kategori', $divs, $colD, $colT,
                    fn ($d) => $m['cells'][$a->id][$s->id][$d->id] ?? 0, null);
                $ws->getRowDimension($r)->setOutlineLevel(1);
                $ws->getStyle("A$r:$lastL$r")->getFont()->getColor()->setRGB('475569');
            }
        }
        $first = $h2 + 1;
        $last = max($first, $r);
        $t = $last + 1;
        $ws->setCellValue("A$t", 'TOTAL');
        for ($c = 3; $c <= $colT + 2; $c += 3) {
            foreach ([0, 1] as $k) {
                $col = $this->L($c + $k);
                $ws->setCellValue("$col$t", "=SUMIF(\$B\$$first:\$B\$$last,\"Akun\",$col$first:$col$last)");
            }
            $rc = $this->L($c).$t;
            $bc = $this->L($c + 1).$t;
            $ws->setCellValue($this->L($c + 2).$t, "=IF($bc>0,$rc/$bc,\"\")");
        }
        $this->totalRow($ws, "A$t:$lastL$t");
        for ($c = 3; $c <= $colT; $c += 3) {
            $ws->getStyle($this->L($c).$first.':'.$this->L($c + 1).$t)->getNumberFormat()->setFormatCode(static::$RP);
            $ws->getStyle($this->L($c + 2).$first.':'.$this->L($c + 2).$t)->getNumberFormat()->setFormatCode(static::$PCT);
            $this->serapanRules($ws, $this->L($c + 2).$first.':'.$this->L($c + 2).$last);
            $this->setWidths($ws, [$this->L($c) => 15, $this->L($c + 1) => 15, $this->L($c + 2) => 10]);
        }
        $this->dataBar($ws, $this->L($colT).$first.':'.$this->L($colT).$last);
        $this->gridBorders($ws, "A$h1:$lastL$t");
        $this->setWidths($ws, ['A' => 34, 'B' => 10]);
        $ws->setAutoFilter("A$h2:$lastL$last");
        $ws->freezePane('C'.$first);
        $ws->setShowSummaryBelow(false);
        $this->print($ws, PageSetup::ORIENTATION_LANDSCAPE, "$h1:$h2");
    }

    private function matriksRow(Worksheet $ws, int $r, string $name, string $tingkat, Collection $divs, callable $colD, int $colT, callable $real, ?callable $budget): void
    {
        $ws->setCellValueExplicit("A$r", $name, DataType::TYPE_STRING);
        $ws->setCellValue("B$r", $tingkat);
        $rs = [];
        $bs = [];
        foreach ($divs as $i => $d) {
            $c = $colD($i);
            $rc = $this->L($c).$r;
            $bc = $this->L($c + 1).$r;
            $ws->setCellValue($rc, round($real($d), 2));
            $rs[] = $rc;
            if ($budget) {
                $ws->setCellValue($bc, round($budget($d), 2));
                $ws->setCellValue($this->L($c + 2).$r, "=IF($bc>0,$rc/$bc,IF($rc>0,\"tanpa budget\",\"\"))");
                $bs[] = $bc;
            }
        }
        $tr = $this->L($colT).$r;
        $tb = $this->L($colT + 1).$r;
        $ws->setCellValue($tr, $rs ? '=SUM('.implode(',', $rs).')' : 0);
        if ($budget) {
            $ws->setCellValue($tb, $bs ? '=SUM('.implode(',', $bs).')' : 0);
            $ws->setCellValue($this->L($colT + 2).$r, "=IF($tb>0,$tr/$tb,IF($tr>0,\"tanpa budget\",\"\"))");
        }
    }

    // ============================================================ Per toko

    private function sheetToko(Worksheet $ws): void
    {
        $ws->setTitle('Per Toko');
        $q = $this->base($this->periode)
            ->select('d.id as did', 'd.name as divisi', 'sd.id as sid', 'sd.name as toko', 'ab.id as aid', DB::raw('SUM(ju.kredit) as total'), DB::raw('COUNT(*) as jumlah'))
            ->groupBy('d.id', 'd.name', 'sd.id', 'sd.name', 'ab.id');
        $rows = $q->get();
        $akun = DB::table('akun_biaya')->where('dikecualikan', false)
            ->when($this->akunIds, fn ($x) => $x->whereIn('id', $this->akunIds))->orderBy('name')->get(['id', 'name']);
        $tokoList = DB::table('sub_divisions as sd')->join('divisions as d', 'd.id', '=', 'sd.division_id')
            ->when($this->divisionIds, fn ($x) => $x->whereIn('d.id', $this->divisionIds))
            ->when($this->tokoIds, fn ($x) => $x->whereIn('sd.id', $this->tokoIds))
            ->orderBy('d.name')->orderBy('sd.name')->get(['sd.id', 'sd.name', 'd.id as did', 'd.name as divisi']);
        $budget = $this->periode->budgetMap('sub_divisi_budgets', 'sub_divisi_id', $tokoList->pluck('id')->all(), $akun->pluck('id')->all());
        $val = [];
        $cnt = [];
        foreach ($rows as $x) {
            $val[$x->sid][$x->aid] = (float) $x->total;
            $cnt[$x->sid] = ($cnt[$x->sid] ?? 0) + (int) $x->jumlah;
        }
        $budgetToko = [];
        foreach ($budget as $aid => $perToko) {
            foreach ($perToko as $sid => $amount) {
                $budgetToko[$sid] = ($budgetToko[$sid] ?? 0) + $amount;
            }
        }
        // Hanya akun dan toko yang punya angka.
        $akun = $akun->filter(fn ($a) => collect($val)->contains(fn ($v) => isset($v[$a->id])))->values();
        $tokoList = $tokoList->filter(fn ($t) => isset($val[$t->id]) || isset($budgetToko[$t->id]))->values();

        $na = $akun->count();
        $cTot = 3 + $na;
        $lastL = $this->L($cTot + 4);
        $this->judulPeriode($ws, 'Realisasi per toko dan akun', $lastL);
        $h = 5;
        $ws->setCellValue("A$h", 'Toko');
        $ws->setCellValue("B$h", 'Divisi');
        foreach ($akun as $i => $a) {
            $ws->setCellValue($this->L(3 + $i).$h, $a->name);
        }
        $ws->fromArray(['Total realisasi', 'Budget toko', '% Serapan', 'Sisa budget', 'Transaksi'], null, $this->L($cTot).$h);
        $this->styleHeader($ws, "A$h:$lastL$h");
        $ws->getRowDimension($h)->setRowHeight(42);

        $r = $h;
        $divRows = [];
        foreach ($tokoList->groupBy('did') as $toko) {
            $r++;
            $dr = $r;
            $ws->setCellValue("A$r", $toko->first()->divisi);
            $ws->setCellValue("B$r", 'Subtotal divisi');
            $start = $r + 1;
            foreach ($toko as $tk) {
                $r++;
                $ws->setCellValueExplicit("A$r", '    '.$tk->name, DataType::TYPE_STRING);
                $ws->setCellValue("B$r", $tk->divisi);
                foreach ($akun as $i => $a) {
                    $ws->setCellValue($this->L(3 + $i).$r, round($val[$tk->id][$a->id] ?? 0, 2));
                }
                $tr = $this->L($cTot).$r;
                $tb = $this->L($cTot + 1).$r;
                $ws->setCellValue($tr, $na ? '=SUM('.$this->L(3).$r.':'.$this->L(2 + $na).$r.')' : 0);
                if (isset($budgetToko[$tk->id])) {
                    $ws->setCellValue($tb, round($budgetToko[$tk->id], 2));
                    $ws->setCellValue($this->L($cTot + 2).$r, "=IF($tb>0,$tr/$tb,\"\")");
                    $ws->setCellValue($this->L($cTot + 3).$r, "=IF($tb>0,$tb-$tr,\"\")");
                }
                $ws->setCellValue($this->L($cTot + 4).$r, $cnt[$tk->id] ?? 0);
                $ws->getRowDimension($r)->setOutlineLevel(1);
            }
            $end = $r;
            for ($c = 3; $c <= $cTot + 1; $c++) {
                $col = $this->L($c);
                $ws->setCellValue("$col$dr", "=SUBTOTAL(9,$col$start:$col$end)");
            }
            $ws->setCellValue($this->L($cTot + 4).$dr, '=SUBTOTAL(9,'.$this->L($cTot + 4)."$start:".$this->L($cTot + 4)."$end)");
            $ws->getStyle("A$dr:$lastL$dr")->getFont()->setBold(true);
            $ws->getStyle("A$dr:$lastL$dr")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB(static::$C_SUB);
            $divRows[] = $dr;
        }
        $first = $h + 1;
        $last = max($first, $r);
        $t = $last + 1;
        $ws->setCellValue("A$t", 'TOTAL');
        for ($c = 3; $c <= $cTot + 4; $c++) {
            if ($c === $cTot + 2 || $c === $cTot + 3) {
                continue;
            }
            $col = $this->L($c);
            // SUBTOTAL(9) mengabaikan baris subtotal divisi yang juga memakai SUBTOTAL.
            $ws->setCellValue("$col$t", "=SUBTOTAL(9,$col$first:$col$last)");
        }
        $this->totalRow($ws, "A$t:$lastL$t");
        $ws->getStyle("C$first:".$this->L($cTot + 1).$t)->getNumberFormat()->setFormatCode(static::$RP);
        $ws->getStyle($this->L($cTot + 3).$first.':'.$this->L($cTot + 3).$t)->getNumberFormat()->setFormatCode(static::$RP);
        $ws->getStyle($this->L($cTot + 2).$first.':'.$this->L($cTot + 2).$t)->getNumberFormat()->setFormatCode(static::$PCT);
        $ws->getStyle($this->L($cTot + 4).$first.':'.$this->L($cTot + 4).$t)->getNumberFormat()->setFormatCode('#,##0');
        $this->serapanRules($ws, $this->L($cTot + 2).$first.':'.$this->L($cTot + 2).$last);
        $this->dataBar($ws, $this->L($cTot).$first.':'.$this->L($cTot).$last);
        $this->gridBorders($ws, "A$h:$lastL$t");
        $this->setWidths($ws, ['A' => 30, 'B' => 18]);
        for ($c = 3; $c <= $cTot + 4; $c++) {
            $ws->getColumnDimension($this->L($c))->setWidth($c >= $cTot ? 15 : 14);
        }
        $ws->getStyle($this->L($cTot).$first.':'.$this->L($cTot).$t)->getFont()->setBold(true);
        $ws->setAutoFilter("A$h:$lastL$last");
        $ws->freezePane('C'.$first);
        $ws->setShowSummaryBelow(false);
        $this->print($ws, PageSetup::ORIENTATION_LANDSCAPE, "$h:$h");
    }

    // ============================================================ Tren

    private function sheetTren(Worksheet $ws): void
    {
        $tahun = $this->trenTahun();
        $ws->setTitle('Tren '.$tahun);
        $tren = CostReport::monthlyByAkun($tahun, $this->divisionIds, $this->akunIds);
        $lalu = CostReport::monthlyRealisasi($tahun - 1, $this->divisionIds, $this->akunIds);

        // Realisasi per kategori per bulan.
        $q = $this->base(new Periode($tahun))->select('ab.id as aid', 'sab.id as kid', 'sab.name as kategori', 'ju.tanggal', DB::raw('SUM(ju.kredit) as total'))
            ->groupBy('ab.id', 'sab.id', 'sab.name', 'ju.tanggal');
        $kat = [];
        foreach ($q->get() as $x) {
            $mo = (int) substr((string) $x->tanggal, 5, 2);
            $kat[$x->aid][$x->kid]['name'] = $x->kategori;
            $kat[$x->aid][$x->kid]['m'][$mo] = ($kat[$x->aid][$x->kid]['m'][$mo] ?? 0) + (float) $x->total;
        }

        $lastL = 'P';
        $ws->setCellValue('A1', 'Tren realisasi vs budget '.$tahun);
        $this->judulPeriode($ws, 'Tren realisasi vs budget '.$tahun, $lastL, 'Tahun '.$tahun);
        $h = 5;
        $ws->fromArray(array_merge(['Akun / kategori', 'Tingkat'], array_values(self::BULAN), ['Total', '% Serapan']), null, "A$h");
        $this->styleHeader($ws, "A$h:$lastL$h");
        $r = $h;
        foreach ($tren['akun'] as $a) {
            $r++;
            $ws->setCellValue("A$r", $a->name);
            $ws->setCellValue("B$r", 'Akun');
            foreach (range(1, 12) as $mo) {
                $ws->setCellValue($this->L(2 + $mo).$r, round($tren['r'][$a->id][$mo] ?? 0, 2));
            }
            $ws->setCellValue("O$r", "=SUM(C$r:N$r)");
            $b = array_sum($tren['b'][$a->id] ?? []);
            $ws->setCellValue("P$r", $b > 0 ? "=O$r/".round($b, 2) : '');
            $ws->getStyle("A$r:$lastL$r")->getFont()->setBold(true);
            $ws->getStyle("A$r:$lastL$r")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB(static::$C_SUB);
            foreach (collect($kat[$a->id] ?? [])->sortBy('name') as $k) {
                $r++;
                $ws->setCellValueExplicit("A$r", '    '.$k['name'], DataType::TYPE_STRING);
                $ws->setCellValue("B$r", 'Kategori');
                foreach (range(1, 12) as $mo) {
                    $ws->setCellValue($this->L(2 + $mo).$r, round($k['m'][$mo] ?? 0, 2));
                }
                $ws->setCellValue("O$r", "=SUM(C$r:N$r)");
                $ws->getRowDimension($r)->setOutlineLevel(1);
                $ws->getStyle("A$r:$lastL$r")->getFont()->getColor()->setRGB('475569');
            }
        }
        $first = $h + 1;
        $last = max($first, $r);

        // Blok ringkasan di bawah tabel.
        $s = $last + 1;
        $rows = [
            'Total realisasi' => fn ($col) => "=SUMIF(\$B\$$first:\$B\$$last,\"Akun\",$col$first:$col$last)",
            'Budget' => null,
            '% Serapan' => fn ($col) => "=IF($col".($s + 1).">0,$col$s/$col".($s + 1).',"")',
            'Kumulatif realisasi' => fn ($col, $prev) => $prev ? "=$prev".($s + 3)."+$col$s" : "=$col$s",
            'Kumulatif budget' => fn ($col, $prev) => $prev ? "=$prev".($s + 4)."+$col".($s + 1) : "=$col".($s + 1),
            '% Serapan kumulatif' => fn ($col) => "=IF($col".($s + 4).">0,$col".($s + 3)."/$col".($s + 4).',"")',
            'Realisasi '.($tahun - 1) => null,
            '% vs '.($tahun - 1) => fn ($col) => "=IF(AND($col".($s + 6)."<>0,$col$s<>0),($col$s-$col".($s + 6).')/ABS('.$col.($s + 6).'),"")',
        ];
        $i = 0;
        foreach ($rows as $label => $fn) {
            $rr = $s + $i;
            $ws->setCellValue("A$rr", $label);
            foreach (range(1, 12) as $mo) {
                $col = $this->L(2 + $mo);
                $prev = $mo > 1 ? $this->L(1 + $mo) : null;
                if ($label === 'Budget') {
                    $ws->setCellValue("$col$rr", round(array_sum(array_map(fn ($x) => $x[$mo] ?? 0, $tren['b'])), 2));
                } elseif ($label === 'Realisasi '.($tahun - 1)) {
                    $ws->setCellValue("$col$rr", round($lalu[$mo] ?? 0, 2));
                } else {
                    $ws->setCellValue("$col$rr", $fn($col, $prev));
                }
            }
            if (in_array($i, [0, 1, 6], true)) {
                $ws->setCellValue("O$rr", "=SUM(C$rr:N$rr)");
            }
            if ($i === 0) {
                $ws->setCellValue("P$rr", "=IF(O".($s + 1).">0,O$rr/O".($s + 1).',"")');
            }
            if ($i === 7) {
                $ws->setCellValue("O$rr", "=IF(O".($s + 6)."<>0,(O$s-O".($s + 6).')/ABS(O'.($s + 6).'),"")');
            }
            $i++;
        }
        $end = $s + count($rows) - 1;
        $this->totalRow($ws, "A$s:$lastL$s");
        $ws->getStyle('A'.($s + 1).":$lastL$end")->getFont()->setBold(true);
        $ws->getStyle('A'.($s + 1).":$lastL$end")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('F8FAFC');

        $ws->getStyle("C$first:O".($s + 1))->getNumberFormat()->setFormatCode(static::$RP);
        $ws->getStyle('C'.($s + 3).':O'.($s + 4))->getNumberFormat()->setFormatCode(static::$RP);
        $ws->getStyle('C'.($s + 6).':O'.($s + 6))->getNumberFormat()->setFormatCode(static::$RP);
        foreach ([$s + 2, $s + 5] as $pr) {
            $ws->getStyle("C$pr:P$pr")->getNumberFormat()->setFormatCode(static::$PCT);
            $this->serapanRules($ws, "C$pr:N$pr");
        }
        $ws->getStyle("P$first:P$s")->getNumberFormat()->setFormatCode(static::$PCT);
        $this->serapanRules($ws, "P$first:P$last");
        $ws->getStyle('C'.($s + 7).':O'.($s + 7))->getNumberFormat()->setFormatCode(static::$PCT_DELTA);
        $this->perubahanRules($ws, 'C'.($s + 7).':O'.($s + 7));
        $this->gridBorders($ws, "A$h:$lastL$end");
        $this->setWidths($ws, ['A' => 32, 'B' => 9, 'O' => 16, 'P' => 10]);
        foreach (range(3, 14) as $c) {
            $ws->getColumnDimension($this->L($c))->setWidth(12.5);
        }
        $ws->setAutoFilter("A$h:$lastL$last");
        $ws->freezePane("C$first");
        $ws->setShowSummaryBelow(false);

        // Grafik: realisasi, budget, dan tahun lalu per bulan.
        $title = "'".$ws->getTitle()."'";
        $cats = [new DataSeriesValues(DataSeriesValues::DATASERIES_TYPE_STRING, "$title!\$C\$$h:\$N\$$h", null, 12)];
        $mk = fn ($row) => new DataSeriesValues(DataSeriesValues::DATASERIES_TYPE_NUMBER, "$title!\$C\$$row:\$N\$$row", '#,##0', 12);
        $lbl = fn ($row) => new DataSeriesValues(DataSeriesValues::DATASERIES_TYPE_STRING, "$title!\$A\$$row", null, 1);
        $bars = new DataSeries(DataSeries::TYPE_BARCHART, DataSeries::GROUPING_CLUSTERED, [0, 1], [$lbl($s), $lbl($s + 6)], $cats, [$mk($s), $mk($s + 6)]);
        $bars->setPlotDirection(DataSeries::DIRECTION_COL);
        $line = new DataSeries(DataSeries::TYPE_LINECHART, DataSeries::GROUPING_STANDARD, [2], [$lbl($s + 1)], $cats, [$mk($s + 1)]);
        $chart = new Chart('grafik_tren', new Title('Realisasi, budget, dan tahun lalu per bulan'), new Legend(Legend::POSITION_BOTTOM, null, false), new PlotArea(null, [$bars, $line]));
        $chart->setTopLeftPosition('A'.($end + 2));
        $chart->setBottomRightPosition('P'.($end + 24));
        $ws->addChart($chart);
        $this->print($ws, PageSetup::ORIENTATION_LANDSCAPE, "$h:$h");
    }

    // ============================================================ Pos melebihi budget

    private function sheetLewat(Worksheet $ws): void
    {
        $ws->setTitle('Status Pos');
        $report = new CostReport($this->periode);
        $positions = $report->positions($report->matrix($this->divisionIds, $this->akunIds));
        $this->judulPeriode($ws, 'Status setiap pos (divisi × akun)', 'I');

        $cnt = ['bad' => 0, 'warn' => 0, 'ok' => 0];
        foreach ($positions as $p) {
            $st = CostReport::status($p->pct, $p->realisasi);
            if (isset($cnt[$st])) {
                $cnt[$st]++;
            }
        }
        foreach ([['Lewat budget / tanpa budget', $cnt['bad'], 'DC2626'], ['Hampir habis (80–100%)', $cnt['warn'], 'D97706'], ['Aman (< 80%)', $cnt['ok'], '16A34A']] as $i => [$label, $n, $color]) {
            $r0 = 5 + $i;
            $ws->setCellValue("A$r0", $label)->mergeCells("A$r0:B$r0");
            $ws->setCellValue("C$r0", $n.' pos');
            $ws->getStyle("A$r0:C$r0")->getFont()->setBold(true);
            $ws->getStyle("C$r0")->getFont()->getColor()->setRGB($color);
        }

        $h = 9;
        $ws->fromArray(['Status', 'Divisi', 'Akun', 'Realisasi (Rp)', 'Budget (Rp)', '% Serapan', 'Lebih / sisa (Rp)', 'Urutan', 'Catatan'], null, "A$h");
        $r = $h;
        foreach ($positions as $i => $p) {
            $r++;
            $ws->setCellValue("A$r", '');
            $ws->fromArray([$p->division, $p->akun, round($p->realisasi, 2), round($p->budget, 2)], null, "B$r");
            $ws->setCellValue("F$r", "=IF(E$r>0,D$r/E$r,\"\")");
            $ws->setCellValue("G$r", "=E$r-D$r");
            $ws->setCellValue("H$r", $i + 1);
            $this->statusFormula($ws, "A$r", "D$r", "E$r");
        }
        if ($r === $h) {
            $r++;
            $ws->setCellValue("B$r", 'Tidak ada pos untuk periode ini');
        }
        $ws->getStyle('D'.($h + 1).":E$r")->getNumberFormat()->setFormatCode(static::$RP);
        $ws->getStyle('G'.($h + 1).":G$r")->getNumberFormat()->setFormatCode('#,##0;[Red]-#,##0;"–"');
        $ws->getStyle('F'.($h + 1).":F$r")->getNumberFormat()->setFormatCode(static::$PCT);
        $this->serapanRules($ws, 'F'.($h + 1).":F$r");
        $this->statusRules($ws, 'A'.($h + 1).":A$r");
        $this->dataBar($ws, 'D'.($h + 1).":D$r");

        $table = new Table("A$h:I$r", 'StatusPos');
        $style = new TableStyle();
        $style->setTheme(TableStyle::TABLE_STYLE_LIGHT9);
        $style->setShowRowStripes(true);
        $table->setStyle($style);
        $ws->addTable($table);
        $this->setWidths($ws, ['A' => 17, 'B' => 22, 'C' => 30, 'D' => 17, 'E' => 17, 'F' => 11, 'G' => 18, 'H' => 8, 'I' => 30]);
        $ws->freezePane('A'.($h + 1));
        $this->print($ws, PageSetup::ORIENTATION_LANDSCAPE, "$h:$h");
    }

    // ============================================================ Data mentah

    private function sheetData(Worksheet $ws, Periode $periode): void
    {
        $ws->setTitle('Data');
        $rows = $this->base($periode)
            ->select('d.name as divisi', 'sd.name as toko', 'ab.name as akun', 'sab.name as kategori', 'ju.tanggal', DB::raw('COUNT(*) as jumlah'), DB::raw('SUM(ju.debet) as debet'), DB::raw('SUM(ju.kredit) as kredit'))
            ->groupBy('d.name', 'sd.name', 'ab.name', 'sab.name', 'ju.tanggal')->get();
        $agg = [];
        foreach ($rows as $x) {
            $tgl = $x->tanggal ? substr((string) $x->tanggal, 0, 7) : '';
            $k = implode('|', [$tgl, $x->divisi, $x->toko, $x->akun, $x->kategori]);
            $agg[$k] ??= ['tgl' => $tgl, 'divisi' => $x->divisi, 'toko' => $x->toko, 'akun' => $x->akun, 'kategori' => $x->kategori, 'n' => 0, 'd' => 0, 'k' => 0];
            $agg[$k]['n'] += (int) $x->jumlah;
            $agg[$k]['d'] += (float) $x->debet;
            $agg[$k]['k'] += (float) $x->kredit;
        }
        ksort($agg);
        $ws->fromArray(['Tahun', 'Bulan ke', 'Bulan', 'Divisi', 'Toko', 'Akun', 'Kategori', 'Jumlah transaksi', 'Debet (Rp)', 'Realisasi / kredit (Rp)'], null, 'A1');
        $r = 1;
        foreach ($agg as $a) {
            $r++;
            $th = $a['tgl'] ? (int) substr($a['tgl'], 0, 4) : null;
            $mo = $a['tgl'] ? (int) substr($a['tgl'], 5, 2) : null;
            $ws->fromArray([$th, $mo, $mo ? Periode::NAMA_BULAN[$mo] : 'Tanpa tanggal', $a['divisi'], $a['toko'], $a['akun'], $a['kategori'], $a['n'], round($a['d'], 2), round($a['k'], 2)], null, "A$r", true);
        }
        if ($r === 1) {
            $r = 2;
            $ws->setCellValue('D2', 'Tidak ada data');
        }
        $ws->getStyle("I2:J$r")->getNumberFormat()->setFormatCode(static::$RP);
        $ws->getStyle("H2:H$r")->getNumberFormat()->setFormatCode('#,##0');
        $table = new Table("A1:J$r", 'DataJurnal');
        $style = new TableStyle();
        $style->setTheme(TableStyle::TABLE_STYLE_MEDIUM2);
        $style->setShowRowStripes(true);
        $table->setStyle($style);
        $ws->addTable($table);
        $this->setWidths($ws, ['A' => 8, 'B' => 9, 'C' => 13, 'D' => 22, 'E' => 28, 'F' => 28, 'G' => 28, 'H' => 12, 'I' => 16, 'J' => 20]);
        $ws->getStyle('A1:J1')->getAlignment()->setWrapText(true)->setVertical(Alignment::VERTICAL_CENTER);
        $ws->getRowDimension(1)->setRowHeight(30);
        $ws->freezePane('A2');
    }

    // ============================================================ Helpers

    private function base(Periode $periode)
    {
        $q = DB::table('jurnal_umum as ju')
            ->join('sub_divisions as sd', 'sd.id', '=', 'ju.sub_divisi_id')
            ->join('divisions as d', 'd.id', '=', 'sd.division_id')
            ->join('sub_akun_biaya as sab', 'sab.id', '=', 'ju.sub_akun_biaya_id')
            ->join('akun_biaya as ab', 'ab.id', '=', 'sab.akun_biaya_id')
            ->where('ab.dikecualikan', false)
            ->when($this->divisionIds, fn ($x) => $x->whereIn('d.id', $this->divisionIds))
            ->when($this->akunIds, fn ($x) => $x->whereIn('ab.id', $this->akunIds))
            ->when($this->tokoIds, fn ($x) => $x->whereIn('sd.id', $this->tokoIds))
            ->when($this->kategoriIds, fn ($x) => $x->whereIn('sab.id', $this->kategoriIds));
        $periode->applyToJurnal($q);

        return $q;
    }

    private function judulPeriode(Worksheet $ws, string $title, string $lastCol, ?string $label = null): void
    {
        $this->judul($ws, $title.' — '.($label ?? $this->periode->label()), $lastCol);
    }

    private function totalRow(Worksheet $ws, string $range): void
    {
        $ws->getStyle($range)->getFont()->setBold(true)->getColor()->setRGB('FFFFFF');
        $ws->getStyle($range)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB(static::$C_DARK);
    }

    private function statusFormula(Worksheet $ws, string $cell, string $real, string $budget): void
    {
        $ws->setCellValue($cell, "=IF($budget>0,IF($real/$budget>1,\"Lewat budget\",IF($real/$budget>=0.8,\"Hampir habis\",\"Aman\")),IF($real>0,\"Tanpa budget\",\"\"))");
    }

    private function statusRules(Worksheet $ws, string $range): void
    {
        $first = explode(':', $range)[0];
        $rules = [];
        foreach ([['Lewat budget', '991B1B', 'FEE2E2'], ['Tanpa budget', '991B1B', 'FEE2E2'], ['Hampir habis', '92400E', 'FEF3C7'], ['Aman', '166534', 'DCFCE7']] as [$text, $font, $fill]) {
            $c = new \PhpOffice\PhpSpreadsheet\Style\Conditional();
            $c->setConditionType(\PhpOffice\PhpSpreadsheet\Style\Conditional::CONDITION_EXPRESSION)->addCondition("$first=\"$text\"");
            $c->getStyle()->getFont()->setBold(true)->getColor()->setRGB($font);
            $c->getStyle()->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB($fill);
            $c->getStyle()->getFill()->getEndColor()->setRGB($fill);
            $rules[] = $c;
        }
        $ws->getStyle($range)->setConditionalStyles($rules);
    }

    private function barChart(Worksheet $ws, string $name, string $title, string $cats, string $l1, string $v1, string $l2, string $v2, string $from, string $to): void
    {
        $sheet = "'".$ws->getTitle()."'!";
        $abs = fn ($ref) => $sheet.preg_replace('/([A-Z]+)(\d+)/', '\$$1\$$2', $ref);
        $n = (int) preg_replace('/\D/', '', explode(':', $cats)[1]) - (int) preg_replace('/\D/', '', explode(':', $cats)[0]) + 1;
        $values = [
            new DataSeriesValues(DataSeriesValues::DATASERIES_TYPE_NUMBER, $abs($v1), '#,##0', $n),
            new DataSeriesValues(DataSeriesValues::DATASERIES_TYPE_NUMBER, $abs($v2), '#,##0', $n),
        ];
        $values[0]->setFillColor('3B82F6');
        $values[1]->setFillColor('CBD5E1');
        $series = new DataSeries(DataSeries::TYPE_BARCHART, DataSeries::GROUPING_CLUSTERED, [0, 1],
            [new DataSeriesValues(DataSeriesValues::DATASERIES_TYPE_STRING, $abs($l1), null, 1), new DataSeriesValues(DataSeriesValues::DATASERIES_TYPE_STRING, $abs($l2), null, 1)],
            [new DataSeriesValues(DataSeriesValues::DATASERIES_TYPE_STRING, $abs($cats), null, $n)], $values);
        $series->setPlotDirection(DataSeries::DIRECTION_BAR);
        $chart = new Chart($name, new Title($title), new Legend(Legend::POSITION_BOTTOM, null, false), new PlotArea(null, [$series]));
        $chart->setTopLeftPosition($from);
        $chart->setBottomRightPosition($to);
        $ws->addChart($chart);
    }

    private function panduanLines(): array
    {
        $utama = [
            'ringkasan' => [
                ['Ringkasan', true],
                ['Angka utama periode ini, tabel per divisi (realisasi, budget, % serapan, sisa, porsi, status), grafik, dan pos dengan serapan tertinggi.', false],
                ['Akun × Divisi', true],
                ['Baris tebal = akun dengan budget. Kategori ada di bawahnya dan bisa dilipat (tanda + / − di kiri). Kolom TOTAL di kanan.', false],
            ],
            'toko' => [
                ['Per Toko', true],
                ['Toko dikelompokkan per divisi. Baris tebal = subtotal divisi; toko bisa dilipat. Kolom akun berisi realisasi, lalu total, budget toko, % serapan, sisa, dan jumlah transaksi.', false],
                ['Subtotal dan TOTAL memakai SUBTOTAL, jadi ikut menyesuaikan saat baris disaring.', false],
            ],
            'tren' => [
                ['Tren', true],
                ['Realisasi per akun dan kategori setiap bulan. Di bawah tabel: total, budget, % serapan, kumulatif, realisasi tahun lalu, dan % perubahan dibanding tahun lalu. Grafik di bawahnya.', false],
            ],
            'lewat' => [
                ['Status Pos', true],
                ['Semua pos divisi × akun, diurutkan dari % serapan tertinggi. Pakai filter kolom Status untuk melihat yang Lewat budget atau Hampir habis saja. Kolom Catatan bisa diisi sendiri.', false],
            ],
        ][$this->tab] ?? [];

        return array_merge(
            [['Cara membaca file ini', true], ['', false]],
            $utama,
            [['Data', true], ['Data mentah per bulan × divisi × toko × akun × kategori (tabel Excel). Cocok untuk filter cepat atau PivotTable.', false], ['', false]],
            $this->panduanUmum(),
        );
    }
}
