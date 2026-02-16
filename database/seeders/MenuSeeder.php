<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class MenuSeeder extends Seeder
{
    public function run(): void
    {
        // Ensure Master Data parent exists
        DB::table('menus')->updateOrInsert(
            ['slug' => 'master-data'],
            [
                'name' => 'Master Data',
                'route' => null,
                'icon' => 'fa-solid fa-database',
                'parent_id' => null,
                'sort_order' => 10,
                'is_active' => true,
                'updated_at' => now(),
                'created_at' => now(),
            ]
        );

        DB::table('menus')->updateOrInsert(
            ['slug' => 'keuangan'],
            [
                'name' => 'Keuangan',
                'route' => null,
                'icon' => 'fa-solid fa-coins',
                'parent_id' => null,
                'sort_order' => 15,
                'is_active' => true,
                'updated_at' => now(),
                'created_at' => now(),
            ]
        );

        // Rename legacy slugs if they exist
        DB::table('menus')->where('slug', 'divisions')->update(['slug' => 'divisi']);
        DB::table('menus')->where('slug', 'sub-divisions')->update(['slug' => 'sub-divisi']);
        DB::table('menus')->where('slug', 'akun-biaya')->update(['slug' => 'akun-pembayaran']);
        DB::table('menus')->where('slug', 'budgets')->update(['slug' => 'budget']);

        $parent = DB::table('menus')->where('slug', 'keuangan')->first();

        $menuRows = [
            ['name' => 'Divisi', 'slug' => 'divisi', 'route' => 'admin.keuangan.divisi.index', 'icon' => 'fa-solid fa-diagram-project', 'sort_order' => 0],
            ['name' => 'Sub Divisi', 'slug' => 'sub-divisi', 'route' => 'admin.keuangan.sub-divisi.index', 'icon' => 'fa-solid fa-sitemap', 'sort_order' => 1],
            ['name' => 'Akun Pembayaran', 'slug' => 'akun-pembayaran', 'route' => 'admin.keuangan.akun-pembayaran.index', 'icon' => 'fa-solid fa-wallet', 'sort_order' => 2],
            ['name' => 'Budget', 'slug' => 'budget', 'route' => 'admin.keuangan.budget.index', 'icon' => 'fa-solid fa-sack-dollar', 'sort_order' => 3],
            ['name' => 'Jurnal Umum', 'slug' => 'jurnal-umum', 'route' => 'admin.keuangan.jurnal-umum.index', 'icon' => 'fa-solid fa-book', 'sort_order' => 4],
            ['name' => 'Laporan Jurnal Umum', 'slug' => 'laporan-jurnal-umum', 'route' => 'admin.keuangan.laporan-jurnal-umum.index', 'icon' => 'fa-solid fa-chart-column', 'sort_order' => 5],
        ];

        foreach ($menuRows as $menu) {
            DB::table('menus')->updateOrInsert(
                ['slug' => $menu['slug']],
                [
                    'name' => $menu['name'],
                    'route' => $menu['route'],
                    'icon' => $menu['icon'],
                    'parent_id' => $parent?->id,
                    'sort_order' => $menu['sort_order'],
                    'is_active' => true,
                    'updated_at' => now(),
                    'created_at' => now(),
                ]
            );
        }

        DB::table('menus')
            ->whereIn('slug', ['sub-akun-biaya', 'sub-divisions', 'akun-biaya', 'budgets', 'divisions'])
            ->update([
                'is_active' => false,
                'updated_at' => now(),
            ]);

        // Grant admin full permissions to the new menus
        $adminRole = DB::table('roles')->where('slug', 'admin')->first();
        if ($adminRole) {
            $grantSlugs = collect($menuRows)->pluck('slug')->push('keuangan')->values();
            $menus = DB::table('menus')->whereIn('slug', $grantSlugs)->get();
            foreach ($menus as $m) {
                DB::table('permission_menu')->updateOrInsert(
                    ['role_id' => $adminRole->id, 'menu_id' => $m->id],
                    [
                        'can_view' => true,
                        'can_create' => true,
                        'can_update' => true,
                        'can_delete' => true,
                        'updated_at' => now(),
                        'created_at' => now(),
                    ]
                );
            }
        }
    }
}
