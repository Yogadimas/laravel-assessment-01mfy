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
        Route::get('/search', [App\Http\Controllers\MasterItemsController::class, 'search']);
        Route::get('/excel', [App\Http\Controllers\MasterItemsController::class, 'downloadExcel']);
        Route::get('/foto/{filename}', [App\Http\Controllers\MasterItemsController::class, 'showFoto']);
        Route::get('/form/{method}/{id?}', [App\Http\Controllers\MasterItemsController::class, 'formView']);
        Route::post('/form/{method}/{id?}', [App\Http\Controllers\MasterItemsController::class, 'formSubmit']);
        Route::get('/view/{kode}', [App\Http\Controllers\MasterItemsController::class, 'singleView']);
        Route::delete('/delete/{id}', [App\Http\Controllers\MasterItemsController::class, 'delete']);
        Route::get('/update-random-data', [App\Http\Controllers\MasterItemsController::class, 'updateRandomData']);
    });

    Route::prefix('category')->group(function () {
        Route::get('/', [\App\Http\Controllers\CategoryController::class, 'index']);
        Route::get('/search', [\App\Http\Controllers\CategoryController::class, 'search']);
        Route::get('/form/{method}/{id?}', [\App\Http\Controllers\CategoryController::class, 'formView'])->where('method', 'new|edit');
        Route::post('/form/{method}/{id?}', [\App\Http\Controllers\CategoryController::class, 'formSubmit'])->where('method', 'new|edit');
        Route::get('/view/{id}', [\App\Http\Controllers\CategoryController::class, 'singleView']);
        Route::get('/view/{id}/pdf', [\App\Http\Controllers\CategoryController::class, 'printView']);
        Route::delete('/delete/{id}', [\App\Http\Controllers\CategoryController::class, 'delete']);
    });
});
