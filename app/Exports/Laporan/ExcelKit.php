<?php

namespace App\Exports\Laporan;

use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Conditional;
use PhpOffice\PhpSpreadsheet\Style\ConditionalFormatting\ConditionalDataBar;
use PhpOffice\PhpSpreadsheet\Style\ConditionalFormatting\ConditionalFormatValueObject;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\PageSetup;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Gaya bersama untuk semua export laporan: judul, header, garis, warna status, data bar, cetak.
 * Kelas pemakai menyediakan $divisionIds dan $akunIds.
 */
trait ExcelKit
{
    protected static string $RP = '#,##0;[Red]-#,##0;"–"';
    protected static string $RP_DELTA = '+#,##0;-#,##0;"–"';
    protected static string $PCT = '0.0%;-0.0%;"–"';
    protected static string $PCT_DELTA = '▲ 0.0%;▼ 0.0%;"sama"';

    protected static string $C_DARK = '0F172A';
    protected static string $C_HEAD = 'E2E8F0';
    protected static string $C_SUB = 'F1F5F9';
    protected static string $C_UTAMA = 'DBEAFE';

    /** Keterangan filter tambahan di baris info, mis. "Toko: A, B". */
    protected array $infoTambahan = [];

    protected function judul(Worksheet $ws, string $title, string $lastCol): int
    {
        $ws->setCellValue('A1', $title);
        $ws->getStyle('A1')->getFont()->setBold(true)->setSize(15)->getColor()->setRGB(static::$C_DARK);
        $ws->setCellValue('A2', 'Divisi: '.$this->namaFilter('divisions', $this->divisionIds, 'Semua divisi')
            .'   ·   Akun: '.$this->namaFilter('akun_biaya', $this->akunIds, 'Semua akun')
            .($this->infoTambahan ? '   ·   '.implode('   ·   ', $this->infoTambahan) : '')
            .'   ·   Dibuat '.now()->translatedFormat('d F Y H:i').($this->dibuatOleh ? ' oleh '.$this->dibuatOleh : ''));
        $ws->getStyle('A2')->getFont()->setSize(9)->getColor()->setRGB('64748B');
        $ws->setCellValue('A3', 'Nilai dalam Rupiah. Merah pada % serapan = melebihi budget; ▲ merah = biaya naik dibanding periode utama.');
        $ws->getStyle('A3')->getFont()->setSize(9)->setItalic(true)->getColor()->setRGB('64748B');

        return 5;
    }

    protected function namaFilter(string $table, array $ids, string $semua): string
    {
        return $ids ? DB::table($table)->whereIn('id', $ids)->orderBy('name')->pluck('name')->implode(', ') : $semua;
    }

