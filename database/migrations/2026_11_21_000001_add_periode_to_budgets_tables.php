<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Budget sekarang berlaku per periode:
 * - tahun wajib diisi
 * - bulan NULL  = budget tahunan (dibagi rata per bulan saat dihitung untuk sebagian tahun)
 * - bulan 1..12 = budget bulanan
 * Budget lama otomatis dianggap budget tahunan untuk tahun saat migration dijalankan.
 */
return new class extends Migration
{
    public function up(): void
    {
        $tahun = (int) now()->year;

        Schema::table('budgets', function (Blueprint $table) {
            $table->unsignedSmallInteger('tahun')->nullable()->after('akun_biaya_id');
            $table->unsignedTinyInteger('bulan')->nullable()->after('tahun');
        });
        DB::table('budgets')->whereNull('tahun')->update(['tahun' => $tahun]);
        Schema::table('budgets', function (Blueprint $table) {
            $table->index(['division_id', 'akun_biaya_id', 'tahun', 'bulan'], 'budgets_periode_idx');
        });

        Schema::table('sub_divisi_budgets', function (Blueprint $table) {
            $table->unsignedSmallInteger('tahun')->nullable()->after('akun_biaya_id');
            $table->unsignedTinyInteger('bulan')->nullable()->after('tahun');
        });
        DB::table('sub_divisi_budgets')->whereNull('tahun')->update(['tahun' => $tahun]);
        Schema::table('sub_divisi_budgets', function (Blueprint $table) {
            // Index baru dibuat dulu (diawali sub_divisi_id) agar foreign key tetap punya index
            // sebelum unique lama dihapus.
            $table->index(['sub_divisi_id', 'akun_biaya_id', 'tahun', 'bulan'], 'sub_divisi_budgets_periode_idx');
        });
        Schema::table('sub_divisi_budgets', function (Blueprint $table) {
            $table->dropUnique(['sub_divisi_id', 'akun_biaya_id']);
        });
    }

    public function down(): void
    {
        Schema::table('sub_divisi_budgets', function (Blueprint $table) {
            $table->unique(['sub_divisi_id', 'akun_biaya_id']);
        });
        Schema::table('sub_divisi_budgets', function (Blueprint $table) {
            $table->dropIndex('sub_divisi_budgets_periode_idx');
            $table->dropColumn(['tahun', 'bulan']);
        });
        Schema::table('budgets', function (Blueprint $table) {
            // Foreign key division_id butuh index sendiri setelah index gabungan dihapus.
            $table->index('division_id', 'budgets_division_id_fk_idx');
        });
        Schema::table('budgets', function (Blueprint $table) {
            $table->dropIndex('budgets_periode_idx');
            $table->dropColumn(['tahun', 'bulan']);
        });
    }
};
