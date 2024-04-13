@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header text-primary">{{ __('WELCOME TO ANCHOR REFERENCE SERVER') }}</div>                                                 
                    <div class="col-md-6 mx-auto">
                            <img class = "img-fluid" src="{{ asset('/img/home.png') }}" alt="PHP Anchor SDK">                        
                    </div>

                    <div class="col-md-12 p-2 mt-4">
                        <h2 class = "text-primary fs-3">Dashboard</h2>
                        <p class = "text-secondary">Helps you to administer the customers.</p>
                        <a href="{{ route('home.index') }}" class="btn btn-primary">{{ __('Dashboard') }}</a>
                    </div>
                    <div class="col-md-12 p-2 ">
                        <hr>
                        <h2 class = "text-primary fs-3">SEP-12 demo</h2>
                        <p class = "text-secondary">Demonstrates the SEP-12 functionalities, so you can register test custoemrs.</p>                        
                        <a href="{{ route('sep12demo') }}" class="btn btn-primary">{{ __('SEP-12 demo') }}</a>
                    </div>                
            </div>
        </div>
    </div>
</div>
@endsection