<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class LaporanDivisiExport implements WithMultipleSheets
{
    private array $divisionGroups;
    private array $budgetMap;

    public function __construct(array $payload)
    {
        $this->divisionGroups = $this->toArray($payload['division_groups'] ?? []);
        $this->budgetMap = $this->toArray($payload['budget_map'] ?? []);
    }

    private function toArray($value): array
    {
        if ($value instanceof Collection) {
            return $value->toArray();
        }
        if (is_array($value)) {
            return $value;
        }
        return (array) $value;
    }

    public function sheets(): array
    {
        $sheets = [];
        foreach ($this->divisionGroups as $division) {
            $division = $this->toArray($division);
            $title = $this->sanitizeSheetName((string) ($division['division'] ?? 'Divisi'));
            $sheets[] = new LaporanDivisiSheetExport($division, $this->budgetMap, $title);
        }

        if (empty($sheets)) {
            $sheets[] = new LaporanDivisiSheetExport([], $this->budgetMap, 'Laporan');
        }

        return $sheets;
    }

    private function sanitizeSheetName(string $name): string
    {
        $clean = preg_replace('/[\\\\\\/\\?\\*\\[\\]:]/', '-', $name);
        $clean = trim($clean);
        if ($clean === '') {
            $clean = 'Divisi';
        }
        return mb_substr($clean, 0, 31);
    }
}
