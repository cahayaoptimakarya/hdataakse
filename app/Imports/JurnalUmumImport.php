<?php

namespace App\Imports;

use App\Models\JurnalUmum;
use App\Models\SubAkunBiaya;
use App\Models\SubDivision;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\OnEachRow;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterImport;
use Maatwebsite\Excel\Imports\HeadingRowFormatter;
use Maatwebsite\Excel\Row;

class JurnalUmumImport implements OnEachRow, WithHeadingRow, SkipsEmptyRows, WithEvents
{
    public int $created = 0;
    public int $skipped = 0;
    public array $errors = [];
    public array $errorRows = [];

    private bool $headerChecked = false;
    private Collection $validSubDivisi;
    private Collection $validSubAkun;
    private array $pendingRows = [];
    private array $headingColumnMap = [];

    public function __construct()
    {
        $this->validSubDivisi = SubDivision::pluck('id', 'name')
            ->mapWithKeys(fn ($id, $name) => [self::normalizeName($name) => $id]);
        $this->validSubAkun = SubAkunBiaya::pluck('id', 'name')
            ->mapWithKeys(fn ($id, $name) => [self::normalizeName($name) => $id]);
    }

    public function onRow(Row $row): void
    {
        $rowIndex = $row->getIndex();
        $data = $row->toArray();

        $this->validateHeaders($data);

        $keterangan = trim((string) ($data['keterangan'] ?? ''));
        $toko = trim((string) ($data['toko'] ?? ''));
        $kategori = trim((string) ($data['kategori'] ?? ''));
        $debetRaw = $data['debet'] ?? null;
        $kreditRaw = $data['kredit'] ?? null;

        if ($this->isRowEmpty($keterangan, $toko, $kategori, $debetRaw, $kreditRaw)) {
            return;
        }

        $subDivisiId = $this->validSubDivisi->get(self::normalizeName($toko));
        if (!$subDivisiId) {
            $this->errors[] = "Baris {$rowIndex}: toko tidak ditemukan";
            $this->addErrorRow($row, $data, "toko tidak ditemukan", $rowIndex);
            $this->skipped++;
            return;
        }
        $subAkunId = $this->validSubAkun->get(self::normalizeName($kategori));
        if (!$subAkunId) {
            $this->errors[] = "Baris {$rowIndex}: kategori tidak ditemukan";
            $this->addErrorRow($row, $data, "kategori tidak ditemukan", $rowIndex);
            $this->skipped++;
            return;
        }

        $debet = $this->parseAmount($debetRaw);
        $kredit = $this->parseAmount($kreditRaw);

        if ($debet === null || $kredit === null) {
            $this->errors[] = "Baris {$rowIndex}: debet/kredit tidak valid";
            $this->addErrorRow($row, $data, "debet/kredit tidak valid", $rowIndex);
            $this->skipped++;
            return;
        }

        if ($debet < 0 || $kredit < 0) {
            $this->errors[] = "Baris {$rowIndex}: debet/kredit harus >= 0";
            $this->addErrorRow($row, $data, "debet/kredit harus >= 0", $rowIndex);
            $this->skipped++;
            return;
        }

        $this->pendingRows[] = [
            'tanggal' => null,
            'keterangan' => $keterangan !== '' ? $keterangan : null,
            'sub_divisi_id' => $subDivisiId,
            'sub_akun_biaya_id' => $subAkunId,
            'debet' => $debet,
            'kredit' => $kredit,
        ];
    }

    protected function validateHeaders(array $row): void
    {
        if ($this->headerChecked) {
            return;
        }

        $expected = ['keterangan', 'toko', 'kategori', 'debet', 'kredit'];
        $missing = array_diff($expected, array_keys($row));
        if ($missing) {
            throw new \RuntimeException('Header harus: keterangan, toko, kategori, debet, kredit');
        }

        $this->headerChecked = true;
    }

