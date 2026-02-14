<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Division;
use App\Models\SubDivision;
use App\Exports\DivisiSubDivisiSkippedExport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use App\Imports\SubDivisiDivisiImport;

class DivisionController extends Controller
{
    public function index()
    {
        $divisions = Division::orderBy('name')->get(['id', 'name']);
        return view('admin.masterdata.divisions.index', compact('divisions'));
    }

    public function data(Request $request)
    {
        $query = Division::query()->orderBy('name');

        $search = trim((string) $request->input('q', ''));
        if ($search !== '') {
            $query->where('name', 'like', "%{$search}%");
        }

        $recordsTotal = Division::count();
        $recordsFiltered = (clone $query)->count();

        $start = (int) $request->input('start', 0);
        $length = (int) $request->input('length', 10);
        if ($length > 0) {
            $query->skip($start)->take($length);
        }

        $data = $query->get()->map(function ($division) {
            return [
                'id' => $division->id,
                'name' => $division->name,
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
            $division = Division::create($validated);
            DB::commit();

            return response()->json([
                'message' => 'Divisi berhasil dibuat',
                'division' => [
                    'id' => $division->id,
                    'name' => $division->name,
                ],
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Gagal membuat divisi',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function update(Request $request, Division $division)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:150'],
        ]);

        DB::beginTransaction();
        try {
            $division->update($validated);
            DB::commit();

            return response()->json([
                'message' => 'Divisi berhasil diperbarui',
                'division' => [
                    'id' => $division->id,
                    'name' => $division->name,
                ],
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Gagal memperbarui divisi',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function destroy(Division $division)
    {
        DB::beginTransaction();
        try {
            $division->delete();
            DB::commit();

            return response()->json(['message' => 'Divisi berhasil dihapus']);
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Gagal menghapus divisi',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function subData(Request $request)
    {
        $query = SubDivision::with('division')->orderBy('name');

        $search = trim((string) $request->input('q', ''));
        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhereHas('division', function ($q2) use ($search) {
                        $q2->where('name', 'like', "%{$search}%");
                    });
            });
        }

        $divisionFilter = $request->input('division_id');
        if ($divisionFilter !== null && $divisionFilter !== '') {
            $query->where('division_id', (int) $divisionFilter);
        }

        $recordsTotal = SubDivision::count();
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
                'division' => $sub->division?->name ?? '-',
                'division_id' => $sub->division_id,
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
            'division_id' => ['required', 'integer', 'exists:divisions,id'],
            'name' => ['required', 'string', 'max:150'],
        ]);

        DB::beginTransaction();
        try {
            $sub = SubDivision::create($validated);
            DB::commit();

            return response()->json([
                'message' => 'Sub divisi berhasil dibuat',
                'sub_division' => [
                    'id' => $sub->id,
                    'name' => $sub->name,
                    'division_id' => $sub->division_id,
                ],
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Gagal membuat sub divisi',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function updateSub(Request $request, SubDivision $subDivision)
    {
        $validated = $request->validate([
            'division_id' => ['required', 'integer', 'exists:divisions,id'],
            'name' => ['required', 'string', 'max:150'],
        ]);

        DB::beginTransaction();
        try {
            $subDivision->update($validated);
            DB::commit();

            return response()->json([
                'message' => 'Sub divisi berhasil diperbarui',
                'sub_division' => [
                    'id' => $subDivision->id,
                    'name' => $subDivision->name,
                    'division_id' => $subDivision->division_id,
                ],
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Gagal memperbarui sub divisi',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function destroySub(SubDivision $subDivision)
    {
        DB::beginTransaction();
        try {
            $subDivision->delete();
            DB::commit();

            return response()->json(['message' => 'Sub divisi berhasil dihapus']);
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Gagal menghapus sub divisi',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function importSubDivisi(Request $request)
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:xlsx,xls', 'max:5120'],
        ]);

        $import = new SubDivisiDivisiImport();

        DB::beginTransaction();
        try {
            Excel::import($import, $request->file('file'));
            DB::commit();

            $skippedFileUrl = null;
            if (!empty($import->skippedDetails)) {
                $filename = 'divisi-sub-divisi-skip-'.now()->format('Ymd_His').'.xlsx';
                $path = 'divisi-import-skip/'.$filename;
                Excel::store(new DivisiSubDivisiSkippedExport($import->skippedDetails), $path, 'public');
                $skippedFileUrl = '/storage/'.$path;
            }

            return response()->json([
                'message' => 'Import selesai',
                'created_divisi' => $import->createdDivisi,
                'created_sub_divisi' => $import->createdSubDivisi,
                'skipped_divisi' => $import->skippedDivisi,
                'skipped_sub_divisi' => $import->skippedSubDivisi,
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
