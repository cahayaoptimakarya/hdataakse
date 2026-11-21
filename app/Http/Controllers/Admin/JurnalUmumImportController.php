<?php

namespace App\Http\Controllers\Admin;

use App\Exports\JurnalUmumErrorExport;
use App\Exports\JurnalUmumTemplateExport;
use App\Http\Controllers\Controller;
use App\Imports\JurnalUmumImport;
use App\Models\JurnalImportBatch;
use App\Support\Audit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;

class JurnalUmumImportController extends Controller
{
    public function index(Request $request)
    {
        $tab = in_array($request->input('tab'), ['transaksi', 'riwayat', 'import'], true)
            ? $request->input('tab')
            : 'transaksi';

        $filters = [
            'q' => trim((string) $request->input('q', '')),
            'tanggal_dari' => $request->input('tanggal_dari'),
            'tanggal_sampai' => $request->input('tanggal_sampai'),
            'division_id' => (int) $request->input('division_id') ?: null,
            'sub_divisi_id' => (int) $request->input('sub_divisi_id') ?: null,
            'akun_id' => (int) $request->input('akun_id') ?: null,
            'sub_akun_id' => (int) $request->input('sub_akun_id') ?: null,
            'batch_id' => (int) $request->input('batch_id') ?: null,
            'tanpa_tanggal' => $request->boolean('tanpa_tanggal'),
        ];
        $perPage = in_array((int) $request->input('per_page'), [25, 50, 100], true) ? (int) $request->input('per_page') : 25;

        $query = $this->transaksiQuery($filters);
        $summary = (clone $query)
            ->reorder()
            ->select(DB::raw('COUNT(*) as jumlah, COALESCE(SUM(ju.debet),0) as total_debet, COALESCE(SUM(ju.kredit),0) as total_kredit, COUNT(DISTINCT ju.sub_divisi_id) as jumlah_toko'))
            ->first();
        $transaksi = $query->paginate($perPage)->withQueryString();

        $batches = JurnalImportBatch::with(['user:id,name', 'cancelledBy:id,name'])
            ->orderByDesc('id')
            ->paginate(20, ['*'], 'batch_page')
            ->withQueryString();

        return view('admin.keuangan.jurnal-umum.index', [
            'tab' => $tab,
            'filters' => $filters,
            'perPage' => $perPage,
            'transaksi' => $transaksi,
            'summary' => $summary,
            'batches' => $batches,
            'divisionOptions' => DB::table('divisions')->orderBy('name')->get(['id', 'name']),
            'subDivisiOptions' => DB::table('sub_divisions as sd')
                ->join('divisions as d', 'd.id', '=', 'sd.division_id')
                ->orderBy('d.name')->orderBy('sd.name')
                ->get(['sd.id', 'sd.name', 'd.name as division_name']),
            'akunOptions' => DB::table('akun_biaya')->orderBy('name')->get(['id', 'name']),
            'subAkunOptions' => DB::table('sub_akun_biaya as sab')
                ->join('akun_biaya as ab', 'ab.id', '=', 'sab.akun_biaya_id')
                ->orderBy('ab.name')->orderBy('sab.name')
                ->get(['sab.id', 'sab.name', 'ab.name as akun_name']),
            'batchOptions' => JurnalImportBatch::aktif()->orderByDesc('id')->get(['id', 'file_name']),
        ]);
    }

