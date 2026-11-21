<?php

namespace App\Support;

/**
 * Sidik jari satu baris jurnal untuk mendeteksi baris yang sama persis
 * (tanggal, toko, kategori, debet, kredit, keterangan).
 */
class JurnalRowHash
{
    public static function make($tanggal, $subDivisiId, $subAkunId, $debet, $kredit, $keterangan): string
    {
        $tanggal = $tanggal ? substr((string) $tanggal, 0, 10) : '';
        $keterangan = mb_strtolower(preg_replace('/\s+/', ' ', trim((string) $keterangan)));

        return hash('sha256', implode('|', [
            $tanggal,
            (int) $subDivisiId,
            (int) $subAkunId,
            number_format((float) $debet, 2, '.', ''),
            number_format((float) $kredit, 2, '.', ''),
            $keterangan,
        ]));
    }
}
