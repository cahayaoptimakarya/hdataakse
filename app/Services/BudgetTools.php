<?php

namespace App\Services;

use App\Imports\JurnalUmumImport;
use App\Support\Audit;
use Illuminate\Support\Facades\DB;

/**
 * Import budget dari Excel dan salin budget antar tahun.
 * Budget divisi disimpan di `budgets`, budget toko di `sub_divisi_budgets`.
 * Dalam satu tahun, satu pasangan (divisi/toko, akun) memakai budget tahunan ATAU bulanan, tidak keduanya.
 */
class BudgetTools
{
    public const HEADERS = ['tahun', 'bulan', 'divisi', 'toko', 'akun', 'jumlah'];

    /**
     * @param  array<int, array<string, mixed>>  $rows  baris dengan heading (kunci huruf kecil)
     * @return array{ok: bool, errors: array, created: int, updated: int}
     */
    public static function import(array $rows): array
    {
        $norm = fn ($v) => mb_strtolower(trim(preg_replace('/\s+/', ' ', (string) $v)));
        $divisions = DB::table('divisions')->get(['id', 'name'])->keyBy(fn ($d) => $norm($d->name));
        $tokos = DB::table('sub_divisions')->get(['id', 'name', 'division_id'])->keyBy(fn ($d) => $norm($d->name));
        $akuns = DB::table('akun_biaya')->get(['id', 'name'])->keyBy(fn ($d) => $norm($d->name));

        $errors = [];
        $items = [];   // key => [table, keyCol, keyId, akunId, tahun, bulan, amount, line]
        foreach ($rows as $i => $row) {
            $line = $i + 2;
            $row = array_change_key_case($row);
            if (collect(self::HEADERS)->every(fn ($h) => trim((string) ($row[$h] ?? '')) === '')) {
                continue;
            }
            $err = [];
            $tahun = (int) ($row['tahun'] ?? 0);
            if ($tahun < 2000 || $tahun > 2100) {
                $err[] = 'tahun tidak valid';
            }
            $bulanRaw = trim((string) ($row['bulan'] ?? ''));
            $bulan = $bulanRaw === '' ? null : self::parseBulan($bulanRaw);
            if ($bulanRaw !== '' && $bulan === null) {
                $err[] = 'bulan harus 1–12 atau nama bulan (kosongkan untuk budget tahunan)';
            }
            $akun = $akuns[$norm($row['akun'] ?? '')] ?? null;
            if (!$akun) {
                $err[] = 'akun "'.($row['akun'] ?? '').'" tidak ditemukan';
            }
            $tokoName = trim((string) ($row['toko'] ?? ''));
            $divName = trim((string) ($row['divisi'] ?? ''));
            if ($tokoName !== '') {
                $toko = $tokos[$norm($tokoName)] ?? null;
                if (!$toko) {
                    $err[] = 'toko "'.$tokoName.'" tidak ditemukan';
                } elseif ($divName !== '' && ($div = $divisions[$norm($divName)] ?? null) && $div->id !== $toko->division_id) {
                    $err[] = 'toko "'.$tokoName.'" bukan bagian dari divisi "'.$divName.'"';
                }
                $target = ['sub_divisi_budgets', 'sub_divisi_id', $toko->id ?? null];
            } else {
                $div = $divisions[$norm($divName)] ?? null;
                if (!$div) {
                    $err[] = $divName === '' ? 'isi divisi atau toko' : 'divisi "'.$divName.'" tidak ditemukan';
                }
                $target = ['budgets', 'division_id', $div->id ?? null];
            }
            $amount = JurnalUmumImport::parseAmount($row['jumlah'] ?? null);
            if ($amount === null || $amount < 0) {
                $err[] = 'jumlah tidak valid';
            }

            if ($err) {
                $errors[] = ['baris' => $line, 'pesan' => implode('; ', $err)];
                continue;
            }
            $key = implode('|', [$target[0], $target[2], $akun->id, $tahun, $bulan ?? 0]);
            if (isset($items[$key])) {
                $errors[] = ['baris' => $line, 'pesan' => 'dobel dengan baris '.$items[$key][7]];
                continue;
            }
            $items[$key] = [$target[0], $target[1], $target[2], $akun->id, $tahun, $bulan, $amount, $line];
        }

        // Tahunan vs bulanan tidak boleh bercampur, baik di file maupun dengan data yang sudah ada.
        $groups = collect($items)->groupBy(fn ($it) => implode('|', [$it[0], $it[2], $it[3], $it[4]]));
        foreach ($groups as $group) {
            [$table, $keyCol, $keyId, $akunId, $tahun] = $group->first();
            $fileAnnual = $group->contains(fn ($it) => $it[5] === null);
            $fileMonthly = $group->contains(fn ($it) => $it[5] !== null);
            $db = DB::table($table)->where($keyCol, $keyId)->where('akun_biaya_id', $akunId)->where('tahun', $tahun)->pluck('bulan');
            $dbAnnual = $db->contains(fn ($b) => $b === null);
            $dbMonthly = $db->contains(fn ($b) => $b !== null);
            if (($fileAnnual && $fileMonthly) || ($fileAnnual && $dbMonthly) || ($fileMonthly && $dbAnnual)) {
                $errors[] = ['baris' => $group->first()[7], 'pesan' => 'budget tahunan dan bulanan tidak boleh dicampur untuk pasangan yang sama di tahun '.$tahun];
            }
        }

        if ($errors) {
            usort($errors, fn ($a, $b) => $a['baris'] <=> $b['baris']);
            return ['ok' => false, 'errors' => $errors, 'created' => 0, 'updated' => 0];
        }

        $created = 0;
        $updated = 0;
        DB::transaction(function () use ($items, &$created, &$updated) {
            $now = now();
            foreach ($items as [$table, $keyCol, $keyId, $akunId, $tahun, $bulan, $amount]) {
                $q = DB::table($table)->where($keyCol, $keyId)->where('akun_biaya_id', $akunId)->where('tahun', $tahun)
                    ->where(fn ($w) => $bulan === null ? $w->whereNull('bulan') : $w->where('bulan', $bulan));
                if ($q->exists()) {
                    $q->update(['amount' => $amount, 'updated_at' => $now]);
                    $updated++;
                } else {
                    DB::table($table)->insert([$keyCol => $keyId, 'akun_biaya_id' => $akunId, 'tahun' => $tahun, 'bulan' => $bulan,
                        'amount' => $amount, 'created_at' => $now, 'updated_at' => $now]);
                    $created++;
                }
            }
            Audit::log('import', 'Budget', null, "Import budget dari Excel: {$created} baru, {$updated} diubah");
        });

        return ['ok' => true, 'errors' => [], 'created' => $created, 'updated' => $updated];
    }

