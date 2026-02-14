<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;

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

            return response()->json([
                'message' => 'Import selesai',
                'created' => $import->created,
                'skipped' => $import->skipped,
                'errors' => array_slice($import->errors, 0, 20),
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
