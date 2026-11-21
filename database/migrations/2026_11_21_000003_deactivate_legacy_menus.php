<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Menyembunyikan menu modul lama (scan resi, data, kategori/item/toko barang).
 * Tabel datanya TIDAK dihapus di sini.
 */
return new class extends Migration
{
    private array $slugs = ['import', 'data', 'categories', 'items', 'stores'];

    public function up(): void
    {
        if (!Schema::hasTable('menus')) {
            return;
        }
        DB::table('menus')->whereIn('slug', $this->slugs)->update(['is_active' => false, 'updated_at' => now()]);
    }

    public function down(): void
    {
        if (!Schema::hasTable('menus')) {
            return;
        }
        DB::table('menus')->whereIn('slug', $this->slugs)->update(['is_active' => true, 'updated_at' => now()]);
    }
};
