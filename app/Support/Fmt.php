<?php

namespace App\Support;

/** Format angka untuk tampilan. */
class Fmt
{
    public static function rp($value, int $decimals = 0): string
    {
        return 'Rp '.number_format((float) $value, $decimals, ',', '.');
    }

    /** Ringkas: "Rp 2,68 M", "Rp 179,5 jt", "Rp 850 rb". */
    public static function rpShort($value): string
    {
        $v = (float) $value;
        $abs = abs($v);
        if ($abs >= 1e9) {
            return 'Rp '.number_format($v / 1e9, 2, ',', '.').' M';
        }
        if ($abs >= 1e6) {
            return 'Rp '.number_format($v / 1e6, 1, ',', '.').' jt';
        }
        if ($abs >= 1e3) {
            return 'Rp '.number_format($v / 1e3, 0, ',', '.').' rb';
        }
        return 'Rp '.number_format($v, 0, ',', '.');
    }

    /** Angka dalam satuan tertentu tanpa "Rp": penuh | ribu | juta. */
    public static function unit($value, string $satuan = 'juta'): string
    {
        $v = (float) $value;
        return match ($satuan) {
            'penuh' => number_format($v, 0, ',', '.'),
            'ribu' => number_format($v / 1e3, 0, ',', '.'),
            default => number_format($v / 1e6, 1, ',', '.'),
        };
    }

    public static function pct(?float $pct): string
    {
        return $pct === null ? '–' : number_format($pct, 0, ',', '.').'%';
    }

    public static function color(string $status): string
    {
        return ['ok' => 'var(--ui-ok)', 'warn' => 'var(--ui-warn)', 'bad' => 'var(--ui-bad)'][$status] ?? 'var(--ui-ink3)';
    }
}
