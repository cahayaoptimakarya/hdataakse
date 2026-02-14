<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;

class JurnalUmumErrorExport implements FromArray, WithHeadings
{
    public function __construct(private array $rows)
    {
    }

    public function headings(): array
    {
        return [
            'keterangan',
            'toko',
            'kategori',
            'debet',
            'kredit',
            'error',
            'row',
        ];
    }

    public function array(): array
    {
        return $this->rows;
    }
}
