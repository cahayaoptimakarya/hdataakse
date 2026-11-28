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
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Divisi, toko, akun, dan kategori yang masih punya transaksi jurnal tidak boleh terhapus. */
class HapusMasterDataTest extends TestCase
{
    use RefreshDatabase;

    private Division $division;
    private SubDivision $toko;
    private AkunBiaya $akun;
    private SubAkunBiaya $kategori;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAs(User::factory()->create());
        $this->division = Division::create(['name' => 'Retail']);
        $this->toko = SubDivision::create(['division_id' => $this->division->id, 'name' => 'Toko A']);
        $this->akun = AkunBiaya::create(['name' => 'Operasional']);
        $this->kategori = SubAkunBiaya::create(['akun_biaya_id' => $this->akun->id, 'name' => 'Listrik']);
        Budget::create(['division_id' => $this->division->id, 'akun_biaya_id' => $this->akun->id, 'tahun' => 2026, 'bulan' => null, 'amount' => 1_000_000]);
    }

    private function jurnal(): JurnalUmum
    {
        return JurnalUmum::create([
            'tanggal' => '2026-10-05', 'keterangan' => 'Bayar listrik', 'sub_divisi_id' => $this->toko->id,
            'sub_akun_biaya_id' => $this->kategori->id, 'debet' => 0, 'kredit' => 250_000,
        ]);
    }

    public function test_master_dengan_transaksi_tidak_bisa_dihapus(): void
    {
        $this->jurnal();

        foreach ([
            route('admin.keuangan.divisi.destroy', $this->division) => 'Divisi Retail',
            route('admin.keuangan.sub-divisi.destroy', $this->toko) => 'Toko Toko A',
            route('admin.keuangan.akun-pembayaran.destroy', $this->akun) => 'Akun Operasional',
            route('admin.keuangan.sub-akun-pembayaran.destroy', $this->kategori) => 'Kategori Listrik',
        ] as $url => $label) {
            $this->deleteJson($url)->assertStatus(422)
                ->assertJsonFragment(['message' => "{$label} tidak bisa dihapus karena masih dipakai oleh 1 transaksi jurnal. Pindahkan atau hapus transaksinya dulu."]);
        }

        $this->assertDatabaseCount('jurnal_umum', 1);
        $this->assertDatabaseCount('budgets', 1);
        $this->assertDatabaseHas('divisions', ['id' => $this->division->id]);
        $this->assertDatabaseHas('sub_divisions', ['id' => $this->toko->id]);
        $this->assertDatabaseHas('akun_biaya', ['id' => $this->akun->id]);
        $this->assertDatabaseHas('sub_akun_biaya', ['id' => $this->kategori->id]);
    }

    public function test_master_tanpa_transaksi_bisa_dihapus_dan_tercatat(): void
    {
        $this->deleteJson(route('admin.keuangan.sub-akun-pembayaran.destroy', $this->kategori))->assertOk();
        $this->deleteJson(route('admin.keuangan.akun-pembayaran.destroy', $this->akun))->assertOk()
            ->assertJsonFragment(['message' => 'Akun pembayaran berhasil dihapus (1 baris budget ikut terhapus)']);
        $this->deleteJson(route('admin.keuangan.sub-divisi.destroy', $this->toko))->assertOk();
        $this->deleteJson(route('admin.keuangan.divisi.destroy', $this->division))->assertOk();

        $this->assertDatabaseCount('divisions', 0);
        $this->assertDatabaseCount('akun_biaya', 0);
        $this->assertSame(4, AuditLog::where("action", "hapus")->count()); // dicatat oleh trait Auditable
    }

    public function test_bisa_dihapus_setelah_transaksinya_dihapus(): void
    {
        $jurnal = $this->jurnal();
        $this->deleteJson(route('admin.keuangan.sub-divisi.destroy', $this->toko))->assertStatus(422);

        $jurnal->delete();
        $this->deleteJson(route('admin.keuangan.sub-divisi.destroy', $this->toko))->assertOk();
        $this->assertDatabaseMissing('sub_divisions', ['id' => $this->toko->id]);
    }
}
