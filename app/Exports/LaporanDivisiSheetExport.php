<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

class LaporanDivisiSheetExport implements FromArray, WithEvents, WithTitle
{
    private array $division;
    private array $subDivisions;
    private array $budgetMap;
    private string $title;

    private array $akunHeaderRows = [];
    private array $boldRows = [];
    private int $lastRow = 1;

    public function __construct(array $division, array $budgetMap, string $title)
    {
        $this->division = $this->toArray($division);
        $this->subDivisions = $this->toArray($this->division['sub_divisions'] ?? []);
        $this->budgetMap = $this->toArray($budgetMap);
        $this->title = $title;
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

    public function title(): string
    {
        return $this->title;
    }

    public function array(): array
    {
        $rows = [];
        $subCount = count($this->subDivisions);
        $totalCols = $subCount + 2;

        $header1 = array_fill(0, $totalCols, '');
        $header1[0] = 'Biaya';
        if ($subCount > 0) {
            $header1[1] = 'Sub Divisi';
        }
        $header1[$totalCols - 1] = 'Total';
        $rows[] = $header1;

        $header2 = [''];
        foreach ($this->subDivisions as $subDiv) {
            $header2[] = $subDiv['name'] ?? '';
        }
        $header2[] = '';
        $rows[] = $header2;

        $rowIndex = 2;
        $akunGroups = $this->toArray($this->division['akun_groups'] ?? []);

        foreach ($akunGroups as $akunGroup) {
            $akunGroup = $this->toArray($akunGroup);
            $akunName = $akunGroup['akun'] ?? '';
            $akunId = (int) ($akunGroup['akun_id'] ?? 0);

            $rowIndex++;
            $akunRow = array_fill(0, $totalCols, '');
            $akunRow[0] = $akunName;
            $rows[] = $akunRow;
            $this->akunHeaderRows[] = $rowIndex;
            $this->boldRows[] = $rowIndex;

            $subGroups = $this->toArray($akunGroup['sub_groups'] ?? []);
            foreach ($subGroups as $sub) {
                $sub = $this->toArray($sub);
                $rowIndex++;
                $row = [$sub['sub_akun'] ?? ''];
                $total = 0;

                $cells = $this->toArray($sub['cells'] ?? []);
                foreach ($cells as $cell) {
                    $cell = $this->toArray($cell);
                    $val = (float) ($cell['kredit'] ?? 0);
                    $row[] = $val;
                    $total += $val;
                }

                while (count($row) < $subCount + 1) {
                    $row[] = 0;
                }

                $row[] = $total;
                $rows[] = $row;
            }

            $rowIndex++;
            $totalRow = ['Total '.$akunName];
            $totalAkun = 0;
            foreach ($this->subDivisions as $subDiv) {
                $totals = $akunGroup['totals_by_sub_divisi'][$subDiv['id']] ?? ['kredit' => 0];
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
            foreach ($this->subDivisions as $subDiv) {
                $val = (float) ($this->budgetMap[$akunId][$subDiv['id']] ?? 0);
                $budgetRow[] = $val > 0 ? $val : '';
                $budgetTotal += $val;
            }
            $budgetRow[] = $budgetTotal > 0 ? $budgetTotal : '';
            $rows[] = $budgetRow;
            $this->boldRows[] = $rowIndex;

            $rowIndex++;
            $selisihRow = ['Selisih'];
            $selisihTotal = 0;
            foreach ($this->subDivisions as $subDiv) {
                $actual = (float) ($akunGroup['totals_by_sub_divisi'][$subDiv['id']]['kredit'] ?? 0);
                $budget = (float) ($this->budgetMap[$akunId][$subDiv['id']] ?? 0);
                $diff = $budget - $actual;
                $selisihRow[] = $diff;
                $selisihTotal += $diff;
            }
            $selisihRow[] = $selisihTotal;
            $rows[] = $selisihRow;
            $this->boldRows[] = $rowIndex;
        }

        $grandBySub = $this->toArray($this->division['grand_by_sub_divisi'] ?? []);
        $grandTotalKredit = (float) ($this->division['grand_total_kredit'] ?? 0);

        $rowIndex++;
        $grandTotalRow = ['Grand Total'];
        $grandTotal = 0;
        foreach ($this->subDivisions as $subDiv) {
            $val = (float) ($grandBySub[$subDiv['id']]['kredit'] ?? 0);
            $grandTotalRow[] = $val;
            $grandTotal += $val;
        }
        $grandTotalRow[] = $grandTotalKredit ?: $grandTotal;
        $rows[] = $grandTotalRow;
        $this->boldRows[] = $rowIndex;

        $rowIndex++;
        $grandBudgetRow = ['Grand Budget'];
        $grandBudget = 0;
        foreach ($this->subDivisions as $subDiv) {
            $sum = 0;
            foreach ($this->budgetMap as $akunBudgets) {
                $sum += (float) ($akunBudgets[$subDiv['id']] ?? 0);
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
        foreach ($this->subDivisions as $subDiv) {
            $actual = (float) ($grandBySub[$subDiv['id']]['kredit'] ?? 0);
            $budget = 0;
            foreach ($this->budgetMap as $akunBudgets) {
                $budget += (float) ($akunBudgets[$subDiv['id']] ?? 0);
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
                $subCount = count($this->subDivisions);
                $totalCols = $subCount + 2;
                $lastCol = Coordinate::stringFromColumnIndex($totalCols);

                $sheet->mergeCells("A1:A2");
                if ($subCount > 0) {
                    $subEnd = Coordinate::stringFromColumnIndex(1 + $subCount);
                    $sheet->mergeCells("B1:{$subEnd}1");
                }
                $sheet->mergeCells("{$lastCol}1:{$lastCol}2");

                foreach ($this->akunHeaderRows as $row) {
                    $sheet->mergeCells("A{$row}:{$lastCol}{$row}");
                }

                foreach ($this->boldRows as $row) {
                    $sheet->getStyle("A{$row}:{$lastCol}{$row}")->getFont()->setBold(true);
                }
                $sheet->getStyle("A1:{$lastCol}2")->getFont()->setBold(true);

                $sheet->getStyle("A1:{$lastCol}2")->getAlignment()
                    ->setVertical(Alignment::VERTICAL_CENTER)
                    ->setHorizontal(Alignment::HORIZONTAL_CENTER);

                if ($this->lastRow >= 3) {
                    $sheet->getStyle("B3:{$lastCol}{$this->lastRow}")
                        ->getNumberFormat()
                        ->setFormatCode('#,##0.00');
                }
            },
        ];
    }
}