    private function transaksiQuery(array $f)
    {
        $query = DB::table('jurnal_umum as ju')
            ->join('sub_divisions as sd', 'sd.id', '=', 'ju.sub_divisi_id')
            ->join('divisions as d', 'd.id', '=', 'sd.division_id')
            ->join('sub_akun_biaya as sab', 'sab.id', '=', 'ju.sub_akun_biaya_id')
            ->join('akun_biaya as ab', 'ab.id', '=', 'sab.akun_biaya_id')
            ->select(
                'ju.id', 'ju.tanggal', 'ju.keterangan', 'ju.debet', 'ju.kredit', 'ju.import_batch_id',
                'sd.name as toko', 'd.name as divisi', 'sab.name as kategori', 'ab.name as akun'
            )
            ->orderByRaw('ju.tanggal IS NULL')
            ->orderByDesc('ju.tanggal')
            ->orderByDesc('ju.id');

        if ($f['q'] !== '') {
            $like = '%'.$f['q'].'%';
            $query->where(function ($q) use ($like) {
                $q->where('ju.keterangan', 'like', $like)
                    ->orWhere('sd.name', 'like', $like)
                    ->orWhere('sab.name', 'like', $like)
                    ->orWhere('ab.name', 'like', $like)
                    ->orWhere('d.name', 'like', $like);
            });
        }
        if ($f['tanpa_tanggal']) {
            $query->whereNull('ju.tanggal');
        } else {
            if ($f['tanggal_dari']) {
                $query->where('ju.tanggal', '>=', $f['tanggal_dari']);
            }
            if ($f['tanggal_sampai']) {
                $query->where('ju.tanggal', '<=', $f['tanggal_sampai']);
            }
        }
        foreach (['division_id' => 'd.id', 'sub_divisi_id' => 'sd.id', 'akun_id' => 'ab.id', 'sub_akun_id' => 'sab.id', 'batch_id' => 'ju.import_batch_id'] as $key => $column) {
            if ($f[$key]) {
                $query->where($column, $f[$key]);
            }
        }

        return $query;
    }

    public function template()
    {
        return Excel::download(new JurnalUmumTemplateExport(), 'template-import-jurnal-umum.xlsx');
    }

