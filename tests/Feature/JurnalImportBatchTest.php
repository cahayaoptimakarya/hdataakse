<?php

namespace Tests\Feature;

use App\Models\AkunBiaya;
use App\Models\Division;
use App\Models\JurnalImportBatch;
use App\Models\JurnalUmum;
use App\Models\SubAkunBiaya;
use App\Models\SubDivision;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Tests\TestCase;

class JurnalImportBatchTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
        $this->user = User::factory()->create();
        $division = Division::create(['name' => 'Divisi A']);
        SubDivision::create(['division_id' => $division->id, 'name' => 'Toko A']);
        $akun = AkunBiaya::create(['name' => 'Operasional']);
        SubAkunBiaya::create(['akun_biaya_id' => $akun->id, 'name' => 'Listrik']);
    }

    private function upload(array $rows, string $name = 'jurnal.xlsx'): UploadedFile
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->fromArray(['tanggal', 'keterangan', 'toko', 'kategori', 'debet', 'kredit'], null, 'A1');
        foreach ($rows as $i => $row) {
            $r = $i + 2;
            $sheet->setCellValueExplicit('A'.$r, $row[0], DataType::TYPE_STRING);
            $sheet->setCellValue('B'.$r, $row[1]);
            $sheet->setCellValue('C'.$r, $row[2]);
            $sheet->setCellValue('D'.$r, $row[3]);
            $sheet->setCellValue('E'.$r, $row[4]);
            $sheet->setCellValue('F'.$r, $row[5]);
        }
        $path = tempnam(sys_get_temp_dir(), 'jurnal').'.xlsx';
        (new Xlsx($spreadsheet))->save($path);

        return new UploadedFile($path, $name, null, null, true);
    }

    private function import(UploadedFile $file)
    {
        return $this->actingAs($this->user)->postJson(route('admin.keuangan.jurnal-umum.store'), ['file' => $file]);
    }

    public function test_successful_import_is_recorded_as_batch(): void
    {
        $res = $this->import($this->upload([
            ['01/11/2026', 'Listrik Nov', 'Toko A', 'Listrik', 0, 500000],
            ['15/11/2026', 'Listrik susulan', 'Toko A', 'Listrik', 0, 250000],
        ]));

        $res->assertOk()->assertJson(['created' => 2, 'all_failed' => false]);
        $batch = JurnalImportBatch::sole();
        $this->assertSame('berhasil', $batch->status);
        $this->assertSame(2, $batch->rows_count);
        $this->assertEquals(750000, $batch->total_kredit);
        $this->assertSame('2026-11-01', $batch->tanggal_awal->format('Y-m-d'));
        $this->assertSame('2026-11-15', $batch->tanggal_akhir->format('Y-m-d'));
        $this->assertSame(2, JurnalUmum::where('import_batch_id', $batch->id)->count());
    }

    public function test_same_file_is_rejected(): void
    {
        $file = $this->upload([['01/11/2026', 'Listrik Nov', 'Toko A', 'Listrik', 0, 500000]]);
        $copy = tempnam(sys_get_temp_dir(), 'copy').'.xlsx';
        copy($file->getRealPath(), $copy);

        $this->import($file)->assertOk();
        $this->import(new UploadedFile($copy, 'jurnal-lagi.xlsx', null, null, true))
            ->assertStatus(422)
            ->assertJsonFragment(['duplicate_of' => JurnalImportBatch::first()->id]);

        $this->assertSame(1, JurnalUmum::count());
        $this->assertSame('ditolak', JurnalImportBatch::orderByDesc('id')->first()->status);
    }

    public function test_duplicate_rows_from_another_file_block_the_whole_import(): void
    {
        $this->import($this->upload([['01/11/2026', 'Listrik Nov', 'Toko A', 'Listrik', 0, 500000]]))->assertOk();

        $res = $this->import($this->upload([
            ['01/11/2026', '  listrik   nov ', 'Toko A', 'Listrik', 0, 500000],
            ['02/11/2026', 'Baris baru', 'Toko A', 'Listrik', 0, 1000],
        ], 'file-lain.xlsx'));

        $res->assertOk()->assertJson(['created' => 0, 'all_failed' => true, 'duplicates' => 1]);
        $this->assertSame(1, JurnalUmum::count());
        $failed = JurnalImportBatch::orderByDesc('id')->first();
        $this->assertSame('gagal', $failed->status);
        $this->assertNotNull($failed->error_file_path);
    }

    public function test_identical_rows_inside_one_file_are_allowed(): void
    {
        $this->import($this->upload([
            ['01/11/2026', 'Parkir', 'Toko A', 'Listrik', 0, 5000],
            ['01/11/2026', 'Parkir', 'Toko A', 'Listrik', 0, 5000],
        ]))->assertOk()->assertJson(['created' => 2]);
    }

    public function test_cancel_batch_removes_only_its_rows(): void
    {
        $this->import($this->upload([['01/11/2026', 'A', 'Toko A', 'Listrik', 0, 100]], 'a.xlsx'))->assertOk();
        $this->import($this->upload([['02/11/2026', 'B', 'Toko A', 'Listrik', 0, 200]], 'b.xlsx'))->assertOk();
        [$first, $second] = JurnalImportBatch::orderBy('id')->get();

        $this->actingAs($this->user)
            ->deleteJson(route('admin.keuangan.jurnal-umum.batches.destroy', $first))
            ->assertOk()
            ->assertJson(['deleted' => 1]);

        $this->assertSame('dibatalkan', $first->fresh()->status);
        $this->assertSame($this->user->id, $first->fresh()->cancelled_by);
        $this->assertSame(['B'], JurnalUmum::pluck('keterangan')->all());

        // Setelah dibatalkan, file yang sama boleh di-import lagi.
        $this->import($this->upload([['01/11/2026', 'A', 'Toko A', 'Listrik', 0, 100]], 'a.xlsx'))->assertOk();
        $this->assertSame(2, JurnalUmum::count());

        $this->actingAs($this->user)
            ->deleteJson(route('admin.keuangan.jurnal-umum.batches.destroy', $first))
            ->assertStatus(422);
    }

    public function test_jurnal_page_lists_transactions_and_history(): void
    {
        $this->import($this->upload([['01/11/2026', 'Bayar listrik toko', 'Toko A', 'Listrik', 0, 500000]]))->assertOk();

        $this->actingAs($this->user)->get(route('admin.keuangan.jurnal-umum.index'))
            ->assertOk()
            ->assertSee('Bayar listrik toko')
            ->assertSee('Rp 500.000');

        $this->actingAs($this->user)->get(route('admin.keuangan.jurnal-umum.index', ['tab' => 'riwayat']))
            ->assertOk()
            ->assertSee('jurnal.xlsx')
            ->assertSee('Berhasil');

        $this->actingAs($this->user)->get(route('admin.keuangan.jurnal-umum.index', ['q' => 'tidak-ada']))
            ->assertOk()
            ->assertSee('Tidak ada transaksi untuk filter ini.');
    }
}
