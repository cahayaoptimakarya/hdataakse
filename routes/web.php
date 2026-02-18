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
use App\Http\Controllers\Admin\JurnalUmumImportController;
use App\Http\Controllers\Admin\LaporanJurnalUmumController;
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

    Route::prefix('keuangan')->as('keuangan.')->group(function () {
        // Divisi
        Route::get('/divisi', [DivisionController::class, 'index'])->name('divisi.index');
        Route::get('/divisi/data', [DivisionController::class, 'data'])->name('divisi.data');
        Route::post('/divisi', [DivisionController::class, 'store'])->name('divisi.store');
        Route::put('/divisi/{division}', [DivisionController::class, 'update'])->name('divisi.update');
        Route::delete('/divisi/{division}', [DivisionController::class, 'destroy'])->name('divisi.destroy');
        Route::post('/divisi/import', [DivisionController::class, 'importSubDivisi'])->name('divisi.import');

        // Sub Divisi
        Route::get('/sub-divisi', [DivisionController::class, 'index'])->name('sub-divisi.index');
        Route::get('/sub-divisi/data', [DivisionController::class, 'subData'])->name('sub-divisi.data');
        Route::post('/sub-divisi', [DivisionController::class, 'storeSub'])->name('sub-divisi.store');
        Route::put('/sub-divisi/{subDivision}', [DivisionController::class, 'updateSub'])->name('sub-divisi.update');
        Route::delete('/sub-divisi/{subDivision}', [DivisionController::class, 'destroySub'])->name('sub-divisi.destroy');

        // Akun Pembayaran
        Route::get('/akun-pembayaran', [AkunBiayaController::class, 'index'])->name('akun-pembayaran.index');
        Route::get('/akun-pembayaran/data', [AkunBiayaController::class, 'data'])->name('akun-pembayaran.data');
        Route::post('/akun-pembayaran', [AkunBiayaController::class, 'store'])->name('akun-pembayaran.store');
        Route::put('/akun-pembayaran/{akunBiaya}', [AkunBiayaController::class, 'update'])->name('akun-pembayaran.update');
        Route::delete('/akun-pembayaran/{akunBiaya}', [AkunBiayaController::class, 'destroy'])->name('akun-pembayaran.destroy');
        Route::post('/akun-pembayaran/import', [AkunBiayaController::class, 'importAkunPembayaran'])->name('akun-pembayaran.import');

        Route::get('/sub-akun-pembayaran/data', [AkunBiayaController::class, 'subData'])->name('sub-akun-pembayaran.data');
        Route::post('/sub-akun-pembayaran', [AkunBiayaController::class, 'storeSub'])->name('sub-akun-pembayaran.store');
        Route::put('/sub-akun-pembayaran/{subAkunBiaya}', [AkunBiayaController::class, 'updateSub'])->name('sub-akun-pembayaran.update');
        Route::delete('/sub-akun-pembayaran/{subAkunBiaya}', [AkunBiayaController::class, 'destroySub'])->name('sub-akun-pembayaran.destroy');

        // Budget
        Route::get('/budget', [BudgetController::class, 'index'])->name('budget.index');
        Route::get('/budget/data', [BudgetController::class, 'data'])->name('budget.data');
        Route::post('/budget', [BudgetController::class, 'store'])->name('budget.store');
        Route::put('/budget/{budget}', [BudgetController::class, 'update'])->name('budget.update');
        Route::delete('/budget/{budget}', [BudgetController::class, 'destroy'])->name('budget.destroy');

        // Jurnal Umum Import
        Route::get('/jurnal-umum', [JurnalUmumImportController::class, 'index'])->name('jurnal-umum.index');
        Route::post('/jurnal-umum', [JurnalUmumImportController::class, 'store'])->name('jurnal-umum.store');

        // Laporan
        Route::get('/laporan', [LaporanJurnalUmumController::class, 'index'])->name('laporan.index');
        Route::get('/laporan/export', [LaporanJurnalUmumController::class, 'export'])->name('laporan.export');
        Route::get('/laporan/sub-akun/{subAkunBiaya}/jurnal', [LaporanJurnalUmumController::class, 'subAkunJurnal'])->name('laporan.sub-akun-jurnal');
    });
});
