<?php

use App\Http\Controllers\StellarAuthController;
use App\Http\Controllers\StellarCustomerController;
use App\Http\Controllers\StellarInteractiveFlowController;
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

// SEP-10
Route::get('auth', [StellarAuthController::class, 'auth']);
Route::post('auth', [StellarAuthController::class, 'auth']);

Route::get('/test_stellar_auth', function (Request $request) {
    return $request->input('stellar_auth');
})->middleware(StellarAuthMiddleware::class);


// SEP-12
Route::get('customer', [StellarCustomerController::class, 'customer'])->middleware(StellarAuthMiddleware::class);
Route::put('customer', [StellarCustomerController::class, 'customer'])->middleware(StellarAuthMiddleware::class);
Route::put('customer/verification', [StellarCustomerController::class, 'customer'])->middleware(StellarAuthMiddleware::class);
Route::delete('customer/{account_id}', [StellarCustomerController::class, 'customer'])->middleware(StellarAuthMiddleware::class);

// SEP-24
Route::get('sep24/info', [StellarInteractiveFlowController::class, 'interactive']);
Route::get('sep24/fee', [StellarInteractiveFlowController::class, 'interactive']);
Route::post('sep24/transactions/deposit/interactive', [StellarInteractiveFlowController::class, 'interactive'])->middleware(StellarAuthMiddleware::class);
Route::post('sep24/transactions/withdraw/interactive', [StellarInteractiveFlowController::class, 'interactive'])->middleware(StellarAuthMiddleware::class);
Route::get('sep24/transaction', [StellarInteractiveFlowController::class, 'interactive'])->middleware(StellarAuthMiddleware::class);
Route::get('sep24/transactions', [StellarInteractiveFlowController::class, 'interactive'])->middleware(StellarAuthMiddleware::class);

// other
Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});
