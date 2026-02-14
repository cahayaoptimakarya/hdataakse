<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;

class AkunPembayaranSkippedExport implements FromArray, WithHeadings
{
    private array $rows;

    public function __construct(array $rows)
    {
        $this->rows = $rows;
    }

    public function headings(): array
    {
        return ['row', 'sub_akun', 'akun', 'reason'];
    }

    public function array(): array
    {
        return array_map(function ($row) {
            return [
                $row['row'] ?? '',
                $row['sub_akun'] ?? '',
                $row['akun'] ?? '',
                $row['reason'] ?? '',
            ];
        }, $this->rows);
    }
}
