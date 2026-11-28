<?php

namespace App\Imports;

use App\Models\JurnalUmum;
use App\Models\SubAkunBiaya;
use App\Models\SubDivision;
use App\Support\JurnalRowHash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\OnEachRow;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterImport;
use Maatwebsite\Excel\Imports\HeadingRowFormatter;
use Maatwebsite\Excel\Row;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;

class JurnalUmumImport implements OnEachRow, WithHeadingRow, SkipsEmptyRows, WithEvents
{
    public int $created = 0;
    public int $skipped = 0;
    public array $errors = [];
    public array $errorRows = [];
    public int $duplicateCount = 0;
    public float $totalDebet = 0;
    public float $totalKredit = 0;
    public ?string $tanggalAwal = null;
    public ?string $tanggalAkhir = null;

    private bool $headerChecked = false;
    private Collection $validSubDivisi;
    private Collection $validSubAkun;
    private array $pendingRows = [];
    private array $headingColumnMap = [];

    public function __construct(private ?int $batchId = null)
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

        $tanggalRaw = $data['tanggal'] ?? null;
        $keterangan = trim((string) ($data['keterangan'] ?? ''));
        $toko = trim((string) ($data['toko'] ?? ''));
        $kategori = trim((string) ($data['kategori'] ?? ''));
        $debetRaw = $data['debet'] ?? null;
        $kreditRaw = $data['kredit'] ?? null;

        if ($this->isRowEmpty($tanggalRaw, $keterangan, $toko, $kategori, $debetRaw, $kreditRaw)) {
            return;
        }

        $tanggal = $this->parseDate($tanggalRaw);
        if ($tanggal === null) {
            $message = $this->isBlank($tanggalRaw) ? 'tanggal wajib diisi' : 'tanggal tidak valid';
            $this->errors[] = "Baris {$rowIndex}: {$message}";
            $this->addErrorRow($row, $data, $message, $rowIndex);
            $this->skipped++;
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

        $debet = self::parseAmount($debetRaw);
        $kredit = self::parseAmount($kreditRaw);

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

        $keteranganValue = $keterangan !== '' ? $keterangan : null;
        $this->pendingRows[] = [
            'payload' => [
                'tanggal' => $tanggal,
                'keterangan' => $keteranganValue,
                'sub_divisi_id' => $subDivisiId,
                'sub_akun_biaya_id' => $subAkunId,
                'debet' => $debet,
                'kredit' => $kredit,
                'row_hash' => JurnalRowHash::make($tanggal, $subDivisiId, $subAkunId, $debet, $kredit, $keteranganValue),
            ],
            'display' => $this->displayValues($row, $data),
            'row_index' => $rowIndex,
        ];
    }

    protected function validateHeaders(array $row): void
    {
        if ($this->headerChecked) {
            return;
        }

        $expected = ['tanggal', 'keterangan', 'toko', 'kategori', 'debet', 'kredit'];
        $missing = array_diff($expected, array_keys($row));
        if ($missing) {
            throw new \RuntimeException('Header harus: tanggal, keterangan, toko, kategori, debet, kredit');
        }

        $this->headerChecked = true;
    }

    protected function isRowEmpty($tanggal, string $keterangan, string $toko, string $kategori, $debet, $kredit): bool
    {
        return $this->isBlank($tanggal)
            && $keterangan === '' && $toko === '' && $kategori === ''
            && $this->isBlank($debet) && $this->isBlank($kredit);
    }

    protected function isBlank($value): bool
    {
        return $value === null || (is_string($value) && trim($value) === '');
    }

    /**
     * Ubah nilai tanggal dari Excel menjadi format Y-m-d.
     * Menerima sel tanggal Excel (serial number) dan teks
     * dd/mm/yyyy, dd-mm-yyyy, dd.mm.yyyy, atau yyyy-mm-dd.
     */
    public static function parseDate($value): ?string
    {
        if ($value === null) {
            return null;
        }

        if ($value instanceof \DateTimeInterface) {
            return $value->format('Y-m-d');
        }

        if (is_int($value) || is_float($value) || (is_string($value) && preg_match('/^\d+(\.\d+)?$/', trim($value)))) {
            $serial = (float) $value;
            // Batas wajar serial tanggal Excel (1900-01-01 s/d 9999-12-31).
            if ($serial < 1 || $serial > 2958465) {
                return null;
            }
            try {
                return ExcelDate::excelToDateTimeObject($serial)->format('Y-m-d');
            } catch (\Throwable) {
                return null;
            }
        }

        $value = trim((string) $value);
        if ($value === '') {
            return null;
        }

        // Buang bagian jam jika ada, mis. "2026-11-21 00:00:00".
        $value = preg_split('/\s+/', $value)[0];

        if (preg_match('/^(\d{4})[-\/.](\d{1,2})[-\/.](\d{1,2})$/', $value, $m)) {
            [$year, $month, $day] = [(int) $m[1], (int) $m[2], (int) $m[3]];
        } elseif (preg_match('/^(\d{1,2})[-\/.](\d{1,2})[-\/.](\d{4})$/', $value, $m)) {
            [$day, $month, $year] = [(int) $m[1], (int) $m[2], (int) $m[3]];
        } else {
            return null;
        }

        if (!checkdate($month, $day, $year)) {
            return null;
        }

        return sprintf('%04d-%02d-%02d', $year, $month, $day);
    }

