<?php

namespace App\Exports\Laporan;

use App\Services\CostReport;
use App\Support\Periode;
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
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Conditional;
use PhpOffice\PhpSpreadsheet\Style\ConditionalFormatting\ConditionalDataBar;
use PhpOffice\PhpSpreadsheet\Style\ConditionalFormatting\ConditionalFormatValueObject;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\PageSetup;
use PhpOffice\PhpSpreadsheet\Worksheet\Table;
use PhpOffice\PhpSpreadsheet\Worksheet\Table\TableStyle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Workbook "Perbandingan periode":
 *  1. Ringkasan      total tiap periode + grafik
 *  2. Per Akun       akun (bisa dilipat ke kategori) × periode
 *  3. Per Divisi     divisi × periode
 *  4. Per Toko       toko × periode
 *  5. Data           data mentah (tabel Excel) untuk filter dan PivotTable
 *  6. Panduan        cara membaca
 * Persen, selisih, dan total memakai rumus Excel, jadi ikut berubah bila angka disunting.
 */
class BandingkanExport
{
    use ExcelKit;



    /** @param Periode[] $periodes */
    public function __construct(
        private array $periodes,
        private array $divisionIds = [],
        private array $akunIds = [],
        private ?string $dibuatOleh = null,
    ) {
    }

    public function build(): Spreadsheet
    {
        $book = new Spreadsheet();
        $book->getProperties()->setCreator('Kontrol Biaya')->setTitle('Perbandingan periode')
            ->setSubject('Realisasi vs budget per periode');
        $book->getDefaultStyle()->getFont()->setName('Calibri')->setSize(10);

        $this->ringkasan($book->getActiveSheet());
        $this->perbandingan($book->createSheet(), 'Per Akun & Kategori', 'akun');
        $this->perbandingan($book->createSheet(), 'Per Divisi', 'divisi');
        $this->perbandingan($book->createSheet(), 'Per Toko', 'toko');
        $this->data($book->createSheet());
        $this->panduan($book->createSheet());
        $book->setActiveSheetIndex(0);

        return $book;
    }

    // ------------------------------------------------------------------ Ringkasan

