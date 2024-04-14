<?php

declare(strict_types=1);

// Copyright 2024 Argo Navis Dev. All rights reserved.
// Use of this source code is governed by a license that can be.
// found in the LICENSE file.

namespace App\Http\Controllers\Admin;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Models\User; 

/**
 * Controller for administering the users.
 */
class UserController extends Controller
{
    /**
     * Create a new controller instance.
     * The auth middleware is used to authenticate the user.
     * This controller can be accessed exclusively by authenticated users.
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth');
    }
        
    /**
     * Load the passed user data by ID.
     *
     * @param string $id The ID of the user to be shown.
     * @return \Illuminate\View\View The user page: user.blade.php
     */
    public function show(string $id) 
    {
        LOG::debug('Accessing user page, ID: ' . $id);
        $user = User::find($id); // Load the user by id
        if (!$user) {
            Log::debug('User not found, creating new one.');
            $user = new User();            
        }
        return view('/admin/user', ['user' => $user]); 
    }

    /**
     * Renders the admin users page.
     * @return \Illuminate\View\View The admin users page: users.blade.php
     */
    public function index()
    {
        Log::debug('Accessing the users page.');
        $users = User::all(); // Select all users from the database
        return view('/admin/users', ['users' => $users]); // Pass the users model to the view
    }
    
    /**
     * Load the users data as a JSON array.
     * @return \Illuminate\Http\JsonResponse The admin users data as a JSON array
     */
    public function loadUsers()
    {
        Log::debug('Loading all users.');
        $users = User::all(); // Select all users from the database        
        return response()->json($users, 200);
    }

    /**
     * Deletes the passed user.
     *
     * @param  Request  $request The request object
     * @return \Illuminate\View\View The admin users page: users.blade.php
     */
    public function destroy(Request $request)
    {
        $id = $request->input('id');
        Log::debug('Deleting user by id: ' . $id);        
        $user = User::find($id); // Find the user by id
        if ($user) {
            if ($user->id == auth()->user()->id) {
                Log::error('The currently logged in user can\'t be deleted!');
                return response()->json(['success' => 'false','error' => 'The currently logged in user can\'t be deleted!'], 400);
            }
            $user->delete(); // Delete the user
            Log::debug('The user has been deleted successfully!');
            return response()->json(['success' => true, 'message' => 'The user has been deleted successfully'], 200);
        } else {
            Log::debug('User not found!');
            return response()->json(['success' => 'false','error' => 'User not found!'], 404);
        }       
    }

    /**
     * Update or creates the user with the passed data.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  string  $id
     * @return \Illuminate\View\View The admin user page: user.blade.php
     */
    public function store(Request $request, string $id)
    {
        LOG::debug('Updating/creating user, passed id: ' . $id);
        $user = User::find($id); 
        $isNew = false;
        if (!$user) {
            Log::debug('User not found, creating new one.');
            $user = new User();
            $isNew = true;
        }
        //Validation
        $fieldsToValidate = [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255'        
        ];
        if ($request->has('password') && !empty($request->input('password'))) {
            $fieldsToValidate['password'] = 'required|string|min:8|confirmed';
        }        
        $request->validate($fieldsToValidate);

        $user->name = $request->input('name');
        $user->email = $request->input('email');
        if ($request->has('password') && !empty($request->input('password'))) {            
            $user->password = bcrypt($request->input('password')); // Hash the password
        }        
        $user->save();        
        $isNew ? $msg = "The user has been created successfully!" : $msg = "The user has been updated successfully!";
        Log::debug($msg);
        return view('/admin/user', ['user' => $user, 'success' => $msg]); // Pass the user to the view
    }
}