    protected function isRowEmpty(string $keterangan, string $toko, string $kategori, $debet, $kredit): bool
    {
        $debetEmpty = $debet === null || $debet === '';
        $kreditEmpty = $kredit === null || $kredit === '';

        return $keterangan === '' && $toko === '' && $kategori === '' && $debetEmpty && $kreditEmpty;
    }

    protected function addErrorRow(Row $row, array $data, string $message, int $rowIndex): void
    {
        $this->errorRows[] = [
            $this->getDisplayValue($row, $data, 'keterangan'),
            $this->getDisplayValue($row, $data, 'toko'),
            $this->getDisplayValue($row, $data, 'kategori'),
            $this->getDisplayValue($row, $data, 'debet'),
            $this->getDisplayValue($row, $data, 'kredit'),
            $message,
            $rowIndex,
        ];
    }

    protected static function normalizeName(?string $value): string
    {
        $value = trim((string) $value);
        return mb_strtolower($value);
    }


    protected function parseAmount($value): ?float
    {
        if ($value === null || $value === '') return 0.0;
        if (is_numeric($value)) return (float) $value;

        $value = str_replace([' ', "\xc2\xa0"], '', (string) $value);

        $hasComma = str_contains($value, ',');
        $hasDot = str_contains($value, '.');

        if ($hasComma && $hasDot) {
            $lastComma = strrpos($value, ',');
            $lastDot = strrpos($value, '.');
            if ($lastComma > $lastDot) {
                $value = str_replace('.', '', $value);
                $value = str_replace(',', '.', $value);
            } else {
                $value = str_replace(',', '', $value);
            }
        } elseif ($hasComma) {
            $value = str_replace(',', '.', $value);
        }

        if (!is_numeric($value)) {
            return null;
        }

        return (float) $value;
    }

    protected function getDisplayValue(Row $row, array $data, string $key): string
    {
        $formatted = $this->getFormattedCellValue($row, $key);
        if ($formatted !== null && $formatted !== '') {
            return $formatted;
        }

        $fallback = $data[$key] ?? '';
        return is_string($fallback) ? $fallback : (string) $fallback;
    }

    protected function getFormattedCellValue(Row $row, string $key): ?string
    {
        $this->buildHeadingColumnMap($row);
        $column = $this->headingColumnMap[$key] ?? null;
        if (!$column) {
            return null;
        }

        $worksheet = $row->getDelegate()->getWorksheet();
        $cell = $worksheet->getCell($column.$row->getIndex());
        if (!$cell) {
            return null;
        }

        $value = $cell->getFormattedValue();
        if ($value === null) {
            return null;
        }

        return (string) $value;
    }

    protected function buildHeadingColumnMap(Row $row): void
    {
        if (!empty($this->headingColumnMap)) {
            return;
        }

        $worksheet = $row->getDelegate()->getWorksheet();
        $headingRowIndex = 1;
        $headerRow = $worksheet->getRowIterator($headingRowIndex, $headingRowIndex)->current();
        if (!$headerRow) {
            return;
        }

        $cellIterator = $headerRow->getCellIterator();
        $cellIterator->setIterateOnlyExistingCells(true);

        $headings = [];
        $columns = [];
        foreach ($cellIterator as $cell) {
            $headings[] = (string) $cell->getValue();
            $columns[] = $cell->getColumn();
        }

        $formattedHeadings = HeadingRowFormatter::format($headings);
        foreach ($formattedHeadings as $idx => $heading) {
            if ($heading === '') {
                continue;
            }
            if (!isset($columns[$idx])) {
                continue;
            }
            if (!isset($this->headingColumnMap[$heading])) {
                $this->headingColumnMap[$heading] = $columns[$idx];
            }
        }
    }

    public function registerEvents(): array
    {
        return [
            AfterImport::class => function () {
                if ($this->skipped > 0 || !empty($this->errors) || !empty($this->errorRows)) {
                    return;
                }
                if (empty($this->pendingRows)) {
                    return;
                }

                foreach ($this->pendingRows as $payload) {
                    JurnalUmum::create($payload);
                }
                $this->created = count($this->pendingRows);
            },
        ];
    }
}
