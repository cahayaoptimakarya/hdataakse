<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Item;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ItemController extends Controller
{
    public function index()
    {
        $categories = Category::orderBy('name')->get(['id', 'name']);
        return view('admin.masterdata.items.index', compact('categories'));
    }

    public function data(Request $request)
    {
        $query = Item::with('category')->orderBy('name');

        $search = trim((string) $request->input('q', ''));
        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        $catFilter = $request->input('category_id');
        if ($catFilter !== null && $catFilter !== '') {
            if ((int)$catFilter === 0) {
                $query->where('category_id', 0);
            } else {
                $query->where('category_id', (int)$catFilter);
            }
        }

        $recordsTotal = Item::count();
        $recordsFiltered = (clone $query)->count();

        $start = (int) $request->input('start', 0);
        $length = (int) $request->input('length', 10);
        if ($length > 0) {
            $query->skip($start)->take($length);
        }

        $data = $query->get()->map(function ($i) {
            return [
                'id' => $i->id,
                'name' => $i->name,
                'category' => $i->category?->name ?? '-',
                'category_id' => $i->category_id,
                'description' => $i->description ?? '',
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
            'category_id' => ['nullable', 'integer', 'min:0', function($attr, $value, $fail) {
                if ((int)$value === 0) return;
                if (!Category::where('id', $value)->exists()) {
                    $fail('Kategori tidak valid');
                }
            }],
            'description' => ['nullable', 'string'],
        ]);

        $catId = $request->input('category_id');
        $validated['category_id'] = ($catId === null || (int)$catId === 0) ? 0 : $catId;

        $item = Item::create($validated);

        return response()->json([
            'message' => 'Item berhasil dibuat',
            'item' => [
                'id' => $item->id,
                'name' => $item->name,
                'category_id' => $item->category_id,
            ]
        ]);
    }

    public function update(Request $request, Item $item)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'category_id' => ['nullable', 'integer', 'min:0', function($attr, $value, $fail) {
                if ((int)$value === 0) return;
                if (!Category::where('id', $value)->exists()) {
                    $fail('Kategori tidak valid');
                }
            }],
            'description' => ['nullable', 'string'],
        ]);

        $catId = $request->input('category_id');
        $validated['category_id'] = ($catId === null || (int)$catId === 0) ? 0 : $catId;

        $item->update($validated);

        return response()->json([
            'message' => 'Item berhasil diperbarui',
            'item' => [
                'id' => $item->id,
                'name' => $item->name,
                'category_id' => $item->category_id,
            ]
        ]);
    }

    public function destroy(Item $item)
    {
        $item->delete();

        return response()->json(['message' => 'Item berhasil dihapus']);
    }

    public function import(Request $request)
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:csv,txt', 'max:2048'],
        ]);

        $file = $request->file('file');
        $path = $file->getRealPath();
        $handle = fopen($path, 'r');
        if (!$handle) {
            return response()->json(['message' => 'Tidak dapat membaca file'], 422);
        }

        $headers = fgetcsv($handle);
        if (!$headers) {
            return response()->json(['message' => 'File kosong'], 422);
        }
        $headers = array_map(fn($h) => strtolower(trim($h)), $headers);

        $expected = ['name', 'category', 'description'];
        if (array_diff($expected, $headers)) {
            return response()->json(['message' => 'Header harus: name, category, description'], 422);
        }

        $idx = array_flip($headers);
        $created = 0;
        $updated = 0;
        DB::beginTransaction();
        try {
            while (($row = fgetcsv($handle)) !== false) {
                $name = trim($row[$idx['name']] ?? '');
                $categoryName = trim($row[$idx['category']] ?? '');
                $description = trim($row[$idx['description']] ?? '');
                if ($name === '') {
                    continue;
                }
                $catId = 0;
                if ($categoryName !== '') {
                    $cat = Category::firstOrCreate(['name' => $categoryName], ['parent_id' => 0]);
                    $catId = $cat->id;
                }
                $item = Item::updateOrCreate(
                    ['name' => $name],
                    ['category_id' => $catId, 'description' => $description]
                );
                $item->wasRecentlyCreated ? $created++ : $updated++;
            }
            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json(['message' => 'Gagal import: '.$e->getMessage()], 500);
        } finally {
            fclose($handle);
        }

        return response()->json([
            'message' => 'Import selesai',
            'created' => $created,
            'updated' => $updated,
        ]);
    }
}
