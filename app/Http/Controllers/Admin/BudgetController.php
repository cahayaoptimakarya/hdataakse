<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AkunBiaya;
use App\Models\Budget;
use App\Models\Division;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BudgetController extends Controller
{
    public function index()
    {
        $divisions = Division::orderBy('name')->get(['id', 'name']);
        $akunBiaya = AkunBiaya::orderBy('name')->get(['id', 'name']);
        return view('admin.masterdata.budgets.index', compact('divisions', 'akunBiaya'));
    }

    public function data(Request $request)
    {
        $query = Budget::with(['division', 'akunBiaya'])->orderByDesc('id');

        $search = trim((string) $request->input('q', ''));
        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('amount', 'like', "%{$search}%")
                    ->orWhereHas('division', function ($q2) use ($search) {
                        $q2->where('name', 'like', "%{$search}%");
                    })
                    ->orWhereHas('akunBiaya', function ($q2) use ($search) {
                        $q2->where('name', 'like', "%{$search}%");
                    });
            });
        }

        $divisionFilter = $request->input('division_id');
        if ($divisionFilter !== null && $divisionFilter !== '') {
            $query->where('division_id', (int) $divisionFilter);
        }

        $akunFilter = $request->input('akun_biaya_id');
        if ($akunFilter !== null && $akunFilter !== '') {
            $query->where('akun_biaya_id', (int) $akunFilter);
        }

        $recordsTotal = Budget::count();
        $recordsFiltered = (clone $query)->count();

        $start = (int) $request->input('start', 0);
        $length = (int) $request->input('length', 10);
        if ($length > 0) {
            $query->skip($start)->take($length);
        }

        $data = $query->get()->map(function ($budget) {
            return [
                'id' => $budget->id,
                'division' => $budget->division?->name ?? '-',
                'division_id' => $budget->division_id,
                'akun_biaya' => $budget->akunBiaya?->name ?? '-',
                'akun_biaya_id' => $budget->akun_biaya_id,
                'amount' => $budget->amount,
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
            'division_id' => ['required', 'integer', 'exists:divisions,id'],
            'akun_biaya_id' => ['required', 'integer', 'exists:akun_biaya,id'],
            'amount' => ['required', 'numeric', 'min:0'],
        ]);

        DB::beginTransaction();
        try {
            $budget = Budget::create($validated);
            DB::commit();

            return response()->json([
                'message' => 'Budget berhasil dibuat',
                'budget' => [
                    'id' => $budget->id,
                    'division_id' => $budget->division_id,
                    'akun_biaya_id' => $budget->akun_biaya_id,
                    'amount' => $budget->amount,
                ],
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Gagal membuat budget',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function update(Request $request, Budget $budget)
    {
        $validated = $request->validate([
            'division_id' => ['required', 'integer', 'exists:divisions,id'],
            'akun_biaya_id' => ['required', 'integer', 'exists:akun_biaya,id'],
            'amount' => ['required', 'numeric', 'min:0'],
        ]);

        DB::beginTransaction();
        try {
            $budget->update($validated);
            DB::commit();

            return response()->json([
                'message' => 'Budget berhasil diperbarui',
                'budget' => [
                    'id' => $budget->id,
                    'division_id' => $budget->division_id,
                    'akun_biaya_id' => $budget->akun_biaya_id,
                    'amount' => $budget->amount,
                ],
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Gagal memperbarui budget',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function destroy(Budget $budget)
    {
        DB::beginTransaction();
        try {
            $budget->delete();
            DB::commit();
            return response()->json(['message' => 'Budget berhasil dihapus']);
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Gagal menghapus budget',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
