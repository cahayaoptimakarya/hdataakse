<?php

namespace App\Console\Commands;

use App\Mail\BudgetAlertMail;
use App\Services\BudgetAlerts;
use App\Support\Audit;
use App\Support\Periode;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class KirimPeringatanBudget extends Command
{
    protected $signature = 'budget:peringatan {--dry-run : Tampilkan saja, jangan kirim email}';

    protected $description = 'Kirim email untuk pos budget yang baru mencapai 80% atau lewat 100% (tahun berjalan)';

    public function handle(): int
    {
        $tahun = (int) now()->year;
        $bulan = (int) now()->month;
        $items = BudgetAlerts::pending($tahun, $bulan);
        $periode = new Periode($tahun, 1, $bulan);

        if ($items->isEmpty()) {
            $this->info('Tidak ada pos baru yang perlu diperingatkan.');
            return self::SUCCESS;
        }
        foreach ($items as $p) {
            $this->line(sprintf('%-30s %-25s %6.1f%%', $p->akun, $p->division, $p->pct));
        }
        if ($this->option('dry-run')) {
            return self::SUCCESS;
        }

        $to = BudgetAlerts::recipients();
        if (!$to) {
            $this->warn('Tidak ada penerima. Isi BUDGET_ALERT_EMAILS di .env atau beri peran admin ke pengguna.');
            return self::FAILURE;
        }
        $url = route('admin.keuangan.laporan.index', $periode->toQuery() + ['tab' => 'lewat']);
        Mail::to($to)->send(new BudgetAlertMail($items, $periode->label(), $url));
        BudgetAlerts::markSent($tahun, $items);
        Audit::log('kirim', 'Peringatan budget', null, 'Email peringatan '.$items->count().' pos dikirim ke '.implode(', ', $to));
        $this->info('Terkirim ke: '.implode(', ', $to));

        return self::SUCCESS;
    }
}