    private function ringkasan(Worksheet $ws): void
    {
        $ws->setTitle('Ringkasan');
        $ws->setShowGridlines(false);
        $row = $this->judul($ws, 'Perbandingan realisasi vs budget', 'H');

        $headers = ['Periode', 'Peran', 'Tanggal awal', 'Tanggal akhir', 'Realisasi (Rp)', 'Budget (Rp)', '% Serapan', 'Sisa budget (Rp)', 'Selisih vs utama (Rp)', '% Perubahan vs utama'];
        $ws->fromArray($headers, null, 'A'.$row);
        $this->styleHeader($ws, 'A'.$row.':J'.$row);
        $ws->getRowDimension($row)->setRowHeight(30);
        $first = $row + 1;

        $data = CostReport::compare($this->periodes, 'divisi', $this->divisionIds, $this->akunIds);
        foreach ($this->periodes as $i => $p) {
            $r = $first + $i;
            $ws->setCellValue("A$r", $p->label());
            $ws->setCellValue("B$r", $i === 0 ? 'Utama' : 'Pembanding '.$i);
            $ws->setCellValue("C$r", \PhpOffice\PhpSpreadsheet\Shared\Date::stringToExcel($p->tanggalAwal()));
            $ws->setCellValue("D$r", \PhpOffice\PhpSpreadsheet\Shared\Date::stringToExcel($p->tanggalAkhir()));
            $ws->setCellValue("E$r", round($data['totals']['r'][$i], 2));
            $ws->setCellValue("F$r", round($data['totals']['b'][$i], 2));
            $ws->setCellValue("G$r", "=IF(F$r>0,E$r/F$r,\"\")");
            $ws->setCellValue("H$r", "=IF(F$r>0,F$r-E$r,\"\")");
            $ws->setCellValue("I$r", $i === 0 ? '' : "=E$r-E\$$first");
            $ws->setCellValue("J$r", $i === 0 ? '' : "=IF(E\$$first<>0,(E$r-E\$$first)/ABS(E\$$first),\"\")");
        }
        $last = $first + count($this->periodes) - 1;
        $ws->getStyle("C$first:D$last")->getNumberFormat()->setFormatCode('dd mmm yyyy');
        $ws->getStyle("E$first:F$last")->getNumberFormat()->setFormatCode(static::$RP);
        $ws->getStyle("H$first:H$last")->getNumberFormat()->setFormatCode(static::$RP);
        $ws->getStyle("I$first:I$last")->getNumberFormat()->setFormatCode(static::$RP_DELTA);
        $ws->getStyle("G$first:G$last")->getNumberFormat()->setFormatCode(static::$PCT);
        $ws->getStyle("J$first:J$last")->getNumberFormat()->setFormatCode(static::$PCT_DELTA);
        $ws->getStyle("A$first:B$first")->getFont()->setBold(true);
        $ws->getStyle("A$first:J$first")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB(static::$C_UTAMA);
        $this->gridBorders($ws, "A$row:J$last");
        $this->serapanRules($ws, "G$first:G$last");
        $this->perubahanRules($ws, "J$first:J$last");
        $this->perubahanRules($ws, "I$first:I$last");

        // Grafik realisasi vs budget per periode.
        $n = count($this->periodes);
        $cats = [new DataSeriesValues(DataSeriesValues::DATASERIES_TYPE_STRING, "'Ringkasan'!\$A\$$first:\$A\$$last", null, $n)];
        $labels = [
            new DataSeriesValues(DataSeriesValues::DATASERIES_TYPE_STRING, "'Ringkasan'!\$E\$$row", null, 1),
            new DataSeriesValues(DataSeriesValues::DATASERIES_TYPE_STRING, "'Ringkasan'!\$F\$$row", null, 1),
        ];
        $values = [
            new DataSeriesValues(DataSeriesValues::DATASERIES_TYPE_NUMBER, "'Ringkasan'!\$E\$$first:\$E\$$last", '#,##0', $n),
            new DataSeriesValues(DataSeriesValues::DATASERIES_TYPE_NUMBER, "'Ringkasan'!\$F\$$first:\$F\$$last", '#,##0', $n),
        ];
        $values[0]->setFillColor('3B82F6');
        $values[1]->setFillColor('CBD5E1');
        $series = new DataSeries(DataSeries::TYPE_BARCHART, DataSeries::GROUPING_CLUSTERED, [0, 1], $labels, $cats, $values);
        $series->setPlotDirection(DataSeries::DIRECTION_COL);
        $chart = new Chart('grafik_periode', new Title('Realisasi vs budget per periode'), new Legend(Legend::POSITION_BOTTOM, null, false), new PlotArea(null, [$series]));
        $chart->setTopLeftPosition('A'.($last + 3));
        $chart->setBottomRightPosition('H'.($last + 22));
        $ws->addChart($chart);

        // Lima pos dengan kenaikan terbesar (periode pembanding terakhir vs utama).
        $noteRow = $last + 24;
        if ($n > 1) {
            $akun = CostReport::compare($this->periodes, 'akun', $this->divisionIds, $this->akunIds)['rows']->where('level', 1);
            $naik = $akun->map(fn ($x) => (object) ['nama' => $x->name.' · '.$x->sub, 'dasar' => $x->r[0], 'nilai' => $x->r[$n - 1]])
                ->sortByDesc(fn ($x) => $x->nilai - $x->dasar)->take(5)->filter(fn ($x) => $x->nilai > $x->dasar);
            $ws->setCellValue("A$noteRow", 'Kategori dengan kenaikan terbesar: '.$this->periodes[$n - 1]->label().' dibanding '.$this->periodes[0]->label());
            $ws->getStyle("A$noteRow")->getFont()->setBold(true)->setSize(11);
            $h = $noteRow + 1;
            $ws->fromArray(['Kategori · Akun', '', '', '', 'Utama (Rp)', 'Pembanding (Rp)', '', '', 'Kenaikan (Rp)', '% Kenaikan'], null, "A$h");
            $ws->mergeCells("A$h:D$h");
            $this->styleHeader($ws, "A$h:J$h");
            $r = $h;
            foreach ($naik as $x) {
                $r++;
                $ws->setCellValue("A$r", $x->nama);
                $ws->mergeCells("A$r:D$r");
                $ws->setCellValue("E$r", $x->dasar);
                $ws->setCellValue("F$r", $x->nilai);
                $ws->setCellValue("I$r", "=F$r-E$r");
                $ws->setCellValue("J$r", "=IF(E$r<>0,(F$r-E$r)/ABS(E$r),\"\")");
            }
            if ($r === $h) {
                $ws->setCellValue('A'.($h + 1), 'Tidak ada kategori yang naik.');
                $r = $h + 1;
            }
            $ws->getStyle("E$h:I$r")->getNumberFormat()->setFormatCode(static::$RP);
            $ws->getStyle("I".($h + 1).":I$r")->getNumberFormat()->setFormatCode(static::$RP_DELTA);
            $ws->getStyle("J".($h + 1).":J$r")->getNumberFormat()->setFormatCode(static::$PCT_DELTA);
            $this->perubahanRules($ws, "J".($h + 1).":J$r");
            $this->gridBorders($ws, "A$h:J$r");
        }

        foreach (['A' => 26, 'B' => 14, 'C' => 13, 'D' => 13, 'E' => 17, 'F' => 17, 'G' => 11, 'H' => 17, 'I' => 19, 'J' => 14] as $col => $w) {
            $ws->getColumnDimension($col)->setWidth($w);
        }
        $ws->freezePane('A'.($row + 1));
        $this->print($ws, PageSetup::ORIENTATION_LANDSCAPE);
    }

