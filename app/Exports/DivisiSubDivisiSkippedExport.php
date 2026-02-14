<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;

class DivisiSubDivisiSkippedExport implements FromArray, WithHeadings
{
    private array $rows;

    public function __construct(array $rows)
    {
        $this->rows = $rows;
    }

    public function headings(): array
    {
        return ['row', 'sub_divisi', 'divisi', 'reason'];
    }

    public function array(): array
    {
        return array_map(function ($row) {
            return [
                $row['row'] ?? '',
                $row['sub_divisi'] ?? '',
                $row['divisi'] ?? '',
                $row['reason'] ?? '',
            ];
        }, $this->rows);
    }
}
