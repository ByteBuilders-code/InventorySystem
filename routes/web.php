<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ProductController;

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

// Route::post('/inventario/actualizar', [ProductController::class, 'updateStock']);
// Route::get('/inventario/{id}/editar', [ProductController::class, 'edit']);
// Route::put('/inventario/{id}', [ProductController::class, 'update']);
// Route::delete('/inventario/{id}', [ProductController::class, 'destroy']);
// Route::get('/inventario/crear', [ProductController::class, 'create']);
// Route::post('/inventario/crear', [ProductController::class, 'store']);

Route::get('/scanner', function () {
    return view('scanner');
});