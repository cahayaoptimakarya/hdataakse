<?php

namespace Tests\Feature;

use App\Imports\JurnalUmumImport;
use App\Models\AkunBiaya;
use App\Models\Division;
use App\Models\JurnalUmum;
use App\Models\SubAkunBiaya;
use App\Models\SubDivision;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class JurnalUmumImportTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $division = Division::create(['name' => 'Divisi A']);
        SubDivision::create(['division_id' => $division->id, 'name' => 'Toko A']);
        $akun = AkunBiaya::create(['name' => 'Operasional']);
        SubAkunBiaya::create(['akun_biaya_id' => $akun->id, 'name' => 'Listrik']);
    }

    private function makeFile(array $rows, array $header = ['tanggal', 'keterangan', 'toko', 'kategori', 'debet', 'kredit']): string
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->fromArray([$header, ...$rows], null, 'A1', true);
        $sheet->getStyle('A2:A100')->getNumberFormat()->setFormatCode('dd/mm/yyyy');
        // Paksa sel teks untuk tanggal yang berupa string.
        foreach ($rows as $i => $row) {
            if (is_string($row[0])) {
                $sheet->setCellValueExplicit('A'.($i + 2), $row[0], \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
            }
        }
        $path = tempnam(sys_get_temp_dir(), 'jurnal').'.xlsx';
        (new Xlsx($spreadsheet))->save($path);
        return $path;
    }

    public function test_imports_tanggal_from_excel_date_and_text(): void
    {
        $file = $this->makeFile([
            [ExcelDate::PHPToExcel(new \DateTime('2026-11-21')), 'Bayar listrik', 'Toko A', 'Listrik', 100000, 0],
            ['05/10/2026', 'Bayar listrik 2', 'Toko A', 'Listrik', 1500000, 0],
            ['2026-09-01', 'Bayar listrik 3', 'toko a', 'listrik', 0, 2500],
        ]);

        $import = new JurnalUmumImport();
        Excel::import($import, $file);

        $this->assertSame([], $import->errors);
        $this->assertSame(3, $import->created);
        $this->assertSame(
            ['2026-09-01', '2026-10-05', '2026-11-21'],
            JurnalUmum::orderBy('tanggal')->get()->map(fn ($j) => substr((string) $j->tanggal, 0, 10))->all()
        );
    }

    public function test_invalid_or_missing_tanggal_rejects_whole_file(): void
    {
        $file = $this->makeFile([
            ['21/11/2026', 'OK', 'Toko A', 'Listrik', 100, 0],
            ['31/02/2026', 'Salah', 'Toko A', 'Listrik', 100, 0],
            ['', 'Kosong', 'Toko A', 'Listrik', 100, 0],
        ]);

        $import = new JurnalUmumImport();
        Excel::import($import, $file);

        $this->assertSame(0, $import->created);
        $this->assertSame(0, JurnalUmum::count());
        $this->assertSame(['Baris 3: tanggal tidak valid', 'Baris 4: tanggal wajib diisi'], $import->errors);
        $this->assertSame('31/02/2026', $import->errorRows[0][0]);
        $this->assertSame('tanggal tidak valid', $import->errorRows[0][6]);
    }

    public function test_requires_tanggal_header(): void
    {
        $file = $this->makeFile(
            [['OK', 'Toko A', 'Listrik', 100, 0]],
            ['keterangan', 'toko', 'kategori', 'debet', 'kredit']
        );

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Header harus: tanggal');
        Excel::import(new JurnalUmumImport(), $file);
    }
}
