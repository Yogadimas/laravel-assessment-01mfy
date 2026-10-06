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

// Route Publik (Hanya Home/Dashboard)
Route::get('/', [App\Http\Controllers\HomeController::class, 'index'])->name('home');
Route::get('/home', [App\Http\Controllers\HomeController::class, 'index'])->name('home');

// Route Privat (Modul Master Items -> Wajib Login)
Route::middleware(['auth'])->group(function () {
    Route::get('/master-items', [App\Http\Controllers\MasterItemsController::class, 'index']);
    Route::get('/master-items/search', [App\Http\Controllers\MasterItemsController::class, 'search']);
    Route::get('/master-items/view/{kode}', [App\Http\Controllers\MasterItemsController::class, 'singleView']);
    Route::get('/master-items/form/{method}/{id?}', [App\Http\Controllers\MasterItemsController::class, 'formView']);
    Route::post('/master-items/form/{method}/{id?}', [App\Http\Controllers\MasterItemsController::class, 'formSubmit']);

    // Pastikan delete memakai method DELETE, bukan GET
    Route::delete('/master-items/delete/{id}', [App\Http\Controllers\MasterItemsController::class, 'delete']);

    Route::get('/master-items/update-random-data', [App\Http\Controllers\MasterItemsController::class, 'updateRandomData']);


    Route::get('/category', [\App\Http\Controllers\CategoryController::class, 'index']);
    Route::get('/category/search', [\App\Http\Controllers\CategoryController::class, 'search']);
    Route::get('/category/form/{method}/{id?}', [\App\Http\Controllers\CategoryController::class, 'formView'])->where('method', 'new|edit');
    Route::post('/category/form/{method}/{id?}', [\App\Http\Controllers\CategoryController::class, 'formSubmit'])->where('method', 'new|edit');
    Route::get('/category/view/{id}', [\App\Http\Controllers\CategoryController::class, 'singleView']);
    Route::get('/category/view/{id}/pdf', [\App\Http\Controllers\CategoryController::class, 'printView']);
    Route::delete('/category/delete/{id}', [\App\Http\Controllers\CategoryController::class, 'delete']);
});