    private static function parseBulan(string $v): ?int
    {
        if (ctype_digit($v)) {
            $n = (int) $v;
            return $n >= 1 && $n <= 12 ? $n : null;
        }
        $v = mb_strtolower($v);
        foreach (\App\Support\Periode::NAMA_BULAN as $no => $nama) {
            if ($v === mb_strtolower($nama) || $v === mb_strtolower(mb_substr($nama, 0, 3))) {
                return $no;
            }
        }
        return null;
    }

    /**
     * Salin budget satu tahun ke tahun lain, dengan penyesuaian persen.
     * Pasangan (divisi/toko, akun) yang sudah punya budget di tahun tujuan dilewati, kecuali $timpa.
     *
     * @return array{copied: int, skipped: int, replaced: int}
     */
    public static function copy(int $dari, int $ke, float $persen, bool $timpa, bool $termasukToko, int $bulatkan = 1000): array
    {
        $stats = ['copied' => 0, 'skipped' => 0, 'replaced' => 0];
        $tables = ['budgets' => 'division_id'] + ($termasukToko ? ['sub_divisi_budgets' => 'sub_divisi_id'] : []);

        DB::transaction(function () use ($tables, $dari, $ke, $persen, $timpa, $bulatkan, &$stats) {
            $now = now();
            foreach ($tables as $table => $keyCol) {
                $source = DB::table($table)->where('tahun', $dari)->get([$keyCol, 'akun_biaya_id', 'bulan', 'amount'])
                    ->groupBy(fn ($r) => $r->{$keyCol}.'|'.$r->akun_biaya_id);
                foreach ($source as $rows) {
                    $first = $rows->first();
                    $target = DB::table($table)->where($keyCol, $first->{$keyCol})->where('akun_biaya_id', $first->akun_biaya_id)->where('tahun', $ke);
                    if ((clone $target)->exists()) {
                        if (!$timpa) {
                            $stats['skipped'] += $rows->count();
                            continue;
                        }
                        (clone $target)->delete();
                        $stats['replaced'] += $rows->count();
                    }
                    foreach ($rows as $r) {
                        $amount = (float) $r->amount * (1 + $persen / 100);
                        $amount = $bulatkan > 1 ? round($amount / $bulatkan) * $bulatkan : round($amount);
                        DB::table($table)->insert([$keyCol => $r->{$keyCol}, 'akun_biaya_id' => $r->akun_biaya_id, 'tahun' => $ke,
                            'bulan' => $r->bulan, 'amount' => $amount, 'created_at' => $now, 'updated_at' => $now]);
                        $stats['copied']++;
                    }
                }
            }
            $p = rtrim(rtrim(number_format($persen, 2, ',', '.'), '0'), ',');
            Audit::log('salin', 'Budget', null, "Salin budget {$dari} ke {$ke} ({$p}%): {$stats['copied']} disalin, {$stats['skipped']} dilewati"
                .($stats['replaced'] ? ", {$stats['replaced']} ditimpa" : ''));
        });

        return $stats;
    }
}
