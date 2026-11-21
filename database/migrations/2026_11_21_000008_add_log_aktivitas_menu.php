<?php

use App\Support\MenuStructure;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/** Tambah menu Log Aktivitas di grup Pengaturan (admin langsung dapat akses). */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('menus') && DB::table('menus')->exists()) {
            MenuStructure::apply();
        }
    }

    public function down(): void
    {
        DB::table('menus')->where('slug', 'log-aktivitas')->delete();
    }
};
