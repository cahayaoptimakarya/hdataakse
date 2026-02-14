<?php

namespace App\Imports;

use App\Models\JurnalUmum;
use App\Models\SubAkunBiaya;
use App\Models\SubDivision;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\OnEachRow;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Row;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;

class JurnalUmumImport implements OnEachRow, WithHeadingRow, SkipsEmptyRows
{
    public int $created = 0;
    public int $skipped = 0;
    public array $errors = [];

    private bool $headerChecked = false;
    private Collection $validSubDivisi;
    private Collection $validSubAkun;

    public function __construct()
    {
        $this->validSubDivisi = SubDivision::pluck('id')->flip();
        $this->validSubAkun = SubAkunBiaya::pluck('id')->flip();
    }

    public function onRow(Row $row): void
    {
        $rowIndex = $row->getIndex();
        $data = $row->toArray();

        $this->validateHeaders($data);

        $tanggalRaw = $data['tanggal'] ?? null;
        $keterangan = trim((string) ($data['keterangan'] ?? ''));
        $subDivisiId = (int) ($data['sub_divisi_id'] ?? 0);
        $subAkunId = (int) ($data['sub_akun_biaya_id'] ?? 0);
        $debetRaw = $data['debet'] ?? null;
        $kreditRaw = $data['kredit'] ?? null;

        if ($this->isRowEmpty($tanggalRaw, $keterangan, $subDivisiId, $subAkunId, $debetRaw, $kreditRaw)) {
            return;
        }

        $tanggal = $this->parseDate($tanggalRaw);
        if (!$tanggal) {
            $this->errors[] = "Baris {$rowIndex}: tanggal tidak valid";
            $this->skipped++;
            return;
        }

        if (!$this->validSubDivisi->has($subDivisiId)) {
            $this->errors[] = "Baris {$rowIndex}: sub_divisi_id tidak valid";
            $this->skipped++;
            return;
        }
        if (!$this->validSubAkun->has($subAkunId)) {
            $this->errors[] = "Baris {$rowIndex}: sub_akun_biaya_id tidak valid";
            $this->skipped++;
            return;
        }

        $debet = $this->parseAmount($debetRaw);
        $kredit = $this->parseAmount($kreditRaw);

        if ($debet === null || $kredit === null) {
            $this->errors[] = "Baris {$rowIndex}: debet/kredit tidak valid";
            $this->skipped++;
            return;
        }

        if ($debet < 0 || $kredit < 0) {
            $this->errors[] = "Baris {$rowIndex}: debet/kredit harus >= 0";
            $this->skipped++;
            return;
        }

        JurnalUmum::create([
            'tanggal' => $tanggal,
            'keterangan' => $keterangan !== '' ? $keterangan : null,
            'sub_divisi_id' => $subDivisiId,
            'sub_akun_biaya_id' => $subAkunId,
            'debet' => $debet,
            'kredit' => $kredit,
        ]);

        $this->created++;
    }

    protected function validateHeaders(array $row): void
    {
        if ($this->headerChecked) {
            return;
        }

        $expected = ['tanggal', 'keterangan', 'sub_divisi_id', 'sub_akun_biaya_id', 'debet', 'kredit'];
        $missing = array_diff($expected, array_keys($row));
        if ($missing) {
            throw new \RuntimeException('Header harus: tanggal, keterangan, sub_divisi_id, sub_akun_biaya_id, debet, kredit');
        }

        $this->headerChecked = true;
    }

    protected function isRowEmpty($tanggal, string $keterangan, int $subDivisiId, int $subAkunId, $debet, $kredit): bool
    {
        $tanggalEmpty = $tanggal === null || $tanggal === '';
        $debetEmpty = $debet === null || $debet === '';
        $kreditEmpty = $kredit === null || $kredit === '';

        return $tanggalEmpty && $keterangan === '' && $subDivisiId === 0 && $subAkunId === 0 && $debetEmpty && $kreditEmpty;
    }

    protected function parseDate($value): ?string
    {
        if ($value instanceof \DateTimeInterface) {
            return $value->format('Y-m-d');
        }

        if (is_numeric($value)) {
            try {
                return ExcelDate::excelToDateTimeObject($value)->format('Y-m-d');
            } catch (\Throwable $e) {
                return null;
            }
        }

        $value = trim((string) $value);
        if ($value === '') return null;

        $formats = ['Y-m-d', 'd/m/Y', 'd-m-Y', 'm/d/Y'];
        foreach ($formats as $format) {
            $dt = \DateTime::createFromFormat($format, $value);
            if ($dt && $dt->format($format) === $value) {
                return $dt->format('Y-m-d');
            }
        }

        return null;
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
}
