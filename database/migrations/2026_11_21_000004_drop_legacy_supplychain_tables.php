<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Menghapus tabel modul lama aplikasi supply chain (disetujui Ega, 21 Nov 2026).
 * PERHATIAN: data di tabel ini hilang permanen. Backup database sebelum menjalankan migration.
 */
return new class extends Migration
{
    private array $tables = ['items', 'categories', 'stores', 'scan_resi_shipments', 'scan_resi_instan', 'scan_resi'];

    public function up(): void
    {
        Schema::disableForeignKeyConstraints();
        foreach ($this->tables as $table) {
            Schema::dropIfExists($table);
        }
        Schema::enableForeignKeyConstraints();

        if (Schema::hasTable('menus')) {
            $menuIds = DB::table('menus')->whereIn('slug', ['import', 'data', 'categories', 'items', 'stores'])->pluck('id');
            if ($menuIds->isNotEmpty()) {
                if (Schema::hasTable('permission_menu')) {
                    DB::table('permission_menu')->whereIn('menu_id', $menuIds)->delete();
                }
                DB::table('menus')->whereIn('id', $menuIds)->delete();
            }
        }
    }

    public function down(): void
    {
        // Tidak bisa dikembalikan: tabel dan datanya sudah dihapus.
    }
};
