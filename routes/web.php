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

//Home page for the Anchor Reference Server
Route::get('/', function () {
    return view('home');
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
Auth::routes();
//The home page for the admin dashboard
Route::get('/admin-home', [App\Http\Controllers\Admin\AdminHomeController::class, 'index'])->name('admin_home');
//Renders the users
Route::get('/admin-users', [App\Http\Controllers\Admin\AdminUserController::class, 'loadAdminUsers'])->name('admin_users');
//Returns the admin users data as a JSON array
Route::get('/load-admin-users-data', [App\Http\Controllers\Admin\AdminUserController::class, 'loadAdminUsersData']);

//Renders one speicifc user  
Route::get('/admin-user/{id}', [App\Http\Controllers\Admin\AdminUserController::class, 'loadAdminUser'])->name('admin_user');
//Stores the user data
Route::post('/admin-user/${id}', [App\Http\Controllers\Admin\AdminUserController::class, 'updateAdminUser'])->name('update_admin_user');
//Deletes the user
Route::delete('/admin-user', [App\Http\Controllers\Admin\AdminUserController::class, 'deleteAminUser']);
//Retrieves the customer image field to be rendered on the page
Route::get('/admin-customer/{id}/get-customer-img-field/{fieldID}', [App\Http\Controllers\Admin\AdminCustomerController::class, 'getCustomerImgField']);
//Renders the customers
Route::get('/admin-customers', [App\Http\Controllers\Admin\AdminCustomerController::class, 'loadAdminCustomers'])->name('admin_customers');
//Loads the admin customers data as a JSON array
Route::get('/load-admin-customers-data', [App\Http\Controllers\Admin\AdminCustomerController::class, 'loadAdminCustomersData']);

//Deletes the customer
Route::delete('/admin-customer', [App\Http\Controllers\Admin\AdminCustomerController::class, 'deleteAdminCustomer']);
//Renders the customer
Route::get('/admin-customer/{id}', [App\Http\Controllers\Admin\AdminCustomerController::class, 'loadAdminCustomer'])->name('admin_customer');
//Stores the customer data
Route::post('/admin-customer/${id}', [App\Http\Controllers\Admin\AdminCustomerController::class, 'updateAdminCustomer'])->name('update_admin_customer');
