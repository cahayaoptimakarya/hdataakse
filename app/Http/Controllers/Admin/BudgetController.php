<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AkunBiaya;
use App\Models\Budget;
use App\Models\Division;
use App\Support\Periode;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BudgetController extends Controller
{
    public function index()
    {
        $divisions = Division::orderBy('name')->get(['id', 'name']);
        $akunBiaya = AkunBiaya::orderBy('name')->get(['id', 'name']);
        $tahunOptions = Periode::tahunOptions();
        $namaBulan = Periode::NAMA_BULAN;
        return view('admin.masterdata.budgets.index', compact('divisions', 'akunBiaya', 'tahunOptions', 'namaBulan'));
    }

    public function data(Request $request)
    {
        $query = Budget::with(['division', 'akunBiaya'])
            ->orderByDesc('tahun')
            ->orderByRaw('bulan IS NULL DESC')
            ->orderBy('bulan')
            ->orderByDesc('id');

        $tahunFilter = $request->input('tahun');
        if ($tahunFilter !== null && $tahunFilter !== '') {
            $query->where('tahun', (int) $tahunFilter);
        }

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
                'tahun' => $budget->tahun,
                'bulan' => $budget->bulan,
                'periode' => $budget->periodeLabel(),
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
        $validated = $this->validatePayload($request);
        if ($conflict = $this->conflictMessage($validated)) {
            return response()->json(['message' => $conflict, 'errors' => ['bulan' => [$conflict]]], 422);
        }

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
                    'tahun' => $budget->tahun,
                    'bulan' => $budget->bulan,
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
        $validated = $this->validatePayload($request);
        if ($conflict = $this->conflictMessage($validated, $budget->id)) {
            return response()->json(['message' => $conflict, 'errors' => ['bulan' => [$conflict]]], 422);
        }

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
                    'tahun' => $budget->tahun,
                    'bulan' => $budget->bulan,
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

    private function validatePayload(Request $request): array
    {
        // Input jumlah boleh berformat Rupiah (mis. 1.500.000 atau 1.500.000,50).
        if (is_string($request->input('amount'))) {
            $parsed = \App\Imports\JurnalUmumImport::parseAmount($request->input('amount'));
            $request->merge(['amount' => $parsed ?? $request->input('amount')]);
        }
        $validated = $request->validate([
            'division_id' => ['required', 'integer', 'exists:divisions,id'],
            'akun_biaya_id' => ['required', 'integer', 'exists:akun_biaya,id'],
            'tahun' => ['required', 'integer', 'min:2000', 'max:2100'],
            'bulan' => ['nullable', 'integer', 'min:1', 'max:12'],
            'amount' => ['required', 'numeric', 'min:0'],
        ]);
        $validated['bulan'] = isset($validated['bulan']) && $validated['bulan'] !== '' ? (int) $validated['bulan'] : null;

        return $validated;
    }

    public function template()
    {
        return \Maatwebsite\Excel\Facades\Excel::download(new \App\Exports\BudgetTemplateExport(), 'template-import-budget.xlsx');
    }

    /** Import budget dari Excel: semua baris valid baru disimpan, kalau ada yang salah tidak ada yang disimpan. */
    public function import(Request $request)
    {
        $request->validate(['file' => ['required', 'file', 'mimes:xlsx,xls', 'max:5120']]);
        $sheets = \Maatwebsite\Excel\Facades\Excel::toArray(new class implements \Maatwebsite\Excel\Concerns\WithHeadingRow {}, $request->file('file'));
        $rows = $sheets[0] ?? [];
        if (!$rows || !array_key_exists('jumlah', array_change_key_case($rows[0]))) {
            return response()->json(['message' => 'Format file tidak sesuai. Pakai template: '.implode(', ', \App\Services\BudgetTools::HEADERS).'.'], 422);
        }
        $result = \App\Services\BudgetTools::import($rows);
        if (!$result['ok']) {
            return response()->json([
                'message' => count($result['errors']).' baris bermasalah, tidak ada budget yang disimpan.',
                'errors' => array_slice($result['errors'], 0, 50),
            ], 422);
        }

        return response()->json([
            'message' => "Import selesai: {$result['created']} budget baru, {$result['updated']} diperbarui.",
        ] + $result);
    }

    public function copy(Request $request)
    {
        $v = $request->validate([
            'dari_tahun' => ['required', 'integer', 'min:2000', 'max:2100'],
            'ke_tahun' => ['required', 'integer', 'min:2000', 'max:2100', 'different:dari_tahun'],
            'persen' => ['nullable', 'numeric', 'min:-100', 'max:1000'],
            'timpa' => ['nullable', 'boolean'],
            'termasuk_toko' => ['nullable', 'boolean'],
        ]);
        if (!Budget::where('tahun', $v['dari_tahun'])->exists() && !DB::table('sub_divisi_budgets')->where('tahun', $v['dari_tahun'])->exists()) {
            return response()->json(['message' => 'Belum ada budget di tahun '.$v['dari_tahun'].'.'], 422);
        }
        $r = \App\Services\BudgetTools::copy((int) $v['dari_tahun'], (int) $v['ke_tahun'], (float) ($v['persen'] ?? 0),
            $request->boolean('timpa'), $request->boolean('termasuk_toko', true));

        return response()->json([
            'message' => "{$r['copied']} budget disalin ke {$v['ke_tahun']}."
                .($r['skipped'] ? " {$r['skipped']} dilewati karena sudah ada." : '')
                .($r['replaced'] ? " {$r['replaced']} budget lama ditimpa." : ''),
        ] + $r);
    }

    /**
     * Dalam satu tahun, satu divisi × akun memakai budget tahunan ATAU bulanan,
     * dan tiap bulan hanya boleh satu baris.
     */
    private function conflictMessage(array $data, ?int $ignoreId = null): ?string
    {
        $existing = Budget::where('division_id', $data['division_id'])
            ->where('akun_biaya_id', $data['akun_biaya_id'])
            ->where('tahun', $data['tahun'])
            ->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))
            ->get(['bulan']);

        if ($existing->isEmpty()) {
            return null;
        }
        if ($data['bulan'] === null) {
            return $existing->contains(fn ($b) => $b->bulan === null)
                ? 'Budget tahunan untuk divisi, akun, dan tahun ini sudah ada. Silakan edit yang sudah ada.'
                : 'Divisi dan akun ini sudah memakai budget bulanan di tahun tersebut. Hapus budget bulanannya dulu bila ingin memakai budget tahunan.';
        }
        if ($existing->contains(fn ($b) => $b->bulan === null)) {
            return 'Divisi dan akun ini sudah memakai budget tahunan di tahun tersebut. Hapus budget tahunannya dulu bila ingin memakai budget bulanan.';
        }
        if ($existing->contains(fn ($b) => (int) $b->bulan === $data['bulan'])) {
            return 'Budget untuk bulan ini sudah ada. Silakan edit yang sudah ada.';
        }

        return null;
    }
}
