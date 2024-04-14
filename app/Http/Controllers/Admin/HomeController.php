<?php

declare(strict_types=1);

// Copyright 2024 Argo Navis Dev. All rights reserved.
// Use of this source code is governed by a license that can be.
// found in the LICENSE file.

namespace App\Http\Controllers\Admin;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User; // Add this line to import the User model

/**
 * Controller for the dashboard home page.
 */
class HomeController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Shows the dashboard home page: home.blade.php
     * The auth middleware is used to authenticate the user.
     * This controller can be accessed exclusively by authenticated users.
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function index()
    {        
        return view('/admin/home'); // Pass the users to the view
    }
}
