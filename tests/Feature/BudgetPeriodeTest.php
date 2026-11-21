<?php

namespace Tests\Feature;

use App\Models\AkunBiaya;
use App\Models\Budget;
use App\Models\Division;
use App\Models\JurnalUmum;
use App\Models\SubAkunBiaya;
use App\Models\SubDivision;
use App\Models\User;
use App\Support\Periode;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\TestCase;

class BudgetPeriodeTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Division $division;
    private AkunBiaya $akun;
    private SubDivision $toko;
    private SubAkunBiaya $kategori;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->division = Division::create(['name' => 'Retail']);
        $this->toko = SubDivision::create(['division_id' => $this->division->id, 'name' => 'Toko A']);
        // Laporan selalu mengecualikan akun id 1 / "kas".
        AkunBiaya::create(['name' => 'Kas']);
        $this->akun = AkunBiaya::create(['name' => 'Operasional']);
        $this->kategori = SubAkunBiaya::create(['akun_biaya_id' => $this->akun->id, 'name' => 'Listrik']);
    }

    private function periode(array $query): Periode
    {
        return Periode::fromRequest(Request::create('/', 'GET', $query));
    }

    private function budget(?int $bulan, float $amount, int $tahun = 2026): void
    {
        Budget::create([
            'division_id' => $this->division->id,
            'akun_biaya_id' => $this->akun->id,
            'tahun' => $tahun,
            'bulan' => $bulan,
            'amount' => $amount,
        ]);
    }

    private function amountFor(Periode $p): float
    {
        return $p->budgetMap('budgets', 'division_id', [$this->division->id], [$this->akun->id])[$this->akun->id][$this->division->id] ?? 0.0;
    }

    public function test_annual_budget_is_prorated_for_part_of_year(): void
    {
        $this->budget(null, 12_000_000);

        $this->assertEquals(12_000_000, $this->amountFor($this->periode(['tahun' => 2026])));
        $this->assertEquals(3_000_000, $this->amountFor($this->periode(['tahun' => 2026, 'bulan_dari' => 1, 'bulan_sampai' => 3])));
        $this->assertEquals(0, $this->amountFor($this->periode(['tahun' => 2025])));
        $this->assertEquals(12_000_000, $this->amountFor($this->periode(['tahun' => 'semua'])));
    }

    public function test_monthly_budgets_are_summed_within_range(): void
    {
        $this->budget(1, 100);
        $this->budget(2, 200);
        $this->budget(3, 300);

        $this->assertEquals(200, $this->amountFor($this->periode(['tahun' => 2026, 'bulan_dari' => 2, 'bulan_sampai' => 2])));
        $this->assertEquals(500, $this->amountFor($this->periode(['tahun' => 2026, 'bulan_dari' => 2, 'bulan_sampai' => 12])));
        $this->assertEquals(600, $this->amountFor($this->periode(['tahun' => 2026])));
    }

    public function test_cannot_mix_annual_and_monthly_budget_in_same_year(): void
    {
        $payload = ['division_id' => $this->division->id, 'akun_biaya_id' => $this->akun->id, 'tahun' => 2026, 'amount' => 1000];

        $this->actingAs($this->user)->postJson(route('admin.keuangan.budget.store'), $payload)->assertOk();
        $this->actingAs($this->user)->postJson(route('admin.keuangan.budget.store'), $payload)->assertStatus(422);
        $this->actingAs($this->user)->postJson(route('admin.keuangan.budget.store'), $payload + ['bulan' => 3])->assertStatus(422);
        // Tahun lain tidak bentrok.
        $this->actingAs($this->user)->postJson(route('admin.keuangan.budget.store'), ['tahun' => 2027, 'bulan' => 3] + $payload)->assertOk();
        $this->actingAs($this->user)->postJson(route('admin.keuangan.budget.store'), ['tahun' => 2027, 'bulan' => 3] + $payload)->assertStatus(422);
        $this->actingAs($this->user)->postJson(route('admin.keuangan.budget.store'), ['tahun' => 2027, 'bulan' => 4] + $payload)->assertOk();

        $this->assertSame(3, Budget::count());
    }

    public function test_laporan_filters_jurnal_by_periode(): void
    {
        $this->budget(null, 1_200_000);
        foreach ([['2026-01-10', 100_000], ['2026-06-10', 200_000], ['2025-12-31', 999_000]] as [$tgl, $kredit]) {
            JurnalUmum::create([
                'tanggal' => $tgl, 'keterangan' => 'x', 'sub_divisi_id' => $this->toko->id,
                'sub_akun_biaya_id' => $this->kategori->id, 'debet' => 0, 'kredit' => $kredit,
            ]);
        }
        JurnalUmum::create([
            'tanggal' => null, 'keterangan' => 'lama', 'sub_divisi_id' => $this->toko->id,
            'sub_akun_biaya_id' => $this->kategori->id, 'debet' => 0, 'kredit' => 5_000,
        ]);

        $q = ['tahun' => 2026, 'bulan_dari' => 1, 'bulan_sampai' => 3, 'satuan' => 'penuh'];
        $res = $this->actingAs($this->user)->get(route('admin.keuangan.laporan.index', $q));
        $res->assertOk()
            ->assertSee('Januari – Maret 2026')
            ->assertSee('100.000')
            ->assertDontSee('200.000')
            ->assertSee('dari 300.000', false)
            ->assertSee('belum punya tanggal');

        $all = $this->actingAs($this->user)->get(route('admin.keuangan.laporan.index', ['tahun' => 'semua', 'satuan' => 'penuh']));
        $all->assertOk()->assertSee('1.304.000')->assertDontSee('belum punya tanggal');

        // Tab lain ikut periode yang sama.
        foreach (['toko', 'tren', 'lewat'] as $tab) {
            $this->actingAs($this->user)->get(route('admin.keuangan.laporan.index', $q + ['tab' => $tab]))->assertOk();
        }
        $this->actingAs($this->user)->get(route('admin.keuangan.laporan.index', $q + ['tab' => 'tren']))->assertSee('200.000');

        // Panel detail: realisasi, budget, dan transaksi untuk periode itu saja.
        $detail = $this->actingAs($this->user)->getJson(route('admin.keuangan.laporan.detail', $q + [
            'akun_id' => $this->akun->id, 'division_id' => $this->division->id,
        ]));
        $detail->assertOk()
            ->assertJsonPath('total', 100000)
            ->assertJsonPath('budget', 300000)
            ->assertJsonPath('jumlah', 1)
            ->assertJsonPath('per_toko.0.name', 'Toko A');
    }

    public function test_sub_divisi_budget_saved_per_year(): void
    {
        $this->actingAs($this->user)->postJson(route('admin.keuangan.laporan.sub-divisi-budget'), [
            'sub_divisi_id' => $this->toko->id, 'akun_biaya_id' => $this->akun->id, 'tahun' => 2026, 'amount' => 5000,
        ])->assertOk();
        $this->actingAs($this->user)->postJson(route('admin.keuangan.laporan.sub-divisi-budget'), [
            'sub_divisi_id' => $this->toko->id, 'akun_biaya_id' => $this->akun->id, 'tahun' => 2026, 'amount' => 7000,
        ])->assertOk();
        $this->actingAs($this->user)->postJson(route('admin.keuangan.laporan.sub-divisi-budget'), [
            'sub_divisi_id' => $this->toko->id, 'akun_biaya_id' => $this->akun->id, 'tahun' => 2027, 'amount' => 9000,
        ])->assertOk();

        $this->assertDatabaseCount('sub_divisi_budgets', 2);
        $this->assertDatabaseHas('sub_divisi_budgets', ['tahun' => 2026, 'bulan' => null, 'amount' => 7000]);
    }

    public function test_budget_page_and_data_show_periode(): void
    {
        $this->budget(null, 1000);
        $this->budget(null, 2000, 2025);

        $this->actingAs($this->user)->get(route('admin.keuangan.budget.index'))->assertOk()->assertSee('Tahunan (satu tahun penuh)');
        $this->actingAs($this->user)->getJson(route('admin.keuangan.budget.data', ['tahun' => 2026]))
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.periode', 'Tahunan 2026');
    }
}
