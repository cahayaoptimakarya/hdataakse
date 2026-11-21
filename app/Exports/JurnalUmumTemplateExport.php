<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithHeadings;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;

class JurnalUmumTemplateExport implements FromArray, WithHeadings, WithColumnFormatting, ShouldAutoSize
{
    public function headings(): array
    {
        return ['tanggal', 'keterangan', 'toko', 'kategori', 'debet', 'kredit'];
    }

    public function array(): array
    {
        return [
            [
                ExcelDate::PHPToExcel(now()->startOfDay()),
                'Contoh keterangan',
                'Nama Toko',
                'Nama Kategori',
                150000,
                0,
            ],
        ];
    }

    public function columnFormats(): array
    {
        return [
            'A' => 'dd/mm/yyyy',
        ];
    }
}
