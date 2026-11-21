<?php

namespace App\Support;

use App\Models\Menu;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class Permission
{
    public static function resolveBaseRoute(string $routeName): string
    {
        $base = preg_replace('/\.(create|store|edit|update|destroy|show|data|import)$/', '.index', $routeName);
        return $base;
    }

    public static function actionFromRoute(string $routeName): string
    {
        if (preg_match('/\.(create|store)$/', $routeName)) return 'create';
        if (preg_match('/\.(edit|update)$/', $routeName)) return 'update';
        if (preg_match('/\.(destroy)$/', $routeName)) return 'delete';
        // index, show, data, others default to view
        return 'view';
    }

    public static function can(User $user, string $routeName, ?string $action = null): bool
    {
        $action = $action ?: self::actionFromRoute($routeName);
        $baseRoute = self::resolveBaseRoute($routeName);

        $menu = self::menuForRoute($baseRoute);
        if (!$menu) {
            // If no mapped menu, allow by default to avoid blocking non-menu routes
            return true;
        }

        $roleIds = $user->roles()->pluck('roles.id');
        if ($roleIds->isEmpty()) return false;

        $col = match ($action) {
            'create' => 'can_create',
            'update' => 'can_update',
            'delete' => 'can_delete',
            default => 'can_view',
        };

        return DB::table('permission_menu')
            ->where('menu_id', $menu->id)
            ->whereIn('role_id', $roleIds)
            ->where($col, true)
            ->exists();
    }

    /**
     * Cari menu untuk route. Route turunan (mis. admin.x.jurnal-umum.batches.index)
     * ikut menu induknya (admin.x.jurnal-umum.index) bila tidak punya menu sendiri.
     */
    protected static function menuForRoute(string $baseRoute): ?Menu
    {
        $candidate = $baseRoute;
        while (true) {
            $menu = Menu::where('route', $candidate)->first();
            if ($menu) {
                return $menu;
            }
            $parts = explode('.', $candidate);
            // Minimal "admin.<grup>.index"; berhenti bila sudah tidak bisa naik lagi.
            if (count($parts) <= 3 || end($parts) !== 'index') {
                return null;
            }
            array_splice($parts, -2, 1);
            $candidate = implode('.', $parts);
        }
    }

    public static function viewableMenuIds(User $user)
    {
        $roleIds = $user->roles()->pluck('roles.id');
        if ($roleIds->isEmpty()) return collect();
        return DB::table('permission_menu')
            ->whereIn('role_id', $roleIds)
            ->where('can_view', true)
            ->pluck('menu_id')
            ->unique();
    }
}
