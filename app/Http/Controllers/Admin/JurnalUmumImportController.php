<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\JurnalUmumErrorExport;

class JurnalUmumImportController extends Controller
{
    public function index()
    {
        return view('admin.keuangan.jurnal-umum.index');
    }

    public function store(Request $request)
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:xlsx,xls', 'max:5120'],
        ]);

        try {
            $import = new \App\Imports\JurnalUmumImport();
            DB::beginTransaction();
            Excel::import($import, $request->file('file'));
            DB::commit();

            $errorFileUrl = null;
            if (!empty($import->errorRows)) {
                $dir = 'jurnal-umum-import-errors';
                Storage::disk('public')->makeDirectory($dir);
                $filename = 'jurnal-umum-errors-'.now()->format('Ymd_His').'.xlsx';
                $path = $dir.'/'.$filename;
                Excel::store(new JurnalUmumErrorExport($import->errorRows), $path, 'public');
                $errorFileUrl = '/storage/'.$path;
            }

            $allFailed = $import->created === 0
                && ($import->skipped > 0 || !empty($import->errors) || !empty($import->errorRows));
            $message = $allFailed
                ? 'Semua data tidak dimasukkan ke database karena ada error.'
                : 'Import selesai';

            return response()->json([
                'message' => $message,
                'created' => $import->created,
                'skipped' => $import->skipped,
                'errors' => array_slice($import->errors, 0, 20),
                'error_file_url' => $errorFileUrl,
                'all_failed' => $allFailed,
            ]);
        } catch (\RuntimeException $e) {
            DB::rollBack();
            return response()->json(['message' => $e->getMessage()], 422);
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json(['message' => 'Gagal import: '.$e->getMessage()], 500);
        }
    }
}