    // ------------------------------------------------------------- Per kelompok

    private function perbandingan(Worksheet $ws, string $title, string $kelompok): void
    {
        $ws->setTitle($title);
        $n = count($this->periodes);
        $data = CostReport::compare($this->periodes, $kelompok, $this->divisionIds, $this->akunIds);

        // Kolom: A nama, B keterangan (tingkat / divisi), lalu tiap periode 3 kolom, lalu selisih tiap pembanding 2 kolom.
        $colR = fn ($i) => 3 + $i * 3;          // realisasi periode i
        $colD = fn ($i) => 3 + $n * 3 + ($i - 1) * 2; // selisih pembanding i
        $lastCol = $n > 1 ? $colD($n - 1) + 1 : $colR($n - 1) + 2;
        $L = fn ($c) => Coordinate::stringFromColumnIndex($c);
        $lastL = $L($lastCol);

        $top = $this->judul($ws, $title.' — '.$this->periodes[0]->label().' dibanding '.($n - 1).' periode', $lastL);
        $h1 = $top;
        $h2 = $top + 1;
        $nama = ['akun' => ['Akun / kategori', 'Tingkat'], 'divisi' => ['Divisi', 'Jumlah toko'], 'toko' => ['Toko', 'Divisi']][$kelompok];
        $ws->setCellValue("A$h1", $nama[0])->mergeCells("A$h1:A$h2");
        $ws->setCellValue("B$h1", $nama[1])->mergeCells("B$h1:B$h2");
        foreach ($this->periodes as $i => $p) {
            $c = $colR($i);
            $ws->setCellValue($L($c).$h1, $p->label().($i === 0 ? ' (utama)' : ''));
            $ws->mergeCells($L($c).$h1.':'.$L($c + 2).$h1);
            $ws->fromArray(['Realisasi', 'Budget', '% Serapan'], null, $L($c).$h2);
            if ($i === 0) {
                $ws->getStyle($L($c).$h1.':'.$L($c + 2).$h2)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB(static::$C_UTAMA);
            }
        }
        for ($i = 1; $i < $n; $i++) {
            $c = $colD($i);
            $ws->setCellValue($L($c).$h1, $this->periodes[$i]->label().' vs utama');
            $ws->mergeCells($L($c).$h1.':'.$L($c + 1).$h1);
            $ws->fromArray(['Selisih (Rp)', '% Perubahan'], null, $L($c).$h2);
        }
        $this->styleHeader($ws, "A$h1:$lastL$h2");
        $ws->getStyle(($n > 0 ? $L($colR(0)) : 'C').$h1.':'.$L($colR(0) + 2).$h2)->getFill()->getStartColor()->setRGB('BFDBFE');
        $ws->getRowDimension($h1)->setRowHeight(22);
        $ws->getRowDimension($h2)->setRowHeight(20);

        $tokoCount = $kelompok === 'divisi' ? DB::table('sub_divisions')->groupBy('division_id')->select('division_id', DB::raw('COUNT(*) as n'))->pluck('n', 'division_id') : collect();
        $r = $h2;
        foreach ($data['rows'] as $row) {
            $r++;
            $isSub = $row->level === 1;
            $ws->setCellValueExplicit("A$r", ($isSub ? '    ' : '').$row->name, DataType::TYPE_STRING);
            $ws->setCellValue("B$r", match ($kelompok) {
                'akun' => $isSub ? 'Kategori' : 'Akun',
                'divisi' => (int) ($tokoCount[$row->division_id] ?? 0),
                default => $row->sub,
            });
            foreach ($this->periodes as $i => $p) {
                $c = $colR($i);
                $rc = $L($c).$r;
                $bc = $L($c + 1).$r;
                $ws->setCellValue($rc, round($row->r[$i], 2));
                if ($row->b[$i] !== null) {
                    $ws->setCellValue($bc, round($row->b[$i], 2));
                    $ws->setCellValue($L($c + 2).$r, "=IF($bc>0,$rc/$bc,\"\")");
                }
            }
            $this->deltaFormulas($ws, $r, $n, $colR, $colD, $L);
            if ($isSub) {
                $ws->getRowDimension($r)->setOutlineLevel(1);
                $ws->getStyle("A$r:$lastL$r")->getFont()->getColor()->setRGB('475569');
            } elseif ($kelompok === 'akun') {
                $ws->getStyle("A$r:$lastL$r")->getFont()->setBold(true);
                $ws->getStyle("A$r:$lastL$r")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB(static::$C_SUB);
            }
        }
        $firstData = $h2 + 1;
        $lastData = max($firstData, $r);

        // Baris total: akun memakai SUMIF (kategori tidak ikut dijumlah), lainnya SUBTOTAL agar ikut filter.
        $t = $lastData + 1;
        $ws->setCellValue("A$t", $kelompok === 'akun' ? 'TOTAL' : 'TOTAL (baris yang tampil)');
        foreach ($this->periodes as $i => $p) {
            foreach ([0, 1] as $k) {
                $col = $L($colR($i) + $k);
                $ws->setCellValue("$col$t", $kelompok === 'akun'
                    ? "=SUMIF(\$B\$$firstData:\$B\$$lastData,\"Akun\",$col$firstData:$col$lastData)"
                    : "=SUBTOTAL(109,$col$firstData:$col$lastData)");
            }
            $rc = $L($colR($i)).$t;
            $bc = $L($colR($i) + 1).$t;
            $ws->setCellValue($L($colR($i) + 2).$t, "=IF($bc>0,$rc/$bc,\"\")");
        }
        if ($kelompok === 'toko') {
            // Budget toko biasanya hanya untuk sebagian toko, jadi total % serapan tidak berarti.
            foreach ($this->periodes as $i => $p) {
                $ws->setCellValue($L($colR($i) + 2).$t, '');
            }
        }
        $this->deltaFormulas($ws, $t, $n, $colR, $colD, $L);
        $ws->getStyle("A$t:$lastL$t")->getFont()->setBold(true)->getColor()->setRGB('FFFFFF');
        $ws->getStyle("A$t:$lastL$t")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB(static::$C_DARK);

        // Format angka dan aturan warna.
        foreach ($this->periodes as $i => $p) {
            $c = $colR($i);
            $ws->getStyle($L($c).$firstData.':'.$L($c + 1).$t)->getNumberFormat()->setFormatCode(static::$RP);
            $ws->getStyle($L($c + 2).$firstData.':'.$L($c + 2).$t)->getNumberFormat()->setFormatCode(static::$PCT);
            $this->serapanRules($ws, $L($c + 2).$firstData.':'.$L($c + 2).$lastData);
            $ws->getColumnDimension($L($c))->setWidth(15);
            $ws->getColumnDimension($L($c + 1))->setWidth(15);
            $ws->getColumnDimension($L($c + 2))->setWidth(10);
        }
        for ($i = 1; $i < $n; $i++) {
            $c = $colD($i);
            $ws->getStyle($L($c).$firstData.':'.$L($c).$t)->getNumberFormat()->setFormatCode(static::$RP_DELTA);
            $ws->getStyle($L($c + 1).$firstData.':'.$L($c + 1).$t)->getNumberFormat()->setFormatCode(static::$PCT_DELTA);
            $this->perubahanRules($ws, $L($c).$firstData.':'.$L($c + 1).$lastData);
            $ws->getColumnDimension($L($c))->setWidth(15);
            $ws->getColumnDimension($L($c + 1))->setWidth(12);
        }
        $this->dataBar($ws, $L($colR(0)).$firstData.':'.$L($colR(0)).$lastData);
        $ws->getColumnDimension('A')->setWidth($kelompok === 'toko' ? 30 : 34);
        $ws->getColumnDimension('B')->setWidth($kelompok === 'toko' ? 22 : 12);
        $this->gridBorders($ws, "A$h1:$lastL$t");
        $ws->getStyle("B$firstData:B$t")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);

