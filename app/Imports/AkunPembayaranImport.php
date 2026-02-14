<?php

namespace App\Imports;

use App\Models\AkunBiaya;
use App\Models\SubAkunBiaya;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\ToCollection;

class AkunPembayaranImport implements ToCollection, SkipsEmptyRows
{
    public int $createdAkun = 0;
    public int $createdSubAkun = 0;
    public int $skippedAkun = 0;
    public int $skippedSubAkun = 0;
    public int $skippedRows = 0;
    public array $skippedDetails = [];

    private array $akunMap = [];
    private array $subAkunMap = [];

    public function collection(Collection $rows)
    {
        $this->bootstrapMaps();

        $rowIndex = 0;
        foreach ($rows as $row) {
            $rowIndex++;

            $subName = trim((string) ($row[0] ?? ''));
            $akunName = trim((string) ($row[1] ?? ''));

            if ($rowIndex === 1 && $this->looksLikeHeader($subName, $akunName)) {
                continue;
            }

            if ($subName === '' && $akunName === '') {
                continue;
            }

            if ($subName === '' || $akunName === '') {
                $this->recordSkip($rowIndex, $subName, $akunName, ['Sub akun atau akun pembayaran kosong']);
                continue;
            }

            $akunKey = self::normalize($akunName);
            $akunId = $this->akunMap[$akunKey] ?? null;
            $akunSkipped = false;
            if (!$akunId) {
                $akun = AkunBiaya::create(['name' => $akunName]);
                $akunId = $akun->id;
                $this->akunMap[$akunKey] = $akunId;
                $this->createdAkun++;
            } else {
                $this->skippedAkun++;
                $akunSkipped = true;
            }

            $subKey = $akunId.'|'.self::normalize($subName);
            $subSkipped = false;
            if (isset($this->subAkunMap[$subKey])) {
                $this->skippedSubAkun++;
                $subSkipped = true;
            } else {
                $sub = SubAkunBiaya::create([
                    'akun_biaya_id' => $akunId,
                    'name' => $subName,
                ]);
                $this->subAkunMap[$subKey] = $sub->id;
                $this->createdSubAkun++;
            }

            $skipReasons = [];
            if ($akunSkipped) {
                $skipReasons[] = 'Akun pembayaran sudah ada';
            }
            if ($subSkipped) {
                $skipReasons[] = 'Sub akun pembayaran sudah ada';
            }
            if (!empty($skipReasons)) {
                $this->recordSkip($rowIndex, $subName, $akunName, $skipReasons);
            }
        }
    }

    protected function bootstrapMaps(): void
    {
        $this->akunMap = AkunBiaya::query()
            ->get(['id', 'name'])
            ->mapWithKeys(fn ($a) => [self::normalize($a->name) => $a->id])
            ->toArray();

        $this->subAkunMap = SubAkunBiaya::query()
            ->get(['id', 'name', 'akun_biaya_id'])
            ->mapWithKeys(fn ($s) => [$s->akun_biaya_id.'|'.self::normalize($s->name) => $s->id])
            ->toArray();
    }

    protected function looksLikeHeader(string $subName, string $akunName): bool
    {
        $sub = self::normalize($subName);
        $akun = self::normalize($akunName);

        return str_contains($sub, 'sub') && str_contains($sub, 'akun')
            && str_contains($akun, 'akun');
    }

    protected static function normalize(string $value): string
    {
        return mb_strtolower(trim($value));
    }

    protected function recordSkip(int $rowIndex, string $subName, string $akunName, array $reasons): void
    {
        $this->skippedRows++;
        $this->skippedDetails[] = [
            'row' => $rowIndex,
            'sub_akun' => $subName,
            'akun' => $akunName,
            'reason' => implode(' & ', $reasons),
        ];
    }
}
