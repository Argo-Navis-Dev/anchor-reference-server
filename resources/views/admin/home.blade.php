@extends('layouts.app')
@section('pageType', 'home')
@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header text-primary">{{ __('DASHBOARD') }}</div>

                <div class="card-body">
                    @if (session('status'))
                        <div class="alert alert-success" role="alert">
                            {{ session('status') }}
                        </div>
                    @endif                    
                    <h3 class="text-primary fs-4 text-center">{{ __('Welcome to Anchor Reference Server dashboard!') }}</h3>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
