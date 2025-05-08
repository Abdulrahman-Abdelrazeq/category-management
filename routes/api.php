<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\CategoryController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/


Route::apiResource('categories', CategoryController::class);

Route::prefix('categories-api')->controller(CategoryController::class)->group(function () {
    Route::post('/delete-multiple', 'destroyMultiple');
    Route::get('/tree', 'tree');
});
