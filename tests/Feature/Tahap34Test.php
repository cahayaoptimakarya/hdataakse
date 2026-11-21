<?php

namespace Tests\Feature;

use App\Mail\BudgetAlertMail;
use App\Models\AkunBiaya;
use App\Models\AuditLog;
use App\Models\Budget;
use App\Models\Division;
use App\Models\JurnalImportBatch;
use App\Models\JurnalUmum;
use App\Models\SubAkunBiaya;
use App\Models\SubDivision;
use App\Models\User;
use App\Services\BudgetTools;
use App\Services\CostReport;
use App\Support\Periode;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Tests\TestCase;

/** Fitur tahap 3–4: import/salin budget, akun dikecualikan, log aktivitas, pratinjau import, peringatan email. */
class Tahap34Test extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Division $division;
    private SubDivision $toko;
    private AkunBiaya $akun;
    private SubAkunBiaya $kategori;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
        $this->user = User::factory()->create();
        $this->division = Division::create(['name' => 'Retail']);
        $this->toko = SubDivision::create(['division_id' => $this->division->id, 'name' => 'Toko A']);
        $this->akun = AkunBiaya::create(['name' => 'Operasional']);
        $this->kategori = SubAkunBiaya::create(['akun_biaya_id' => $this->akun->id, 'name' => 'Listrik']);
    }

    private function jurnal(string $tgl, float $kredit, ?int $sub = null): void
    {
        JurnalUmum::create([
            'tanggal' => $tgl, 'keterangan' => 'x', 'sub_divisi_id' => $this->toko->id,
            'sub_akun_biaya_id' => $sub ?? $this->kategori->id, 'debet' => 0, 'kredit' => $kredit,
        ]);
    }

    public function test_budget_import_saves_rows_and_logs(): void
    {
        $res = BudgetTools::import([
            ['tahun' => 2027, 'bulan' => '', 'divisi' => 'Retail', 'toko' => '', 'akun' => 'operasional', 'jumlah' => '1.200.000'],
            ['tahun' => 2027, 'bulan' => 'Maret', 'divisi' => 'Retail', 'toko' => 'Toko A', 'akun' => 'Operasional', 'jumlah' => 50000],
        ]);

        $this->assertTrue($res['ok'], json_encode($res['errors']));
        $this->assertEquals(1_200_000, Budget::where('tahun', 2027)->whereNull('bulan')->value('amount'));
        $this->assertEquals(50_000, DB::table('sub_divisi_budgets')->where('tahun', 2027)->where('bulan', 3)->value('amount'));
        $this->assertTrue(AuditLog::where('action', 'import')->exists());
    }

    public function test_budget_import_with_errors_saves_nothing(): void
    {
        $res = BudgetTools::import([
            ['tahun' => 2027, 'bulan' => '', 'divisi' => 'Retail', 'toko' => '', 'akun' => 'Operasional', 'jumlah' => 100],
            ['tahun' => 2027, 'bulan' => '', 'divisi' => 'Tidak Ada', 'toko' => '', 'akun' => 'Operasional', 'jumlah' => 100],
        ]);
        $this->assertFalse($res['ok']);
        $this->assertNotEmpty($res['errors']);
        $this->assertSame(0, Budget::count());

        // Tahunan dan bulanan untuk pasangan yang sama tidak boleh dicampur.
        $res = BudgetTools::import([
            ['tahun' => 2027, 'bulan' => '', 'divisi' => 'Retail', 'toko' => '', 'akun' => 'Operasional', 'jumlah' => 100],
            ['tahun' => 2027, 'bulan' => 2, 'divisi' => 'Retail', 'toko' => '', 'akun' => 'Operasional', 'jumlah' => 100],
        ]);
        $this->assertFalse($res['ok']);
        $this->assertSame(0, Budget::count());
    }

    public function test_budget_import_endpoint_and_template(): void
    {
        $this->actingAs($this->user)->get(route('admin.keuangan.budget.template'))->assertOk();

        $sheet = (new Spreadsheet())->getActiveSheet();
        $sheet->fromArray([BudgetTools::HEADERS, [2027, null, 'Retail', null, 'Operasional', 300000]], null, 'A1');
        $path = tempnam(sys_get_temp_dir(), 'budget').'.xlsx';
        (new Xlsx($sheet->getParent()))->save($path);

        $this->actingAs($this->user)
            ->postJson(route('admin.keuangan.budget.import'), ['file' => new UploadedFile($path, 'budget.xlsx', null, null, true)])
            ->assertOk();
        $this->assertEquals(300_000, Budget::where('tahun', 2027)->value('amount'));
    }

    public function test_copy_budget_skips_or_replaces(): void
    {
        Budget::create(['division_id' => $this->division->id, 'akun_biaya_id' => $this->akun->id, 'tahun' => 2026, 'bulan' => null, 'amount' => 1_000_000]);

        $this->actingAs($this->user)->postJson(route('admin.keuangan.budget.copy'), ['dari_tahun' => 2026, 'ke_tahun' => 2027, 'persen' => 10])
            ->assertOk();
        $this->assertEquals(1_100_000, Budget::where('tahun', 2027)->value('amount'));

        // Tanpa "timpa", budget yang sudah ada tidak diubah.
        $stats = BudgetTools::copy(2026, 2027, 20, false, true);
        $this->assertSame(1, $stats['skipped']);
        $this->assertEquals(1_100_000, Budget::where('tahun', 2027)->value('amount'));

        $stats = BudgetTools::copy(2026, 2027, 20, true, true);
        $this->assertSame(1, $stats['replaced']);
        $this->assertEquals(1_200_000, Budget::where('tahun', 2027)->value('amount'));
        $this->assertTrue(AuditLog::where('action', 'salin')->exists());
    }

    public function test_excluded_akun_is_not_counted_as_cost(): void
    {
        $lain = AkunBiaya::create(['name' => 'Mutasi Bank']);
        $sub = SubAkunBiaya::create(['akun_biaya_id' => $lain->id, 'name' => 'Transfer']);
        $this->jurnal('2026-01-10', 100_000);
        $this->jurnal('2026-01-11', 500_000, $sub->id);

        $p = new Periode(2026, 1, 1);
        $this->assertEquals(600_000, (new CostReport($p))->matrix()['total_realisasi']);

        $this->actingAs($this->user)->putJson(route('admin.keuangan.akun-pembayaran.update', $lain), ['name' => 'Mutasi Bank', 'dikecualikan' => 1])
            ->assertOk();
        $this->assertTrue($lain->fresh()->dikecualikan);
        $this->assertEquals(100_000, (new CostReport($p))->matrix()['total_realisasi']);
    }

    public function test_audit_log_records_changes_and_page_renders(): void
    {
        $this->actingAs($this->user);
        $b = Budget::create(['division_id' => $this->division->id, 'akun_biaya_id' => $this->akun->id, 'tahun' => 2026, 'bulan' => null, 'amount' => 100]);
        $b->update(['amount' => 250]);

        $log = AuditLog::where('action', 'ubah')->latest('id')->first();
        $this->assertNotNull($log);
        $this->assertSame($this->user->id, $log->user_id);
        $this->assertEquals(250, $log->changes['amount'][1]);

        $this->get(route('admin.masterdata.audit.index'))->assertOk()->assertSee('Operasional');
    }

    public function test_jurnal_import_preview_saves_nothing(): void
    {
        $sheet = (new Spreadsheet())->getActiveSheet();
        $sheet->fromArray(['tanggal', 'keterangan', 'toko', 'kategori', 'debet', 'kredit'], null, 'A1');
        $sheet->setCellValueExplicit('A2', '10/01/2026', DataType::TYPE_STRING);
        $sheet->fromArray(['Bayar', 'Toko A', 'Listrik', 0, 75000], null, 'B2');
        $path = tempnam(sys_get_temp_dir(), 'jurnal').'.xlsx';
        (new Xlsx($sheet->getParent()))->save($path);
        $file = fn () => new UploadedFile($path, 'jurnal.xlsx', null, null, true);

        $this->actingAs($this->user)->postJson(route('admin.keuangan.jurnal-umum.store'), ['file' => $file(), 'preview' => 1])
            ->assertOk()->assertJson(['preview' => true, 'created' => 1, 'total_kredit' => 75000]);
        $this->assertSame(0, JurnalUmum::count());
        $this->assertSame(0, JurnalImportBatch::count());

        // Setelah pratinjau, file yang sama tetap bisa disimpan.
        $this->actingAs($this->user)->postJson(route('admin.keuangan.jurnal-umum.store'), ['file' => $file()])->assertOk();
        $this->assertSame(1, JurnalUmum::count());
    }

    public function test_budget_warning_email_is_sent_once(): void
    {
        Mail::fake();
        Carbon::setTestNow('2026-03-20 07:00:00');
        config(['services.budget_alert.emails' => 'keuangan@example.com']);
        Budget::create(['division_id' => $this->division->id, 'akun_biaya_id' => $this->akun->id, 'tahun' => 2026, 'bulan' => null, 'amount' => 1_200_000]);
        $this->jurnal('2026-02-10', 280_000); // 93% dari budget Jan–Mar (300.000)

        $this->artisan('budget:peringatan')->assertSuccessful();
        Mail::assertSent(BudgetAlertMail::class, fn ($m) => $m->hasTo('keuangan@example.com'));
        $this->assertSame(1, DB::table('budget_alerts')->where('level', 80)->count());

        $this->artisan('budget:peringatan')->expectsOutputToContain('Tidak ada pos baru')->assertSuccessful();
        Mail::assertSentCount(1);

        // Lewat 100% memicu email baru.
        $this->jurnal('2026-03-01', 50_000);
        $this->artisan('budget:peringatan')->assertSuccessful();
        Mail::assertSentCount(2);
        Carbon::setTestNow();
    }

    public function test_dashboard_and_tren_show_last_year(): void
    {
        Budget::create(['division_id' => $this->division->id, 'akun_biaya_id' => $this->akun->id, 'tahun' => 2026, 'bulan' => null, 'amount' => 1_200_000]);
        $this->jurnal('2026-01-10', 90_000);
        $this->jurnal('2025-01-10', 100_000);

        $this->assertEquals(100_000, CostReport::monthlyRealisasi(2025)[1]);
        $this->actingAs($this->user)->get(route('admin.dashboard', ['tahun' => 2026, 'bulan_sampai' => 2]))
            ->assertOk()->assertSee('class="ly"', false);
        $this->actingAs($this->user)->get(route('admin.keuangan.laporan.index', ['tahun' => 2026, 'tab' => 'tren', 'satuan' => 'penuh']))
            ->assertOk()->assertSee('▼ 10,0%');
    }
}
