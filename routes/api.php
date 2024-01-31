<?php

use App\Http\Controllers\StellarAuthController;
use App\Http\Controllers\StellarCustomerController;
use App\Http\Middleware\StellarAuthMiddleware;
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
Route::get('auth', [StellarAuthController::class, 'auth']);
Route::post('auth', [StellarAuthController::class, 'auth']);

Route::get('customer', [StellarCustomerController::class, 'customer'])->middleware(StellarAuthMiddleware::class);
Route::put('customer', [StellarCustomerController::class, 'customer'])->middleware(StellarAuthMiddleware::class);
Route::put('customer/verification', [StellarCustomerController::class, 'customer'])->middleware(StellarAuthMiddleware::class);
Route::delete('customer/{account_id}', [StellarCustomerController::class, 'customer'])->middleware(StellarAuthMiddleware::class);

Route::get('/test_stellar_auth', function (Request $request) {
    return $request->input('stellar_auth');
})->middleware(StellarAuthMiddleware::class);

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});
