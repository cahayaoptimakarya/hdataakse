<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\UserController as AdminUserController;
use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\Admin\MenuController;
use App\Http\Controllers\Admin\PermissionController;
use App\Http\Controllers\Admin\ImportController;
use App\Http\Controllers\Admin\DataController;
use App\Http\Controllers\Admin\DivisionController;
use App\Http\Controllers\Admin\AkunBiayaController;
use App\Http\Controllers\Admin\BudgetController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// Basic health check route for debugging blank page on '/'
Route::get('/healthz', function () {
    return response('OK', 200);
});

Route::get('/dashboard', [DashboardController::class, 'index'])
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';

// Admin area
Route::middleware(['auth', 'verified', 'menu.permission'])->prefix('admin')->as('admin.')->group(function () {
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

    Route::prefix('import')->as('import.')->group(function () {
        Route::get('/', [ImportController::class, 'index'])->name('index');
        Route::post('/', [ImportController::class, 'store'])->name('store');
        Route::delete('/all', [ImportController::class, 'destroyAll'])->name('destroy');
        Route::post('/instan', [ImportController::class, 'storeInstan'])->name('instan');
        Route::delete('/instan/all', [ImportController::class, 'destroyInstan'])->name('instan.destroy');
        Route::post('/shipments', [ImportController::class, 'storeShipments'])->name('shipments');
        Route::delete('/shipments/all', [ImportController::class, 'destroyShipments'])->name('shipments.destroy');
    });

    Route::get('/data', [DataController::class, 'index'])->name('data.index');
    Route::get('/data/export', [DataController::class, 'export'])->name('data.export');
    Route::get('/data/instan/export', [DataController::class, 'exportInstan'])->name('data.export-instan');
    Route::get('/data/unintegrated/export', [DataController::class, 'exportUnintegrated'])->name('data.export-unintegrated');

    Route::prefix('masterdata')->as('masterdata.')->group(function () {
        // Users DataTables
        Route::get('/users/data', [AdminUserController::class, 'data'])->name('users.data');
        // Users CRUD
        Route::resource('users', AdminUserController::class)->except(['show'])->names('users');

        // Roles DataTables
        Route::get('/roles/data', [RoleController::class, 'data'])->name('roles.data');
        // Roles CRUD
        Route::resource('roles', RoleController::class)->except(['show'])->names('roles');

        // Menus DataTables
        Route::get('/menus/data', [MenuController::class, 'data'])->name('menus.data');
        // Menus CRUD
        Route::resource('menus', MenuController::class)->except(['show'])->names('menus');

        // Categories (inheritance via parent)
        Route::get('/categories/data', [\App\Http\Controllers\Admin\CategoryController::class, 'data'])->name('categories.data');
        Route::resource('categories', \App\Http\Controllers\Admin\CategoryController::class)->except(['create','show','edit'])->names('categories');

        // Divisions & Sub Divisions
        Route::get('/divisions', [DivisionController::class, 'index'])->name('divisions.index');
        Route::get('/divisions/data', [DivisionController::class, 'data'])->name('divisions.data');
        Route::post('/divisions', [DivisionController::class, 'store'])->name('divisions.store');
        Route::put('/divisions/{division}', [DivisionController::class, 'update'])->name('divisions.update');
        Route::delete('/divisions/{division}', [DivisionController::class, 'destroy'])->name('divisions.destroy');

        Route::get('/sub-divisions/data', [DivisionController::class, 'subData'])->name('sub-divisions.data');
        Route::post('/sub-divisions', [DivisionController::class, 'storeSub'])->name('sub-divisions.store');
        Route::put('/sub-divisions/{subDivision}', [DivisionController::class, 'updateSub'])->name('sub-divisions.update');
        Route::delete('/sub-divisions/{subDivision}', [DivisionController::class, 'destroySub'])->name('sub-divisions.destroy');

        // Akun Biaya & Sub Akun Biaya
        Route::get('/akun-biaya', [AkunBiayaController::class, 'index'])->name('akun-biaya.index');
        Route::get('/akun-biaya/data', [AkunBiayaController::class, 'data'])->name('akun-biaya.data');
        Route::post('/akun-biaya', [AkunBiayaController::class, 'store'])->name('akun-biaya.store');
        Route::put('/akun-biaya/{akunBiaya}', [AkunBiayaController::class, 'update'])->name('akun-biaya.update');
        Route::delete('/akun-biaya/{akunBiaya}', [AkunBiayaController::class, 'destroy'])->name('akun-biaya.destroy');

        Route::get('/sub-akun-biaya/data', [AkunBiayaController::class, 'subData'])->name('sub-akun-biaya.data');
        Route::post('/sub-akun-biaya', [AkunBiayaController::class, 'storeSub'])->name('sub-akun-biaya.store');
        Route::put('/sub-akun-biaya/{subAkunBiaya}', [AkunBiayaController::class, 'updateSub'])->name('sub-akun-biaya.update');
        Route::delete('/sub-akun-biaya/{subAkunBiaya}', [AkunBiayaController::class, 'destroySub'])->name('sub-akun-biaya.destroy');

        // Budgets
        Route::get('/budgets', [BudgetController::class, 'index'])->name('budgets.index');
        Route::get('/budgets/data', [BudgetController::class, 'data'])->name('budgets.data');
        Route::post('/budgets', [BudgetController::class, 'store'])->name('budgets.store');
        Route::put('/budgets/{budget}', [BudgetController::class, 'update'])->name('budgets.update');
        Route::delete('/budgets/{budget}', [BudgetController::class, 'destroy'])->name('budgets.destroy');

        // Items
        Route::get('/items/data', [\App\Http\Controllers\Admin\ItemController::class, 'data'])->name('items.data');
        Route::resource('items', \App\Http\Controllers\Admin\ItemController::class)->except(['create','show','edit'])->names('items');
        Route::post('/items/import', [\App\Http\Controllers\Admin\ItemController::class, 'import'])->name('items.import');

        // Stores
        Route::get('/stores/data', [\App\Http\Controllers\Admin\StoreController::class, 'data'])->name('stores.data');
        Route::resource('stores', \App\Http\Controllers\Admin\StoreController::class)->except(['create','show','edit'])->names('stores');

        // Permissions management
        Route::get('/permissions', [PermissionController::class, 'index'])->name('permissions.index');
        Route::get('/permissions/{role}/edit', [PermissionController::class, 'edit'])->name('permissions.edit');
        Route::put('/permissions/{role}', [PermissionController::class, 'update'])->name('permissions.update');
    });
});
