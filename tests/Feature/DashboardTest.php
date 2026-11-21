<?php

namespace Tests\Feature;

use App\Models\AkunBiaya;
use App\Models\Budget;
use App\Models\Division;
use App\Models\JurnalUmum;
use App\Models\SubAkunBiaya;
use App\Models\SubDivision;
use App\Models\User;
use App\Services\CostReport;
use App\Support\Periode;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    private Division $division;
    private AkunBiaya $akun;
    private SubDivision $toko;
    private SubAkunBiaya $kategori;

    protected function setUp(): void
    {
        parent::setUp();
        $this->division = Division::create(['name' => 'Retail']);
        $this->toko = SubDivision::create(['division_id' => $this->division->id, 'name' => 'Toko A']);
        $kas = AkunBiaya::create(['name' => 'Kas']);
        $this->akun = AkunBiaya::create(['name' => 'Operasional']);
        $this->kategori = SubAkunBiaya::create(['akun_biaya_id' => $this->akun->id, 'name' => 'Listrik']);
        $kasSub = SubAkunBiaya::create(['akun_biaya_id' => $kas->id, 'name' => 'Kas Kecil']);

        Budget::create(['division_id' => $this->division->id, 'akun_biaya_id' => $this->akun->id, 'tahun' => 2026, 'bulan' => null, 'amount' => 1_200_000]);
        foreach ([['2026-01-10', 90_000], ['2026-02-10', 60_000], ['2025-01-10', 100_000]] as [$tgl, $kredit]) {
            $this->jurnal($tgl, $kredit);
        }
        // Akun kas tidak dihitung sebagai biaya.
        $this->jurnal('2026-01-15', 999_000, $kasSub->id);
    }

    private function jurnal(?string $tgl, float $kredit, ?int $sub = null): void
    {
        JurnalUmum::create([
            'tanggal' => $tgl, 'keterangan' => 'x', 'sub_divisi_id' => $this->toko->id,
            'sub_akun_biaya_id' => $sub ?? $this->kategori->id, 'debet' => 0, 'kredit' => $kredit,
        ]);
    }

    public function test_matrix_and_positions_follow_periode(): void
    {
        $report = new CostReport(new Periode(2026, 1, 2));
        $m = $report->matrix();

        $this->assertEquals(150_000, $m['total_realisasi']);
        $this->assertEquals(200_000, $m['total_budget']);
        $pos = $report->positions($m)->first();
        $this->assertEquals(75.0, $pos->pct);
        $this->assertSame('ok', CostReport::status($pos->pct, $pos->realisasi));

        $this->assertSame('bad', CostReport::status(null, 10));
        $this->assertSame('warn', CostReport::status(80, 10));
        $this->assertSame('bad', CostReport::status(100.1, 10));

        $monthly = CostReport::monthly(2026);
        $this->assertEquals(90_000, $monthly[1]['realisasi']);
        $this->assertEquals(100_000, $monthly[1]['budget']);
    }

    public function test_dashboard_renders(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('admin.dashboard', ['tahun' => 2026, 'bulan_sampai' => 2]))
            ->assertOk()
            ->assertSee('Januari – Februari 2026')
            ->assertSee('75,0%')
            ->assertSee('vs periode sama 2025')
            ->assertSee('Retail')
            ->assertSee('Belum ada pos yang mendekati');
    }
}
