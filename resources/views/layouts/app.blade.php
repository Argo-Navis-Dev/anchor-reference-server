<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- CSRF Token -->
    <meta name="csrf-token" content="{{ csrf_token() }}">
    
    
    <title>{{ config('app.name', 'Anchor Reference Server') }}</title>

    <!-- Fonts -->
    <link rel="dns-prefetch" href="//fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=Nunito" rel="stylesheet">
    <link href="https://maxcdn.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css" rel="stylesheet" />

    
    @vite(['resources/sass/app.scss', 'resources/js/app.js'])
    
</head>

<body>
    <div id="app">
        <nav class="navbar navbar-expand-md navbar-light bg-white shadow-sm">
            <div class="container">                    
                <a class="navbar-brand text-secondary" href="{{ route('admin_home') }}">
                    <i class="fa fa-anchor"></i> 
                    {{ config('app.name', 'Anchor Reference Server') }}
                </a>
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse"
                    data-bs-target="#navbarSupportedContent" aria-controls="navbarSupportedContent"
                    aria-expanded="false" aria-label="{{ __('Toggle navigation') }}">
                    <span class="navbar-toggler-icon"></span>
                </button>

                <div class="collapse navbar-collapse" id="navbarSupportedContent">
                    <!-- Left Side Of Navbar -->
                    <ul class="navbar-nav me-auto">
                        @auth                        
                        <li class="nav-item">                            
                            <a class="nav-link text-primary" href="{{ route('admin_users') }}"><i class="fa fa-user-circle"></i><span class = "ms-1">MANAGE USERS</span></a>
                        </li>

                        <li class="nav-item">
                            <a class="nav-link text-primary" href="{{ route('admin_customers') }}"><i class="fa fa-user-md"></i><span class = "ms-1">MANAGE CUSTOMERS</span></a>
                        </li>
                        @endauth
                    </ul>

                    <!-- Right Side Of Navbar -->
                    <ul class="navbar-nav ms-auto">
                        <!-- Authentication Links -->
                        @guest
                        @if (Route::has('login'))
                        <li class="nav-item">
                            <a class="nav-link" href="{{ route('login') }}">{{ __('Login') }}</a>
                        </li>
                        @endif

                        @if (Route::has('register'))
                        <li class="nav-item">
                            <a class="nav-link" href="{{ route('register') }}">{{ __('Register') }}</a>
                        </li>
                        @endif
                        @else
                        <li class="nav-item dropdown">
                            <a id="navbarDropdown" class="nav-link dropdown-toggle" href="#" role="button"
                                data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false" v-pre>
                                {{ Auth::user()->name }}
                            </a>

                            <div class="dropdown-menu dropdown-menu-end" aria-labelledby="navbarDropdown">
                                <a class="dropdown-item" href="{{ route('logout') }}" onclick="event.preventDefault();
                                                     document.getElementById('logout-form').submit();">
                                    {{ __('Logout') }}
                                </a>

                                <form id="logout-form" action="{{ route('logout') }}" method="POST" class="d-none">
                                    @csrf
                                </form>
                            </div>
                        </li>
                        @endguest
                    </ul>
                </div>
            </div>
        </nav>

        <main class="py-4">
            @yield('content')
        </main>
    </div>

    <!-- Generic info dialog -->
    <div id = "info-dialog" class="modal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Info</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">                
            </div>
            <div class="modal-footer">            
                <button type="button" data-bs-dismiss="modal" class="btn btn-primary">OK</button>
            </div>
            </div>
        </div>
    </div>

    <!-- Generic info dialog -->
    <div id = "info-dialog" class="modal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Info</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">                
            </div>
            <div class="modal-footer">            
                <button type="button" data-bs-dismiss="modal" class="btn btn-primary">OK</button>
            </div>
            </div>
        </div>
    </div>

    <!-- Generic YesNo dialog -->
    <div id = "yesno-dialog" class="modal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Question</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">                
            </div>
            <div class="modal-footer">            
                <button data-modal-btn = "yes" type="button"  class="btn btn-secondary btn-yes">Yes</button>
                <button data-modal-btn = "no" type="button" data-bs-dismiss="modal"  class="btn btn-primary btn-no">No</button>
            </div>
            </div>
        </div>
    </div>

    <!-- Loading overlay -->
    <div id="loading-overlay" class="overlay">
        <div class="spinner"></div>
    </div>    
</body>
</html>