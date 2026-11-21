<?php

use App\Support\MenuStructure;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Susun ulang menu sidebar: Dashboard, Transaksi, Anggaran, Master Data, Pengaturan.
 */
return new class extends Migration
{
    public function up(): void
    {
        // Hanya untuk instalasi yang sudah punya menu; instalasi baru memakai MenuSeeder.
        if (Schema::hasTable('menus') && DB::table('menus')->exists()) {
            MenuStructure::apply();
        }
    }

    public function down(): void
    {
        // Susunan menu lama tidak dikembalikan; hak akses per menu tidak berubah.
    }
};
