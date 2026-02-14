<?php

namespace App\Imports;

use App\Models\Division;
use App\Models\SubDivision;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\ToCollection;

class SubDivisiDivisiImport implements ToCollection, SkipsEmptyRows
{
    public int $createdDivisi = 0;
    public int $createdSubDivisi = 0;
    public int $skippedDivisi = 0;
    public int $skippedSubDivisi = 0;
    public int $skippedRows = 0;
    public array $skippedDetails = [];

    private array $divisionMap = [];
    private array $subDivisionMap = [];

    public function collection(Collection $rows)
    {
        $this->bootstrapMaps();

        $rowIndex = 0;
        foreach ($rows as $row) {
            $rowIndex++;

            $subName = trim((string) ($row[0] ?? ''));
            $divName = trim((string) ($row[1] ?? ''));

            if ($rowIndex === 1 && $this->looksLikeHeader($subName, $divName)) {
                continue;
            }

            if ($subName === '' && $divName === '') {
                continue;
            }

            if ($subName === '' || $divName === '') {
                $this->recordSkip($rowIndex, $subName, $divName, ['Sub divisi atau divisi kosong']);
                continue;
            }

            $divKey = self::normalize($divName);
            $divisionId = $this->divisionMap[$divKey] ?? null;
            $divisionSkipped = false;
            if (!$divisionId) {
                $division = Division::create(['name' => $divName]);
                $divisionId = $division->id;
                $this->divisionMap[$divKey] = $divisionId;
                $this->createdDivisi++;
            } else {
                $this->skippedDivisi++;
                $divisionSkipped = true;
            }

            $subKey = $divisionId.'|'.self::normalize($subName);
            $subSkipped = false;
            if (isset($this->subDivisionMap[$subKey])) {
                $this->skippedSubDivisi++;
                $subSkipped = true;
            } else {
                $sub = SubDivision::create([
                    'division_id' => $divisionId,
                    'name' => $subName,
                ]);
                $this->subDivisionMap[$subKey] = $sub->id;
                $this->createdSubDivisi++;
            }

            $skipReasons = [];
            if ($divisionSkipped) {
                $skipReasons[] = 'Divisi sudah ada';
            }
            if ($subSkipped) {
                $skipReasons[] = 'Sub divisi sudah ada';
            }
            if (!empty($skipReasons)) {
                $this->recordSkip($rowIndex, $subName, $divName, $skipReasons);
            }
        }
    }

    protected function bootstrapMaps(): void
    {
        $this->divisionMap = Division::query()
            ->get(['id', 'name'])
            ->mapWithKeys(fn ($d) => [self::normalize($d->name) => $d->id])
            ->toArray();

        $this->subDivisionMap = SubDivision::query()
            ->get(['id', 'name', 'division_id'])
            ->mapWithKeys(fn ($s) => [$s->division_id.'|'.self::normalize($s->name) => $s->id])
            ->toArray();
    }

    protected function looksLikeHeader(string $subName, string $divName): bool
    {
        $sub = self::normalize($subName);
        $div = self::normalize($divName);

        return str_contains($sub, 'sub') && str_contains($sub, 'divisi')
            && str_contains($div, 'divisi');
    }

    protected static function normalize(string $value): string
    {
        return mb_strtolower(trim($value));
    }

    protected function recordSkip(int $rowIndex, string $subName, string $divName, array $reasons): void
    {
        $this->skippedRows++;
        $this->skippedDetails[] = [
            'row' => $rowIndex,
            'sub_divisi' => $subName,
            'divisi' => $divName,
            'reason' => implode(' & ', $reasons),
        ];
    }
}
