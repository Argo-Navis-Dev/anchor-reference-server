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

Route::get('/', function () {
    return view('home');
});

Route::prefix('.well-known')->group(function () {
    Route::get('/stellar.toml', [\App\Http\Controllers\StellarTomlController::class, 'toml']);
});

/*
Route::get('.well-known/stellar.toml', function () {
    $filePath = storage_path('app/stellar.toml'); // Adjust the file path as needed
    if (file_exists($filePath)) {
        $contents = file_get_contents($filePath);
        return response($contents, 200)
            ->header('Content-Type', 'text/plain');
    } else {
        return response('File not found', 404);
    }
});
*/
