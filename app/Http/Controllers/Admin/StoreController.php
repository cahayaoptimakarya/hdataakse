<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Store;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class StoreController extends Controller
{
    public function index()
    {
        $pics = User::orderBy('name')->get(['id','name']);
        return view('admin.masterdata.stores.index', compact('pics'));
    }

    public function data(Request $request)
    {
        $query = Store::with('pic')->orderBy('name');

        $search = trim((string) $request->input('q', ''));
        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('city', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%")
                    ->orWhere('address', 'like', "%{$search}%");
            });
        }

        $picFilter = $request->input('pic_id');
        if ($picFilter !== null && $picFilter !== '') {
            $query->where('pic_id', (int) $picFilter);
        }

        $recordsTotal = Store::count();
        $recordsFiltered = (clone $query)->count();

        $start = (int) $request->input('start', 0);
        $length = (int) $request->input('length', 10);
        if ($length > 0) {
            $query->skip($start)->take($length);
        }

        $data = $query->get()->map(function ($s) {
            return [
                'id' => $s->id,
                'name' => $s->name,
                'pic' => $s->pic?->name ?? '-',
                'pic_id' => $s->pic_id,
                'logo_url' => $s->logo_url,
                'address' => $s->address ?? '-',
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
            'pic_id' => ['nullable', 'integer', 'exists:users,id'],
            'logo' => ['nullable', 'image', 'mimes:jpg,jpeg,png', 'max:2048'],
            'address' => ['nullable', 'string'],
        ]);

        $logoPath = null;
        if ($request->file('logo')) {
            $stored = $request->file('logo')->store('store-logos', 'public');
            $logoPath = 'storage/'.$stored;
        }

        $store = Store::create([
            'name' => $validated['name'],
            'pic_id' => $validated['pic_id'] ?? null,
            'logo' => $logoPath,
            'address' => $validated['address'] ?? null,
        ]);

        return response()->json([
            'message' => 'Toko berhasil dibuat',
            'store' => [
                'id' => $store->id,
                'name' => $store->name,
                'logo_url' => $store->logo_url,
            ],
        ]);
    }

    public function update(Request $request, Store $store)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'pic_id' => ['nullable', 'integer', 'exists:users,id'],
            'logo' => ['nullable', 'image', 'mimes:jpg,jpeg,png', 'max:2048'],
            'address' => ['nullable', 'string'],
        ]);

        $update = [
            'name' => $validated['name'],
            'pic_id' => $validated['pic_id'] ?? null,
            'address' => $validated['address'] ?? null,
        ];

        if ($request->file('logo')) {
            $stored = $request->file('logo')->store('store-logos', 'public');
            if ($store->logo && str_starts_with($store->logo, 'storage/store-logos/')) {
                $oldPath = str_replace('storage/', '', $store->logo);
                Storage::disk('public')->delete($oldPath);
            }
            $update['logo'] = 'storage/'.$stored;
        }

        $store->update($update);

        return response()->json([
            'message' => 'Toko berhasil diperbarui',
            'store' => [
                'id' => $store->id,
                'name' => $store->name,
                'logo_url' => $store->logo_url,
            ],
        ]);
    }

    public function destroy(Store $store)
    {
        if ($store->logo && str_starts_with($store->logo, 'storage/store-logos/')) {
            $oldPath = str_replace('storage/', '', $store->logo);
            Storage::disk('public')->delete($oldPath);
        }
        $store->delete();
        return response()->json(['message' => 'Toko berhasil dihapus']);
    }
}
