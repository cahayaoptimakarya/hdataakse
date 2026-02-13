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

        $parent = DB::table('menus')->where('slug', 'master-data')->first();

        $menuRows = [
            ['name' => 'Divisions', 'slug' => 'divisions', 'route' => 'admin.masterdata.divisions.index', 'icon' => 'fa-solid fa-diagram-project', 'sort_order' => 24],
            ['name' => 'Akun Biaya', 'slug' => 'akun-biaya', 'route' => 'admin.masterdata.akun-biaya.index', 'icon' => 'fa-solid fa-wallet', 'sort_order' => 25],
            ['name' => 'Budgets', 'slug' => 'budgets', 'route' => 'admin.masterdata.budgets.index', 'icon' => 'fa-solid fa-sack-dollar', 'sort_order' => 26],
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
            ->whereIn('slug', ['sub-divisions', 'sub-akun-biaya'])
            ->update([
                'is_active' => false,
                'updated_at' => now(),
            ]);

        // Grant admin full permissions to the new menus
        $adminRole = DB::table('roles')->where('slug', 'admin')->first();
        if ($adminRole) {
            $menus = DB::table('menus')->whereIn('slug', collect($menuRows)->pluck('slug'))->get();
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
