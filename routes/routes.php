<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\SystemController;

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

Route::get('/', [SystemController::class, 'info'])->middleware(['auth.jwt:team']);

Route::apiResource('users', UserController::class);

Route::prefix('auth')->group(function () {
    Route::get('', [AuthController::class, 'getAuth'])->middleware(['auth.jwt']);
    Route::post('token', [AuthController::class, 'getToken']);
    Route::post('register', [AuthController::class, 'store']);
    Route::get('token', [AuthController::class, 'verifyToken'])->middleware(['auth.jwt']);
    Route::prefix('admin')->group(function () {
        Route::post('token', [AuthController::class, 'getTokenAdmin']);
        Route::get('token', [AuthController::class, 'verifyTokenAdmin'])->middleware(['auth.jwt']);
    });
});