    public function store(Request $request)
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:xlsx,xls', 'max:5120'],
        ]);

        $file = $request->file('file');
        $fileName = $file->getClientOriginalName();
        $fileHash = hash_file('sha256', $file->getRealPath());

        // Tolak file yang persis sama dengan import yang masih aktif.
        $sameFile = JurnalImportBatch::aktif()->where('file_hash', $fileHash)->orderByDesc('id')->first();
        if ($sameFile) {
            $message = "File ini sama persis dengan import {$sameFile->kode()} ({$sameFile->file_name}, "
                .$sameFile->created_at->format('d/m/Y H:i').'). Import dibatalkan agar data tidak dobel.';
            JurnalImportBatch::create([
                'file_name' => $fileName,
                'file_hash' => $fileHash,
                'user_id' => $request->user()?->id,
                'status' => JurnalImportBatch::STATUS_DITOLAK,
                'message' => 'Duplikat dari '.$sameFile->kode(),
            ]);
            return response()->json(['message' => $message, 'duplicate_of' => $sameFile->id], 422);
        }

        DB::beginTransaction();
        try {
            $batch = JurnalImportBatch::create([
                'file_name' => $fileName,
                'file_hash' => $fileHash,
                'user_id' => $request->user()?->id,
                'status' => JurnalImportBatch::STATUS_BERHASIL,
            ]);

            $import = new JurnalUmumImport($batch->id);
            Excel::import($import, $file);

            $allFailed = $import->created === 0
                && ($import->skipped > 0 || !empty($import->errors) || !empty($import->errorRows));

            if ($allFailed || $import->created === 0) {
                // Tidak ada yang disimpan: batalkan transaksi, lalu catat sebagai gagal.
                DB::rollBack();
                $errorPath = $this->storeErrorFile($import->errorRows);
                $failed = JurnalImportBatch::create([
                    'file_name' => $fileName,
                    'file_hash' => $fileHash,
                    'user_id' => $request->user()?->id,
                    'status' => JurnalImportBatch::STATUS_GAGAL,
                    'error_count' => count($import->errorRows),
                    'message' => $import->duplicateCount > 0
                        ? $import->duplicateCount.' baris duplikat dengan data yang sudah ada'
                        : ($allFailed ? 'Ada baris error' : 'File tidak berisi data'),
                    'error_file_path' => $errorPath,
                ]);

                return response()->json([
                    'message' => $allFailed
                        ? 'Semua data tidak dimasukkan ke database karena ada error.'
                        : 'File tidak berisi baris data.',
                    'created' => 0,
                    'skipped' => $import->skipped,
                    'duplicates' => $import->duplicateCount,
                    'errors' => array_slice($import->errors, 0, 20),
                    'error_file_url' => $errorPath ? Storage::disk('public')->url($errorPath) : null,
                    'all_failed' => true,
                    'batch' => $failed->kode(),
                ]);
            }

            $batch->update([
                'rows_count' => $import->created,
                'total_debet' => $import->totalDebet,
                'total_kredit' => $import->totalKredit,
                'tanggal_awal' => $import->tanggalAwal,
                'tanggal_akhir' => $import->tanggalAkhir,
            ]);
            Audit::log('import', 'Import jurnal', $batch->id, "Import {$batch->kode()} ({$fileName}): {$import->created} baris");
            DB::commit();

            return response()->json([
                'message' => 'Import selesai',
                'created' => $import->created,
                'skipped' => $import->skipped,
                'duplicates' => 0,
                'errors' => [],
                'error_file_url' => null,
                'all_failed' => false,
                'batch' => $batch->kode(),
                'batch_id' => $batch->id,
            ]);
        } catch (\RuntimeException $e) {
            DB::rollBack();
            return response()->json(['message' => $e->getMessage()], 422);
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json(['message' => 'Gagal import: '.$e->getMessage()], 500);
        }
    }

    private function storeErrorFile(array $errorRows): ?string
    {
        if (empty($errorRows)) {
            return null;
        }
        $dir = 'jurnal-umum-import-errors';
        Storage::disk('public')->makeDirectory($dir);
        $path = $dir.'/jurnal-umum-errors-'.now()->format('Ymd_His').'.xlsx';
        Excel::store(new JurnalUmumErrorExport($errorRows), $path, 'public');

        return $path;
    }

    /** Batalkan satu import: hapus semua baris jurnal dari batch tersebut. */
    public function cancelBatch(Request $request, JurnalImportBatch $batch)
    {
        if ($batch->status !== JurnalImportBatch::STATUS_BERHASIL) {
            return response()->json(['message' => 'Hanya import yang berhasil yang bisa dibatalkan.'], 422);
        }

        DB::beginTransaction();
        try {
            $deleted = DB::table('jurnal_umum')->where('import_batch_id', $batch->id)->delete();
            $batch->update([
                'status' => JurnalImportBatch::STATUS_DIBATALKAN,
                'cancelled_at' => now(),
                'cancelled_by' => $request->user()?->id,
            ]);
            Audit::log('batal', 'Import jurnal', $batch->id, "Import {$batch->kode()} ({$batch->file_name}) dibatalkan, {$deleted} baris dihapus");
            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json(['message' => 'Gagal membatalkan import: '.$e->getMessage()], 500);
        }

        return response()->json([
            'message' => "Import {$batch->kode()} dibatalkan. {$deleted} baris jurnal dihapus.",
            'deleted' => $deleted,
        ]);
    }

    public function destroyAll(Request $request)
    {
        try {
            DB::beginTransaction();
            $count = DB::table('jurnal_umum')->count();
            if ($count === 0) {
                DB::rollBack();
                return response()->json([
                    'message' => 'Data jurnal umum sudah kosong.',
                    'deleted' => 0,
                    'was_empty' => true,
                ]);
            }
            DB::table('jurnal_umum')->delete();
            JurnalImportBatch::aktif()->update([
                'status' => JurnalImportBatch::STATUS_DIBATALKAN,
                'cancelled_at' => now(),
                'cancelled_by' => $request->user()?->id,
                'message' => 'Dihapus lewat Hapus Semua',
            ]);
            Audit::log('hapus', 'Jurnal umum', null, "Semua jurnal umum dihapus ({$count} baris)");
            DB::commit();
            return response()->json([
                'message' => 'Semua data jurnal umum berhasil dihapus.',
                'deleted' => $count,
                'was_empty' => false,
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json(['message' => 'Gagal menghapus data: '.$e->getMessage()], 500);
        }
    }
}
