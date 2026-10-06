<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

// Halaman awal bawaan Laravel (welcome); sebenarnya tertimpa oleh route '/' ke HomeController di bawah
Route::get('/', function () {
    return view('welcome');
});

// Mendaftarkan route autentikasi bawaan laravel/ui: login, logout, register, reset password
Auth::routes();

// /home dan / sama-sama mengarah ke HomeController@index dengan nama route 'home'
Route::get('/home', [App\Http\Controllers\HomeController::class, 'index'])->name('home');
Route::get('/', [App\Http\Controllers\HomeController::class, 'index'])->name('home');
// Semua route di dalam grup ini wajib login (middleware auth); guest dialihkan ke halaman login
Route::middleware(['auth'])->group(function () {
    // Semua URL modul barang diawali /master-items
    Route::prefix('master-items')->group(function () {
        // GET /master-items : halaman daftar barang
        Route::get('/', [App\Http\Controllers\MasterItemsController::class, 'index']);
        // GET /master-items/search : data JSON untuk DataTables; throttle:search = pembatasan frekuensi request
        Route::get('/search', [App\Http\Controllers\MasterItemsController::class, 'search'])->middleware('throttle:search');
        // GET /master-items/foto/{filename} : menyajikan foto privat hanya untuk user login
        Route::get('/foto/{filename}', [App\Http\Controllers\MasterItemsController::class, 'showFoto'])->middleware('throttle:foto');
        // GET form tambah/ubah; {method} = new atau edit, {id?} = opsional (hanya untuk edit)
        Route::get('/form/{method}/{id?}', [App\Http\Controllers\MasterItemsController::class, 'formView']);
        // POST menyimpan form tambah/ubah; throttle:write membatasi request tulis
        Route::post('/form/{method}/{id?}', [App\Http\Controllers\MasterItemsController::class, 'formSubmit'])->middleware('throttle:write');
        // GET detail barang berdasarkan kode
        Route::get('/view/{kode}', [App\Http\Controllers\MasterItemsController::class, 'singleView']);
        // DELETE hapus barang (soft delete); form HTML memakai POST + @method('DELETE')
        Route::delete('/delete/{id}', [App\Http\Controllers\MasterItemsController::class, 'delete'])->middleware('throttle:write');
        // GET mengacak seluruh data barang (alat demo; berisiko karena mengubah data lewat GET)
        Route::get('/update-random-data', [App\Http\Controllers\MasterItemsController::class, 'updateRandomData']);
    });

    // Semua URL modul kategori diawali /category
    Route::prefix('category')->group(function () {
        // GET /category : halaman daftar kategori
        Route::get('/', [\App\Http\Controllers\CategoryController::class, 'index']);
        // GET /category/search : data JSON untuk DataTables
        Route::get('/search', [\App\Http\Controllers\CategoryController::class, 'search'])->middleware('throttle:search');
        // GET form tambah/ubah kategori; where() membatasi {method} hanya new atau edit
        Route::get('/form/{method}/{id?}', [\App\Http\Controllers\CategoryController::class, 'formView'])->where('method', 'new|edit');
        // POST menyimpan form kategori
        Route::post('/form/{method}/{id?}', [\App\Http\Controllers\CategoryController::class, 'formSubmit'])->where('method', 'new|edit')->middleware('throttle:write');
        // GET detail kategori beserta daftar barangnya
        Route::get('/view/{id}', [\App\Http\Controllers\CategoryController::class, 'singleView']);
        // DELETE hapus kategori (soft delete)
        Route::delete('/delete/{id}', [\App\Http\Controllers\CategoryController::class, 'delete'])->middleware('throttle:write');
    });

    // Export berbasis queue (Excel & PDF)
    Route::prefix('exports')->group(function () {
        // POST meminta export Excel; dibatasi throttle:export_limit karena berat
        Route::post('/master-items-excel', [\App\Http\Controllers\ReportExportController::class, 'storeMasterItemsExcel'])->middleware('throttle:export_limit');
        // POST meminta export PDF satu kategori; dibatasi throttle:pdf_limit
        Route::post('/category-pdf/{id}', [\App\Http\Controllers\ReportExportController::class, 'storeCategoryPdf'])->middleware('throttle:pdf_limit');
        // GET cek status export (polling); {export} di-bind otomatis lewat UUID ke model ReportExport
        Route::get('/{export}/status', [\App\Http\Controllers\ReportExportController::class, 'status'])->middleware('throttle:search');
        // GET unduh file export (hanya milik user yang meminta)
        Route::get('/{export}/download', [\App\Http\Controllers\ReportExportController::class, 'download'])->middleware('throttle:foto');
    });
});

// Route cadangan untuk URL yang tidak dikenal
Route::fallback(function () {
    // Sudah login: kembali ke halaman home
    if (auth()->check()) {
        return redirect()->route('home');
    }
    // Belum login: arahkan ke halaman login
    return redirect()->route('login');
});
