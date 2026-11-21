<?php

namespace Tests\Feature;

use App\Models\AkunBiaya;
use App\Models\AuditLog;
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

class BandingkanDanJurnalCrudTest extends TestCase
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
        $this->user = User::factory()->create();
        $this->division = Division::create(['name' => 'Retail']);
        $this->toko = SubDivision::create(['division_id' => $this->division->id, 'name' => 'Toko A']);
        $this->akun = AkunBiaya::create(['name' => 'Operasional']);
        $this->kategori = SubAkunBiaya::create(['akun_biaya_id' => $this->akun->id, 'name' => 'Listrik']);
        Budget::create(['division_id' => $this->division->id, 'akun_biaya_id' => $this->akun->id, 'tahun' => 2026, 'bulan' => null, 'amount' => 1_200_000]);
        foreach ([['2026-10-05', 120_000], ['2026-09-05', 100_000], ['2025-10-05', 80_000]] as [$tgl, $kredit]) {
            JurnalUmum::create([
                'tanggal' => $tgl, 'keterangan' => 'x', 'sub_divisi_id' => $this->toko->id,
                'sub_akun_biaya_id' => $this->kategori->id, 'debet' => 0, 'kredit' => $kredit,
            ]);
        }
    }

    public function test_compare_periods_per_akun_divisi_toko(): void
    {
        $periodes = [new Periode(2026, 10, 10), new Periode(2026, 9, 9), new Periode(2025, 10, 10)];

        $akun = CostReport::compare($periodes, 'akun');
        $this->assertEquals([120_000, 100_000, 80_000], $akun['totals']['r']);
        $this->assertEquals([100_000, 100_000, 0], $akun['totals']['b']);
        $this->assertCount(2, $akun['rows']); // akun + kategori
        $this->assertSame(1, $akun['rows'][1]->level);
        $this->assertNull($akun['rows'][1]->b[0]);

        $divisi = CostReport::compare($periodes, 'divisi');
        $this->assertSame('Retail', $divisi['rows'][0]->name);
        $this->assertEquals(100_000, $divisi['rows'][0]->b[0]);

        $toko = CostReport::compare($periodes, 'toko');
        $this->assertSame('Toko A', $toko['rows'][0]->name);
        $this->assertEquals(0, $toko['totals']['b'][0]);

        $this->assertEquals(20.0, CostReport::change(100_000, 120_000));
        $this->assertNull(CostReport::change(0, 5));
    }

    public function test_compare_tab_renders_with_defaults_and_custom_periods(): void
    {
        $base = ['tab' => 'bandingkan', 'tahun' => 2026, 'bulan_dari' => 10, 'bulan_sampai' => 10, 'satuan' => 'penuh'];

        // Default: periode sebelumnya + periode yang sama tahun lalu.
        $this->actingAs($this->user)->get(route('admin.keuangan.laporan.index', $base))
            ->assertOk()->assertSee('September 2026')->assertSee('Oktober 2025')->assertSee('▼ 16,7%', false);

        $this->actingAs($this->user)->get(route('admin.keuangan.laporan.index', $base + ['kelompok' => 'toko', 'banding_set' => 1, 'banding' => ['2025-10-10', 'x', '2025-10-10']]))
            ->assertOk()->assertSee('Toko A')->assertSee('Oktober 2025')->assertDontSee('September 2026');

        // Semua pembanding dihapus: hanya periode utama.
        $this->actingAs($this->user)->get(route('admin.keuangan.laporan.index', $base + ['banding_set' => 1]))
            ->assertOk()->assertDontSee('Oktober 2025');
    }

    public function test_jurnal_crud_with_audit(): void
    {
        $this->actingAs($this->user);
        $payload = [
            'tanggal' => '2026-10-20', 'keterangan' => 'Bayar listrik', 'sub_divisi_id' => $this->toko->id,
            'sub_akun_biaya_id' => $this->kategori->id, 'debet' => '', 'kredit' => '1.500.000',
        ];
        $id = $this->postJson(route('admin.keuangan.jurnal-umum.transaksi.store'), $payload)->assertOk()->json('id');
        $j = JurnalUmum::find($id);
        $this->assertEquals(1_500_000, $j->kredit);
        $this->assertNotNull($j->row_hash);
        $this->assertTrue(AuditLog::where('action', 'tambah')->where('subject_type', 'Jurnal')->exists());

        $this->getJson(route('admin.keuangan.jurnal-umum.transaksi.show', $id))->assertOk()->assertJson(['tanggal' => '2026-10-20', 'kredit' => 1500000]);

        $this->putJson(route('admin.keuangan.jurnal-umum.transaksi.update', $id), ['kredit' => '1.750.000'] + $payload)->assertOk();
        $this->assertEquals(1_750_000, $j->fresh()->kredit);
        $log = AuditLog::where('action', 'ubah')->where('subject_type', 'Jurnal')->first();
        $this->assertEquals([1_500_000, 1_750_000], $log->changes['kredit']);

        // Validasi: tanggal wajib, debet/kredit tidak boleh dua-duanya nol.
        $this->postJson(route('admin.keuangan.jurnal-umum.transaksi.store'), ['tanggal' => ''] + $payload)->assertStatus(422);
        $this->postJson(route('admin.keuangan.jurnal-umum.transaksi.store'), ['kredit' => '0'] + $payload)->assertStatus(422);
        $this->postJson(route('admin.keuangan.jurnal-umum.transaksi.store'), ['kredit' => 'abc'] + $payload)->assertStatus(422);

        $this->deleteJson(route('admin.keuangan.jurnal-umum.transaksi.destroy', $id))->assertOk();
        $this->assertNull(JurnalUmum::find($id));
        $this->assertTrue(AuditLog::where('action', 'hapus')->where('subject_type', 'Jurnal')->exists());

        $this->get(route('admin.keuangan.jurnal-umum.index'))->assertOk()->assertSee('Tambah transaksi');
    }
}
