<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AkunBiaya;
use App\Models\SubAkunBiaya;
use App\Imports\AkunPembayaranImport;
use App\Exports\AkunPembayaranSkippedExport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;

class AkunBiayaController extends Controller
{
    public function index()
    {
        $akunBiaya = AkunBiaya::orderBy('name')->get(['id', 'name']);
        return view('admin.masterdata.akun-biaya.index', compact('akunBiaya'));
    }

    public function data(Request $request)
    {
        $query = AkunBiaya::query()->orderBy('name');

        $search = trim((string) $request->input('q', ''));
        if ($search !== '') {
            $query->where('name', 'like', "%{$search}%");
        }

        $recordsTotal = AkunBiaya::count();
        $recordsFiltered = (clone $query)->count();

        $start = (int) $request->input('start', 0);
        $length = (int) $request->input('length', 10);
        if ($length > 0) {
            $query->skip($start)->take($length);
        }

        $data = $query->get()->map(function ($akun) {
            return [
                'id' => $akun->id,
                'name' => $akun->name,
            ];
        });

        return response()->json([
            'draw' => (int) $request->input('draw'),
            'recordsTotal' => $recordsTotal,
            'recordsFiltered' => $recordsFiltered,
            'data' => $data,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:150'],
        ]);

        DB::beginTransaction();
        try {
            $akun = AkunBiaya::create($validated);
            DB::commit();

            return response()->json([
                'message' => 'Akun pembayaran berhasil dibuat',
                'akun_biaya' => [
                    'id' => $akun->id,
                    'name' => $akun->name,
                ],
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Gagal membuat akun pembayaran',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function update(Request $request, AkunBiaya $akunBiaya)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:150'],
        ]);

        DB::beginTransaction();
        try {
            $akunBiaya->update($validated);
            DB::commit();

            return response()->json([
                'message' => 'Akun pembayaran berhasil diperbarui',
                'akun_biaya' => [
                    'id' => $akunBiaya->id,
                    'name' => $akunBiaya->name,
                ],
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Gagal memperbarui akun pembayaran',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function destroy(AkunBiaya $akunBiaya)
    {
        DB::beginTransaction();
        try {
            $akunBiaya->delete();
            DB::commit();

            return response()->json(['message' => 'Akun pembayaran berhasil dihapus']);
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Gagal menghapus akun pembayaran',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function subData(Request $request)
    {
        $query = SubAkunBiaya::with('akunBiaya')->orderBy('name');

        $search = trim((string) $request->input('q', ''));
        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhereHas('akunBiaya', function ($q2) use ($search) {
                        $q2->where('name', 'like', "%{$search}%");
                    });
            });
        }

        $akunFilter = $request->input('akun_biaya_id');
        if ($akunFilter !== null && $akunFilter !== '') {
            $query->where('akun_biaya_id', (int) $akunFilter);
        }

        $recordsTotal = SubAkunBiaya::count();
        $recordsFiltered = (clone $query)->count();

        $start = (int) $request->input('start', 0);
        $length = (int) $request->input('length', 10);
        if ($length > 0) {
            $query->skip($start)->take($length);
        }

        $data = $query->get()->map(function ($sub) {
            return [
                'id' => $sub->id,
                'name' => $sub->name,
                'akun_biaya' => $sub->akunBiaya?->name ?? '-',
                'akun_biaya_id' => $sub->akun_biaya_id,
            ];
        });

        return response()->json([
            'draw' => (int) $request->input('draw'),
            'recordsTotal' => $recordsTotal,
            'recordsFiltered' => $recordsFiltered,
            'data' => $data,
        ]);
    }

    public function storeSub(Request $request)
    {
        $validated = $request->validate([
            'akun_biaya_id' => ['required', 'integer', 'exists:akun_biaya,id'],
            'name' => ['required', 'string', 'max:150'],
        ]);

        DB::beginTransaction();
        try {
            $sub = SubAkunBiaya::create($validated);
            DB::commit();

            return response()->json([
                'message' => 'Sub akun pembayaran berhasil dibuat',
                'sub_akun_biaya' => [
                    'id' => $sub->id,
                    'name' => $sub->name,
                    'akun_biaya_id' => $sub->akun_biaya_id,
                ],
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Gagal membuat sub akun pembayaran',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function updateSub(Request $request, SubAkunBiaya $subAkunBiaya)
    {
        $validated = $request->validate([
            'akun_biaya_id' => ['required', 'integer', 'exists:akun_biaya,id'],
            'name' => ['required', 'string', 'max:150'],
        ]);

        DB::beginTransaction();
        try {
            $subAkunBiaya->update($validated);
            DB::commit();

            return response()->json([
                'message' => 'Sub akun pembayaran berhasil diperbarui',
                'sub_akun_biaya' => [
                    'id' => $subAkunBiaya->id,
                    'name' => $subAkunBiaya->name,
                    'akun_biaya_id' => $subAkunBiaya->akun_biaya_id,
                ],
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Gagal memperbarui sub akun pembayaran',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function destroySub(SubAkunBiaya $subAkunBiaya)
    {
        DB::beginTransaction();
        try {
            $subAkunBiaya->delete();
            DB::commit();

            return response()->json(['message' => 'Sub akun pembayaran berhasil dihapus']);
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Gagal menghapus sub akun pembayaran',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function importAkunPembayaran(Request $request)
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:xlsx,xls', 'max:5120'],
        ]);

        $import = new AkunPembayaranImport();

        DB::beginTransaction();
        try {
            Excel::import($import, $request->file('file'));
            DB::commit();

            $skippedFileUrl = null;
            if (!empty($import->skippedDetails)) {
                $filename = 'akun-pembayaran-skip-'.now()->format('Ymd_His').'.xlsx';
                $path = 'akun-pembayaran-import-skip/'.$filename;
                Excel::store(new AkunPembayaranSkippedExport($import->skippedDetails), $path, 'public');
                $skippedFileUrl = '/storage/'.$path;
            }

            return response()->json([
                'message' => 'Import selesai',
                'created_akun' => $import->createdAkun,
                'created_sub_akun' => $import->createdSubAkun,
                'skipped_akun' => $import->skippedAkun,
                'skipped_sub_akun' => $import->skippedSubAkun,
                'skipped_rows' => $import->skippedRows,
                'skipped_details' => array_slice($import->skippedDetails, 0, 20),
                'skipped_file_url' => $skippedFileUrl,
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Gagal import: '.$e->getMessage(),
            ], 500);
        }
    }
}
