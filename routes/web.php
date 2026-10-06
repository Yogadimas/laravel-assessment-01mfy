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

Route::get('/', function () {
    return view('welcome');
});

Auth::routes();

Route::get('/home', [App\Http\Controllers\HomeController::class, 'index'])->name('home');
Route::get('/', [App\Http\Controllers\HomeController::class, 'index'])->name('home');
Route::middleware(['auth'])->group(function () {
    Route::prefix('master-items')->group(function () {
        Route::get('/', [App\Http\Controllers\MasterItemsController::class, 'index']);
        Route::get('/search', [App\Http\Controllers\MasterItemsController::class, 'search'])->middleware('throttle:search');
        Route::get('/foto/{filename}', [App\Http\Controllers\MasterItemsController::class, 'showFoto'])->middleware('throttle:foto');
        Route::get('/form/{method}/{id?}', [App\Http\Controllers\MasterItemsController::class, 'formView']);
        Route::post('/form/{method}/{id?}', [App\Http\Controllers\MasterItemsController::class, 'formSubmit'])->middleware('throttle:write');
        Route::get('/view/{kode}', [App\Http\Controllers\MasterItemsController::class, 'singleView']);
        Route::delete('/delete/{id}', [App\Http\Controllers\MasterItemsController::class, 'delete'])->middleware('throttle:write');
        Route::get('/update-random-data', [App\Http\Controllers\MasterItemsController::class, 'updateRandomData']);
    });

    Route::prefix('category')->group(function () {
        Route::get('/', [\App\Http\Controllers\CategoryController::class, 'index']);
        Route::get('/search', [\App\Http\Controllers\CategoryController::class, 'search'])->middleware('throttle:search');
        Route::get('/form/{method}/{id?}', [\App\Http\Controllers\CategoryController::class, 'formView'])->where('method', 'new|edit');
        Route::post('/form/{method}/{id?}', [\App\Http\Controllers\CategoryController::class, 'formSubmit'])->where('method', 'new|edit')->middleware('throttle:write');
        Route::get('/view/{id}', [\App\Http\Controllers\CategoryController::class, 'singleView']);
        Route::delete('/delete/{id}', [\App\Http\Controllers\CategoryController::class, 'delete'])->middleware('throttle:write');
    });

    // Export berbasis queue (Excel & PDF)
    Route::prefix('exports')->group(function () {
        Route::post('/master-items-excel', [\App\Http\Controllers\ReportExportController::class, 'storeMasterItemsExcel'])->middleware('throttle:export_limit');
        Route::post('/category-pdf/{id}', [\App\Http\Controllers\ReportExportController::class, 'storeCategoryPdf'])->middleware('throttle:pdf_limit');
        Route::get('/{export}/status', [\App\Http\Controllers\ReportExportController::class, 'status'])->middleware('throttle:search');
        Route::get('/{export}/download', [\App\Http\Controllers\ReportExportController::class, 'download'])->middleware('throttle:foto');
    });
});

Route::fallback(function () {
    if (auth()->check()) {
        return redirect()->route('home');
    }
    return redirect()->route('login');
});