    protected function addErrorRow(Row $row, array $data, string $message, int $rowIndex): void
    {
        $this->errorRows[] = [...$this->displayValues($row, $data), $message, $rowIndex];
    }

    protected function displayValues(Row $row, array $data): array
    {
        return array_map(
            fn ($key) => $this->getDisplayValue($row, $data, $key),
            ['tanggal', 'keterangan', 'toko', 'kategori', 'debet', 'kredit']
        );
    }

    /**
     * Tandai baris yang sama persis dengan data jurnal yang sudah tersimpan.
     * Baris kembar di dalam file yang sama tetap diperbolehkan.
     */
    protected function flagDuplicates(): void
    {
        $hashes = array_values(array_unique(array_map(fn ($p) => $p['payload']['row_hash'], $this->pendingRows)));
        $existing = [];
        foreach (array_chunk($hashes, 500) as $chunk) {
            DB::table('jurnal_umum')
                ->whereIn('row_hash', $chunk)
                ->select('row_hash', DB::raw('MAX(import_batch_id) as batch_id'))
                ->groupBy('row_hash')
                ->get()
                ->each(function ($r) use (&$existing) {
                    $existing[$r->row_hash] = $r->batch_id;
                });
        }

        if (empty($existing)) {
            return;
        }

        foreach ($this->pendingRows as $pending) {
            $hash = $pending['payload']['row_hash'];
            if (!array_key_exists($hash, $existing)) {
                continue;
            }
            $batchId = $existing[$hash];
            $source = $batchId ? 'batch #'.str_pad((string) $batchId, 4, '0', STR_PAD_LEFT) : 'data lama';
            $message = "duplikat: baris yang sama sudah ada ({$source})";
            $this->errors[] = "Baris {$pending['row_index']}: {$message}";
            $this->errorRows[] = [...$pending['display'], $message, $pending['row_index']];
            $this->skipped++;
            $this->duplicateCount++;
        }
    }

    protected static function normalizeName(?string $value): string
    {
        $value = trim((string) $value);
        return mb_strtolower($value);
    }


    public static function parseAmount($value): ?float
    {
        if ($value === null || $value === '') return 0.0;
        if (is_int($value) || is_float($value)) return (float) $value;

        $value = str_replace([' ', "\xc2\xa0"], '', (string) $value);
        if ($value === '') return 0.0;
        // Buang awalan "Rp" dan akhiran ",-" / ".-" yang lazim di penulisan Rupiah.
        $value = preg_replace('/^(-?)rp\.?/i', '$1', $value);
        $value = preg_replace('/[,.]-$/', '', $value);
        if ($value === '') return null;

        $hasComma = str_contains($value, ',');
        $hasDot = str_contains($value, '.');

        if ($hasDot && !$hasComma && preg_match('/^-?\d{1,3}(\.\d{3})+$/', $value)) {
            // Format Indonesia dengan pemisah ribuan titik, mis. 1.500.000 atau 1.500.
            $value = str_replace('.', '', $value);
        } elseif ($hasComma && !$hasDot && preg_match('/^-?\d{1,3}(,\d{3})+$/', $value)) {
            // Pemisah ribuan koma, mis. 15,000 atau 1,500,000. Nominal Rupiah tidak pernah
            // punya tiga angka desimal, jadi koma + tepat 3 angka dibaca sebagai ribuan.
            $value = str_replace(',', '', $value);
        } elseif ($hasComma && $hasDot) {
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

                $this->flagDuplicates();
                if ($this->duplicateCount > 0) {
                    return;
                }

                $now = now();
                $records = array_map(function ($pending) use ($now) {
                    return $pending['payload'] + [
                        'import_batch_id' => $this->batchId,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                }, $this->pendingRows);

                foreach (array_chunk($records, 500) as $chunk) {
                    JurnalUmum::insert($chunk);
                }

                $this->created = count($records);
                $this->totalDebet = array_sum(array_column($records, 'debet'));
                $this->totalKredit = array_sum(array_column($records, 'kredit'));
                $dates = array_column($records, 'tanggal');
                $this->tanggalAwal = min($dates);
                $this->tanggalAkhir = max($dates);
            },
        ];
    }
}
