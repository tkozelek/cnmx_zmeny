<?php

use App\Http\Controllers\ShiftController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

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

// Shifts API
Route::get('/shifts', [ShiftController::class, 'index']);
Route::post('/shifts/bulk', [ShiftController::class, 'upsert']);

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});
