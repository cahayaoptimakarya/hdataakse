<?php

namespace App\Exports;

use App\Services\BudgetTools;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;

class BudgetTemplateExport implements FromArray, WithHeadings, ShouldAutoSize
{
    public function headings(): array
    {
        return BudgetTools::HEADERS;
    }

    public function array(): array
    {
        $tahun = (int) now()->year + 1;
        return [
            [$tahun, '', 'Nama Divisi', '', 'Nama Akun', 120000000],
            [$tahun, 1, 'Nama Divisi', '', 'Akun Lain', 10000000],
            [$tahun, '', '', 'Nama Toko', 'Nama Akun', 24000000],
        ];
    }
}