        $ws->setAutoFilter("A$h2:$lastL$lastData");
        $ws->freezePane('C'.$firstData);
        $ws->setShowSummaryBelow(false);
        $ws->setSelectedCell('A'.$firstData);
        $this->print($ws, PageSetup::ORIENTATION_LANDSCAPE, $h1.':'.$h2);
    }

    private function deltaFormulas(Worksheet $ws, int $r, int $n, callable $colR, callable $colD, callable $L): void
    {
        $base = '$'.$L($colR(0)).$r;
        for ($i = 1; $i < $n; $i++) {
            $cur = $L($colR($i)).$r;
            $ws->setCellValue($L($colD($i)).$r, "=$cur-$base");
            $ws->setCellValue($L($colD($i) + 1).$r, "=IF($base<>0,($cur-$base)/ABS($base),\"\")");
        }
    }

    // ------------------------------------------------------------------- Data

    private function data(Worksheet $ws): void
    {
        $ws->setTitle('Data');
        $headers = ['No. periode', 'Periode', 'Tahun', 'Bulan awal', 'Bulan akhir', 'Divisi', 'Toko', 'Akun', 'Kategori', 'Jumlah transaksi', 'Realisasi (Rp)'];
        $ws->fromArray($headers, null, 'A1');
        $r = 1;
        foreach ($this->periodes as $i => $p) {
            $q = DB::table('jurnal_umum as ju')
                ->join('sub_divisions as sd', 'sd.id', '=', 'ju.sub_divisi_id')
                ->join('divisions as d', 'd.id', '=', 'sd.division_id')
                ->join('sub_akun_biaya as sab', 'sab.id', '=', 'ju.sub_akun_biaya_id')
                ->join('akun_biaya as ab', 'ab.id', '=', 'sab.akun_biaya_id')
                ->where('ab.dikecualikan', false)
                ->when($this->divisionIds, fn ($x) => $x->whereIn('d.id', $this->divisionIds))
                ->when($this->akunIds, fn ($x) => $x->whereIn('ab.id', $this->akunIds));
            $p->applyToJurnal($q);
            $rows = $q->select('d.name as divisi', 'sd.name as toko', 'ab.name as akun', 'sab.name as kategori', DB::raw('COUNT(*) as jumlah'), DB::raw('SUM(ju.kredit) as total'))
                ->groupBy('d.name', 'sd.name', 'ab.name', 'sab.name')
                ->orderBy('d.name')->orderBy('sd.name')->orderBy('ab.name')->orderBy('sab.name')->get();
            foreach ($rows as $x) {
                $r++;
                $ws->fromArray([
                    $i + 1, $p->label(), $p->tahun, Periode::NAMA_BULAN[$p->bulanDari], Periode::NAMA_BULAN[$p->bulanSampai],
                    $x->divisi, $x->toko, $x->akun, $x->kategori, (int) $x->jumlah, round((float) $x->total, 2),
                ], null, "A$r", true);
            }
        }
        if ($r === 1) {
            $r = 2;
            $ws->setCellValue('B2', 'Tidak ada data');
        }
        $ws->getStyle("K2:K$r")->getNumberFormat()->setFormatCode(static::$RP);
        $ws->getStyle("J2:J$r")->getNumberFormat()->setFormatCode('#,##0');
        $table = new Table("A1:K$r", 'DataRealisasi');
        $style = new TableStyle();
        $style->setTheme(TableStyle::TABLE_STYLE_MEDIUM2);
        $style->setShowRowStripes(true);
        $table->setStyle($style);
        $table->setShowTotalsRow(false);
        $ws->addTable($table);
        foreach (['A' => 10, 'B' => 22, 'C' => 8, 'D' => 12, 'E' => 12, 'F' => 22, 'G' => 28, 'H' => 28, 'I' => 28, 'J' => 12, 'K' => 18] as $c => $w) {
            $ws->getColumnDimension($c)->setWidth($w);
        }
        $ws->getStyle('A1:K1')->getAlignment()->setWrapText(true)->setVertical(Alignment::VERTICAL_CENTER);
        $ws->getRowDimension(1)->setRowHeight(30);
        $ws->freezePane('A2');
    }

    // ---------------------------------------------------------------- Panduan

    private function panduan(Worksheet $ws): void
    {
        $ws->setTitle('Panduan');
        $ws->setShowGridlines(false);
        $lines = [
            ['Cara membaca file ini', true],
            ['', false],
            ['Ringkasan', true],
            ['Total realisasi dan budget setiap periode, % serapan, selisih dibanding periode utama, grafik, dan 5 kategori dengan kenaikan terbesar.', false],
            ['Per Akun & Kategori', true],
            ['Baris tebal = akun. Klik tanda + / − di kiri nomor baris (atau tombol 1 dan 2 di pojok kiri atas) untuk membuka atau melipat kategori.', false],
            ['Per Divisi / Per Toko', true],
            ['Pakai panah filter di judul kolom untuk menyaring atau mengurutkan. Baris TOTAL ikut menyesuaikan dengan baris yang tampil.', false],
            ['Data', true],
            ['Data mentah per periode × divisi × toko × akun × kategori dalam bentuk tabel Excel. Cocok untuk filter cepat atau dibuat PivotTable (Insert → PivotTable).', false],
            ['', false],
            ['Arti angka dan warna', true],
            ['% Serapan = realisasi ÷ budget. Hijau < 80%, kuning 80–100%, merah > 100%.', false],
            ['% Perubahan = (periode pembanding − periode utama) ÷ periode utama. ▲ merah = biaya naik, ▼ hijau = biaya turun.', false],
            ['Budget tahunan dibagi rata per bulan sesuai panjang periode. Budget bulanan dihitung bila bulannya masuk periode.', false],
            ['Realisasi = jumlah kolom kredit jurnal. Akun yang ditandai "bukan biaya" tidak dihitung.', false],
            ['Budget per toko hanya tampil untuk toko yang punya budget toko, jadi total % serapan per toko tidak dihitung.', false],
            ['Persen, selisih, dan total memakai rumus. Bila angka disunting, hasilnya ikut berubah.', false],
            ['', false],
            ['Filter saat export', true],
            ['Divisi: '.$this->namaFilter('divisions', $this->divisionIds, 'Semua divisi'), false],
            ['Akun: '.$this->namaFilter('akun_biaya', $this->akunIds, 'Semua akun'), false],
            ['Periode: '.implode('; ', array_map(fn ($p, $i) => ($i === 0 ? 'Utama ' : 'Pembanding '.$i.' ').$p->label(), $this->periodes, array_keys($this->periodes))), false],
        ];
        foreach ($lines as $i => [$text, $bold]) {
            $ws->setCellValue('B'.($i + 2), $text);
            $ws->getStyle('B'.($i + 2))->getFont()->setBold($bold)->setSize($i === 0 ? 14 : ($bold ? 11 : 10));
            $ws->getStyle('B'.($i + 2))->getAlignment()->setWrapText(true)->setVertical(Alignment::VERTICAL_TOP);
        }
        $ws->getColumnDimension('A')->setWidth(3);
        $ws->getColumnDimension('B')->setWidth(110);
    }

}
