<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

class LaporanExport implements FromArray, WithEvents
{
    private array $divisions;
    private array $akunGroups;
    private array $grandByDivision;
    private float $grandTotalKredit;
    private array $budgetMap;

    private array $akunHeaderRows = [];
    private array $boldRows = [];
    private int $lastRow = 1;

    public function __construct(array $payload)
    {
        $this->divisions = $this->toArray($payload['divisions'] ?? []);
        $this->akunGroups = $this->toArray($payload['akun_groups'] ?? []);
        $this->grandByDivision = $this->toArray($payload['grand_by_division'] ?? []);
        $this->grandTotalKredit = (float) ($payload['grand_total_kredit'] ?? 0);
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

    public function array(): array
    {
        $rows = [];
        $divisionCount = count($this->divisions);
        $totalCols = $divisionCount + 2;

        $header1 = array_fill(0, $totalCols, '');
        $header1[0] = 'Biaya';
        if ($divisionCount > 0) {
            $header1[1] = 'Divisi';
        }
        $header1[$totalCols - 1] = 'Total';
        $rows[] = $header1;

        $header2 = [''];
        foreach ($this->divisions as $div) {
            $header2[] = $div['name'];
        }
        $header2[] = '';
        $rows[] = $header2;

        $rowIndex = 2;

        foreach ($this->akunGroups as $akunGroup) {
            $rowIndex++;
            $akunRow = array_fill(0, $totalCols, '');
            $akunRow[0] = $akunGroup['akun'];
            $rows[] = $akunRow;
            $this->akunHeaderRows[] = $rowIndex;
            $this->boldRows[] = $rowIndex;

            foreach ($akunGroup['sub_groups'] as $sub) {
                $rowIndex++;
                $row = [$sub['sub_akun']];
                $total = 0;
                foreach ($sub['cells'] as $cell) {
                    $val = (float) ($cell['kredit'] ?? 0);
                    $row[] = $val;
                    $total += $val;
                }
                $row[] = $total;
                $rows[] = $row;
            }

            $rowIndex++;
            $totalRow = ['Total '.$akunGroup['akun']];
            $totalAkun = 0;
            foreach ($this->divisions as $div) {
                $totals = $akunGroup['totals_by_division'][$div['id']] ?? ['kredit' => 0];
                $val = (float) ($totals['kredit'] ?? 0);
                $totalRow[] = $val;
                $totalAkun += $val;
            }
            $totalRow[] = $totalAkun;
            $rows[] = $totalRow;
            $this->boldRows[] = $rowIndex;

            $rowIndex++;
            $budgetRow = ['Budget'];
            $budgetTotal = 0;
            foreach ($this->divisions as $div) {
                $val = (float) ($this->budgetMap[$akunGroup['akun_id']][$div['id']] ?? 0);
                $budgetRow[] = $val > 0 ? $val : '';
                $budgetTotal += $val;
            }
            $budgetRow[] = $budgetTotal > 0 ? $budgetTotal : '';
            $rows[] = $budgetRow;
            $this->boldRows[] = $rowIndex;

            $rowIndex++;
            $selisihRow = ['Selisih'];
            $selisihTotal = 0;
            foreach ($this->divisions as $div) {
                $actual = (float) (($akunGroup['totals_by_division'][$div['id']]['kredit'] ?? 0));
                $budget = (float) ($this->budgetMap[$akunGroup['akun_id']][$div['id']] ?? 0);
                $diff = $budget - $actual;
                $selisihRow[] = $diff;
                $selisihTotal += $diff;
            }
            $selisihRow[] = $selisihTotal;
            $rows[] = $selisihRow;
            $this->boldRows[] = $rowIndex;
        }

        $rowIndex++;
        $grandTotalRow = ['Grand Total'];
        $grandTotal = 0;
        foreach ($this->divisions as $div) {
            $val = (float) ($this->grandByDivision[$div['id']]['kredit'] ?? 0);
            $grandTotalRow[] = $val;
            $grandTotal += $val;
        }
        $grandTotalRow[] = $this->grandTotalKredit ?: $grandTotal;
        $rows[] = $grandTotalRow;
        $this->boldRows[] = $rowIndex;

        $rowIndex++;
        $grandBudgetRow = ['Grand Budget'];
        $grandBudget = 0;
        foreach ($this->divisions as $div) {
            $sum = 0;
            foreach ($this->budgetMap as $akunBudgets) {
                $sum += (float) ($akunBudgets[$div['id']] ?? 0);
            }
            $grandBudgetRow[] = $sum > 0 ? $sum : '';
            $grandBudget += $sum;
        }
        $grandBudgetRow[] = $grandBudget > 0 ? $grandBudget : '';
        $rows[] = $grandBudgetRow;
        $this->boldRows[] = $rowIndex;

        $rowIndex++;
        $grandSelisihRow = ['Grand Selisih'];
        $grandSelisihTotal = 0;
        foreach ($this->divisions as $div) {
            $actual = (float) ($this->grandByDivision[$div['id']]['kredit'] ?? 0);
            $budget = 0;
            foreach ($this->budgetMap as $akunBudgets) {
                $budget += (float) ($akunBudgets[$div['id']] ?? 0);
            }
            $diff = $budget - $actual;
            $grandSelisihRow[] = $diff;
            $grandSelisihTotal += $diff;
        }
        $grandSelisihRow[] = $grandSelisihTotal;
        $rows[] = $grandSelisihRow;
        $this->boldRows[] = $rowIndex;

        $this->lastRow = $rowIndex;

        return $rows;
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $divisionCount = count($this->divisions);
                $totalCols = $divisionCount + 2;
                $lastCol = Coordinate::stringFromColumnIndex($totalCols);

                // Merge header
                $sheet->mergeCells("A1:A2");
                if ($divisionCount > 0) {
                    $divEnd = Coordinate::stringFromColumnIndex(1 + $divisionCount);
                    $sheet->mergeCells("B1:{$divEnd}1");
                }
                $sheet->mergeCells("{$lastCol}1:{$lastCol}2");

                // Merge akun header rows
                foreach ($this->akunHeaderRows as $row) {
                    $sheet->mergeCells("A{$row}:{$lastCol}{$row}");
                }

                // Bold rows
                foreach ($this->boldRows as $row) {
                    $sheet->getStyle("A{$row}:{$lastCol}{$row}")->getFont()->setBold(true);
                }
                $sheet->getStyle("A1:{$lastCol}2")->getFont()->setBold(true);

                // Alignment for header
                $sheet->getStyle("A1:{$lastCol}2")->getAlignment()
                    ->setVertical(Alignment::VERTICAL_CENTER)
                    ->setHorizontal(Alignment::HORIZONTAL_CENTER);

                // Number format for numeric cells
                if ($this->lastRow >= 3) {
                    $sheet->getStyle("B3:{$lastCol}{$this->lastRow}")
                        ->getNumberFormat()
                        ->setFormatCode('#,##0.00');
                }
            },
        ];
    }
}
