<?php

namespace App\Services;

use App\Support\Periode;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Cari pos divisi × akun yang serapannya baru mencapai 80% atau lewat 100% (tahun berjalan s/d bulan ini),
 * yang belum pernah diperingatkan untuk level tersebut.
 */
class BudgetAlerts
{
    public const LEVELS = [100, 80];

    /** @return Collection<int, object{akun_id,akun,division_id,division,realisasi,budget,pct,level}> */
    public static function pending(int $tahun, int $bulanSampai): Collection
    {
        $report = new CostReport(new Periode($tahun, 1, $bulanSampai));
        $sent = DB::table('budget_alerts')->where('tahun', $tahun)->get()
            ->map(fn ($r) => $r->division_id.'|'.$r->akun_biaya_id.'|'.$r->level)->flip();

        return $report->positions($report->matrix())
            ->filter(fn ($p) => $p->budget > 0 && $p->pct !== null && $p->pct >= 80)
            ->map(function ($p) {
                $p->level = $p->pct > 100 ? 100 : 80;
                return $p;
            })
            ->reject(fn ($p) => isset($sent[$p->division_id.'|'.$p->akun_id.'|'.$p->level]))
            ->values();
    }

    public static function markSent(int $tahun, Collection $items): void
    {
        $now = now();
        foreach ($items as $p) {
            // Level 100 juga menandai level 80, agar tidak dikirim mundur.
            foreach (array_filter(self::LEVELS, fn ($l) => $l <= $p->level) as $level) {
                DB::table('budget_alerts')->updateOrInsert(
                    ['tahun' => $tahun, 'division_id' => $p->division_id, 'akun_biaya_id' => $p->akun_id, 'level' => $level],
                    ['pct' => round($p->pct, 2), 'sent_at' => $now]
                );
            }
        }
    }

    /** Alamat penerima: BUDGET_ALERT_EMAILS (dipisah koma), atau semua pengguna berperan admin. */
    public static function recipients(): array
    {
        $env = array_filter(array_map('trim', explode(',', (string) config('services.budget_alert.emails'))));
        if ($env) {
            return array_values($env);
        }
        return DB::table('users')
            ->join('role_user', 'role_user.user_id', '=', 'users.id')
            ->join('roles', 'roles.id', '=', 'role_user.role_id')
            ->where('roles.slug', 'admin')
            ->distinct()->pluck('users.email')->filter()->values()->all();
    }
}
