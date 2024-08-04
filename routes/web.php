<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

//Welcome page for the Anchor Reference Server
/*Route::get('/', function () {
    return view('welcome');
});*/

//SEP-01
Route::prefix('.well-known')->group(function () {
    Route::get('/stellar.toml', [\App\Http\Controllers\StellarTomlController::class, 'toml']);
});

//SEP-12 demo
Route::get('/sep12demo', function () {
    return view('sep12demo');
})->name('sep12demo');

//Renders the customer (binary) image field
Route::get('/customer/{id}/binary-field/{fieldID}', [App\Http\Controllers\StellarCustomerController::class, 'renderBinaryField']);
