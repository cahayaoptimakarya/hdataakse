<?php

namespace App\Support;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Periode laporan: satu tahun dengan rentang bulan, atau "semua periode".
 *
 * Aturan budget untuk periode:
 * - budget bulanan (bulan 1..12) dihitung bila bulannya ada dalam rentang;
 * - budget tahunan (bulan NULL) dibagi rata: amount × jumlah bulan / 12;
 * - "semua periode" menjumlahkan seluruh budget tanpa pembagian.
 */
class Periode
{
    public const NAMA_BULAN = [
        1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April', 5 => 'Mei', 6 => 'Juni',
        7 => 'Juli', 8 => 'Agustus', 9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember',
    ];

    public function __construct(
        public readonly ?int $tahun,
        public readonly int $bulanDari = 1,
        public readonly int $bulanSampai = 12,
    ) {
    }

    public static function fromRequest(Request $request): self
    {
        $tahunInput = $request->input('tahun');
        if ($tahunInput === 'semua') {
            return new self(null);
        }

        $tahun = (int) $tahunInput;
        if ($tahun < 2000 || $tahun > 2100) {
            $tahun = (int) now()->year;
        }

        $dari = min(12, max(1, (int) $request->input('bulan_dari', 1)));
        $sampai = min(12, max(1, (int) $request->input('bulan_sampai', 12)));
        if ($sampai < $dari) {
            [$dari, $sampai] = [$sampai, $dari];
        }

        return new self($tahun, $dari, $sampai);
    }

    public function isSemua(): bool
    {
        return $this->tahun === null;
    }

    public function jumlahBulan(): int
    {
        return $this->isSemua() ? 12 : ($this->bulanSampai - $this->bulanDari + 1);
    }

    public function tanggalAwal(): ?string
    {
        return $this->isSemua() ? null : sprintf('%04d-%02d-01', $this->tahun, $this->bulanDari);
    }

    public function tanggalAkhir(): ?string
    {
        if ($this->isSemua()) {
            return null;
        }
        return Carbon::create($this->tahun, $this->bulanSampai, 1)->endOfMonth()->format('Y-m-d');
    }

    public function label(): string
    {
        if ($this->isSemua()) {
            return 'Semua periode';
        }
        if ($this->bulanDari === 1 && $this->bulanSampai === 12) {
            return 'Tahun '.$this->tahun;
        }
        $dari = self::NAMA_BULAN[$this->bulanDari];
        if ($this->bulanDari === $this->bulanSampai) {
            return $dari.' '.$this->tahun;
        }
        return $dari.' – '.self::NAMA_BULAN[$this->bulanSampai].' '.$this->tahun;
    }

    /** Parameter query untuk dipakai ulang di link/form. */
    public function toQuery(): array
    {
        if ($this->isSemua()) {
            return ['tahun' => 'semua'];
        }
        return ['tahun' => $this->tahun, 'bulan_dari' => $this->bulanDari, 'bulan_sampai' => $this->bulanSampai];
    }

    /** Batasi query jurnal (kolom tanggal) ke periode ini. */
    public function applyToJurnal(Builder $query, string $column = 'ju.tanggal'): Builder
    {
        if (!$this->isSemua()) {
            $query->whereBetween($column, [$this->tanggalAwal(), $this->tanggalAkhir()]);
        }
        return $query;
    }

    /**
     * Hitung budget untuk periode ini.
     *
     * @param  string  $table      budgets | sub_divisi_budgets
     * @param  string  $keyColumn  division_id | sub_divisi_id
     * @return array<int, array<int, float>>  [akun_biaya_id][keyColumn] => amount
     */
    public function budgetMap(string $table, string $keyColumn, array $keyIds, array $akunIds): array
    {
        if (empty($keyIds) || empty($akunIds)) {
            return [];
        }

        $query = DB::table($table)
            ->whereIn($keyColumn, $keyIds)
            ->whereIn('akun_biaya_id', $akunIds);

        if (!$this->isSemua()) {
            $query->where('tahun', $this->tahun)
                ->where(function ($q) {
                    $q->whereNull('bulan')
                        ->orWhereBetween('bulan', [$this->bulanDari, $this->bulanSampai]);
                });
        }

        $map = [];
        foreach ($query->get([$keyColumn, 'akun_biaya_id', 'bulan', 'amount']) as $row) {
            $amount = (float) $row->amount;
            if (!$this->isSemua() && $row->bulan === null) {
                $amount = $amount * $this->jumlahBulan() / 12;
            }
            $map[$row->akun_biaya_id][$row->{$keyColumn}] = ($map[$row->akun_biaya_id][$row->{$keyColumn}] ?? 0) + $amount;
        }

        return $map;
    }

    /** Tahun yang tersedia untuk dipilih (dari budget dan jurnal), plus tahun berjalan. */
    public static function tahunOptions(): array
    {
        $years = collect([(int) now()->year]);
        $years = $years->merge(DB::table('budgets')->whereNotNull('tahun')->distinct()->pluck('tahun'));
        $range = DB::table('jurnal_umum')->selectRaw('MIN(tanggal) as awal, MAX(tanggal) as akhir')->first();
        if ($range && $range->awal) {
            $years = $years->merge(range((int) substr($range->awal, 0, 4), (int) substr($range->akhir, 0, 4)));
        }
        return $years->map(fn ($y) => (int) $y)->filter()->unique()->sortDesc()->values()->all();
    }
}