    protected function styleHeader(Worksheet $ws, string $range): void
    {
        $s = $ws->getStyle($range);
        $s->getFont()->setBold(true)->getColor()->setRGB(static::$C_DARK);
        $s->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB(static::$C_HEAD);
        $s->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER)->setWrapText(true);
    }

    protected function gridBorders(Worksheet $ws, string $range): void
    {
        $ws->getStyle($range)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN)->getColor()->setRGB('CBD5E1');
    }

    /** Hijau < 80%, kuning 80–100%, merah > 100%. */
    protected function serapanRules(Worksheet $ws, string $range): void
    {
        $first = explode(':', $range)[0];
        $mk = function (string $formula, string $font, string $fill) {
            $c = new Conditional();
            $c->setConditionType(Conditional::CONDITION_EXPRESSION)->addCondition($formula);
            $c->getStyle()->getFont()->setBold(true)->getColor()->setRGB($font);
            $c->getStyle()->getFill()->setFillType(Fill::FILL_SOLID)->getEndColor()->setRGB($fill);
            $c->getStyle()->getFill()->getStartColor()->setRGB($fill);
            return $c;
        };
        $ws->getStyle($range)->setConditionalStyles([
            $mk("AND(ISNUMBER($first),$first>1)", '991B1B', 'FEE2E2'),
            $mk("AND(ISNUMBER($first),$first>=0.8)", '92400E', 'FEF3C7'),
            $mk("AND(ISNUMBER($first),$first<0.8)", '166534', 'DCFCE7'),
        ]);
    }

    /** Naik = merah, turun = hijau. */
    protected function perubahanRules(Worksheet $ws, string $range): void
    {
        $first = explode(':', $range)[0];
        $up = new Conditional();
        $up->setConditionType(Conditional::CONDITION_EXPRESSION)->addCondition("AND(ISNUMBER($first),$first>0)");
        $up->getStyle()->getFont()->getColor()->setRGB('DC2626');
        $down = new Conditional();
        $down->setConditionType(Conditional::CONDITION_EXPRESSION)->addCondition("AND(ISNUMBER($first),$first<0)");
        $down->getStyle()->getFont()->getColor()->setRGB('16A34A');
        $ws->getStyle($range)->setConditionalStyles([$up, $down]);
    }

    protected function dataBar(Worksheet $ws, string $range): void
    {
        $c = new Conditional();
        $c->setConditionType(Conditional::CONDITION_DATABAR);
        $c->setDataBar(new ConditionalDataBar());
        $c->getDataBar()
            ->setMinimumConditionalFormatValueObject(new ConditionalFormatValueObject('min'))
            ->setMaximumConditionalFormatValueObject(new ConditionalFormatValueObject('max'))
            ->setColor('FF93C5FD');
        $existing = $ws->getStyle($range)->getConditionalStyles();
        $ws->getStyle($range)->setConditionalStyles(array_merge($existing, [$c]));
    }

    protected function print(Worksheet $ws, string $orientation, ?string $repeatRows = null): void
    {
        $ws->getPageSetup()->setOrientation($orientation)->setPaperSize(PageSetup::PAPERSIZE_A4)
            ->setFitToWidth(1)->setFitToHeight(0);
        if ($repeatRows) {
            [$a, $b] = explode(':', $repeatRows);
            $ws->getPageSetup()->setRowsToRepeatAtTopByStartAndEnd((int) $a, (int) $b);
        }
        $ws->getPageMargins()->setTop(0.5)->setBottom(0.5)->setLeft(0.4)->setRight(0.4);
        $ws->getHeaderFooter()->setOddFooter('&L&8'.$ws->getTitle().'&R&8Halaman &P dari &N');
    }

    protected function setWidths(Worksheet $ws, array $widths): void
    {
        foreach ($widths as $col => $w) {
            $ws->getColumnDimension($col)->setWidth($w);
        }
    }

    protected function panduanSheet(Worksheet $ws, array $lines): void
    {
        $ws->setTitle('Panduan');
        $ws->setShowGridlines(false);
        foreach ($lines as $i => [$text, $bold]) {
            $ws->setCellValue('B'.($i + 2), $text);
            $ws->getStyle('B'.($i + 2))->getFont()->setBold($bold)->setSize($i === 0 ? 14 : ($bold ? 11 : 10));
            $ws->getStyle('B'.($i + 2))->getAlignment()->setWrapText(true)->setVertical(Alignment::VERTICAL_TOP);
        }
        $ws->getColumnDimension('A')->setWidth(3);
        $ws->getColumnDimension('B')->setWidth(110);
    }

    /** Baris panduan umum tentang angka dan warna. */
    protected function panduanUmum(): array
    {
        return [
            ['Arti angka dan warna', true],
            ['% Serapan = realisasi ÷ budget. Hijau < 80%, kuning 80–100%, merah > 100%. "Tanpa budget" = ada realisasi tetapi budget belum diisi.', false],
            ['Budget tahunan dibagi rata per bulan sesuai panjang periode. Budget bulanan dihitung bila bulannya masuk periode.', false],
            ['Realisasi = jumlah kolom kredit jurnal. Akun yang ditandai "bukan biaya" tidak dihitung.', false],
            ['Persen, selisih, dan total memakai rumus. Bila angka disunting, hasilnya ikut berubah.', false],
            ['Panah filter di judul kolom bisa dipakai untuk menyaring dan mengurutkan. Tanda + / − di kiri nomor baris membuka atau melipat rincian.', false],
            ['Sheet "Data" berisi data mentah dalam tabel Excel. Pilih satu sel di dalamnya lalu Insert → PivotTable untuk membuat analisis sendiri.', false],
        ];
    }
}
