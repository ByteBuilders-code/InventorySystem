<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ScannerController;
/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::post('/inventario/actualizar', [ProductController::class, 'updateStock']);
Route::get('/inventario/{id}/editar', [ProductController::class, 'edit']);
Route::put('/inventario/{id}', [ProductController::class, 'update']);
Route::delete('/inventario/{id}', [ProductController::class, 'destroy']);
Route::get('/inventario/crear', [ProductController::class, 'create']);
Route::post('/inventario/crear', [ProductController::class, 'store']);
Route::get('/inventario', [ProductController::class, 'index']);


Route::post('/scanner/iniciar', [ScannerController::class, 'iniciar']);
Route::get('/scanner/ultimo-codigo', [ScannerController::class, 'ultimoCodigo']);
Route::post('/scanner/detener', [ScannerController::class, 'detener']);
