<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;

/**
 * Susunan menu sidebar (Tahap 2). Dipakai oleh migration dan seeder.
 * Menu lama dipindah berdasarkan slug, jadi hak akses per menu tetap.
 */
class MenuStructure
{
    public const GROUPS = [
        ['slug' => 'dashboard', 'name' => 'Dashboard', 'route' => 'admin.dashboard', 'icon' => 'fa-solid fa-gauge-high', 'sort_order' => 0],
        ['slug' => 'transaksi', 'name' => 'Transaksi', 'route' => null, 'icon' => 'fa-solid fa-book', 'sort_order' => 20],
        ['slug' => 'anggaran', 'name' => 'Anggaran', 'route' => null, 'icon' => 'fa-solid fa-sack-dollar', 'sort_order' => 30],
        ['slug' => 'keuangan', 'name' => 'Master Data', 'route' => null, 'icon' => 'fa-solid fa-coins', 'sort_order' => 40],
        ['slug' => 'master-data', 'name' => 'Pengaturan', 'route' => null, 'icon' => 'fa-solid fa-gear', 'sort_order' => 50],
    ];

    /** slug => [parent slug, nama, route, icon, urutan] */
    public const ITEMS = [
        'jurnal-umum' => ['transaksi', 'Jurnal Umum', 'admin.keuangan.jurnal-umum.index', 'fa-solid fa-book-open', 1],
        'budget' => ['anggaran', 'Budget per Periode', 'admin.keuangan.budget.index', 'fa-solid fa-sack-dollar', 1],
        'laporan' => ['anggaran', 'Laporan Realisasi', 'admin.keuangan.laporan.index', 'fa-solid fa-chart-column', 2],
        'divisi' => ['keuangan', 'Divisi & Toko', 'admin.keuangan.divisi.index', 'fa-solid fa-diagram-project', 1],
        'akun-pembayaran' => ['keuangan', 'Akun & Kategori', 'admin.keuangan.akun-pembayaran.index', 'fa-solid fa-wallet', 3],
        'users' => ['master-data', 'Pengguna', 'admin.masterdata.users.index', 'fa-solid fa-users', 1],
        'roles' => ['master-data', 'Peran', 'admin.masterdata.roles.index', 'fa-solid fa-user-shield', 2],
        'permissions' => ['master-data', 'Hak Akses', 'admin.masterdata.permissions.index', 'fa-solid fa-lock', 3],
        'menus' => ['master-data', 'Menu', 'admin.masterdata.menus.index', 'fa-solid fa-bars', 4],
    ];

    public static function apply(): void
    {
        $now = now();
        foreach (self::GROUPS as $g) {
            DB::table('menus')->updateOrInsert(
                ['slug' => $g['slug']],
                ['name' => $g['name'], 'route' => $g['route'], 'icon' => $g['icon'], 'parent_id' => null,
                    'sort_order' => $g['sort_order'], 'is_active' => true, 'updated_at' => $now]
            );
            DB::table('menus')->where('slug', $g['slug'])->whereNull('created_at')->update(['created_at' => $now]);
        }

        $parentIds = DB::table('menus')->whereIn('slug', array_column(self::GROUPS, 'slug'))->pluck('id', 'slug');
        foreach (self::ITEMS as $slug => [$parent, $name, $route, $icon, $sort]) {
            DB::table('menus')->updateOrInsert(
                ['slug' => $slug],
                ['name' => $name, 'route' => $route, 'icon' => $icon, 'parent_id' => $parentIds[$parent] ?? null,
                    'sort_order' => $sort, 'is_active' => true, 'updated_at' => $now]
            );
            DB::table('menus')->where('slug', $slug)->whereNull('created_at')->update(['created_at' => $now]);
        }

        // Halaman Sub Divisi sama dengan halaman Divisi (satu halaman, dua tabel), jadi menunya disembunyikan.
        DB::table('menus')->where('slug', 'sub-divisi')->update(['is_active' => false, 'updated_at' => $now]);

        // Admin selalu punya akses penuh ke semua menu di atas.
        $admin = DB::table('roles')->where('slug', 'admin')->first();
        if ($admin) {
            $ids = DB::table('menus')->whereIn('slug', array_merge(array_column(self::GROUPS, 'slug'), array_keys(self::ITEMS)))->pluck('id');
            foreach ($ids as $id) {
                DB::table('permission_menu')->updateOrInsert(
                    ['role_id' => $admin->id, 'menu_id' => $id],
                    ['can_view' => true, 'can_create' => true, 'can_update' => true, 'can_delete' => true, 'updated_at' => $now, 'created_at' => $now]
                );
            }
        }
    }
}
