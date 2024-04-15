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
Route::get('/', function () {
    return view('welcome');
});

//SEP-01
Route::prefix('.well-known')->group(function () {
    Route::get('/stellar.toml', [\App\Http\Controllers\StellarTomlController::class, 'toml']);
});

//SEP-12 demo
Route::get('/sep12demo', function () {
    return view('sep12demo');
})->name('sep12demo');

// Admin dashboard
Auth::routes(['register' => false]);

View::composer('*', function($view){
    View::share('view_name', $view->getName());
}); 

//Renders the home page of the dashboard
Route::get('/home', [App\Http\Controllers\Admin\HomeController::class, 'index'])->name('home.index');
//Renders the users page
Route::get('/users', [App\Http\Controllers\Admin\UserController::class, 'index'])->name('users.index');
//Loads the users data as a JSON array
Route::get('/load-users', [App\Http\Controllers\Admin\UserController::class, 'loadUsers']);
//Renders one specific user  
Route::get('/user/{id}', [App\Http\Controllers\Admin\UserController::class, 'show'])->name('user.index');
//Stores the user data
Route::post('/user/${id}', [App\Http\Controllers\Admin\UserController::class, 'store'])->name('user.store');
//Deletes the user
Route::delete('/user', [App\Http\Controllers\Admin\UserController::class, 'destroy']);

//Renders the customer (binary) image field
Route::get('/customer/{id}/binary-field/{fieldID}', [App\Http\Controllers\Admin\CustomerController::class, 'getBinaryField']);
//Renders the customers
Route::get('/customers', [App\Http\Controllers\Admin\CustomerController::class, 'index'])->name('customers.index');
//Loads the customers data as a JSON array
Route::get('/load-customers', [App\Http\Controllers\Admin\CustomerController::class, 'loadCustomers']);
//Deletes the customer
Route::delete('/customer', [App\Http\Controllers\Admin\CustomerController::class, 'destroy']);
//Renders the customer
Route::get('/customer/{id}', [App\Http\Controllers\Admin\CustomerController::class, 'show'])->name('customer.index');
//Stores the customer data
Route::post('/customer/${id}', [App\Http\Controllers\Admin\CustomerController::class, 'store'])->name('customer.store');