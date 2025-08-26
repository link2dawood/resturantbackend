@extends('layouts.auth')

@section('title', 'Forgot Password')

@section('content')
<div class="text-center mb-4">
    <img src="https://tabler.io/static/logo.svg" height="36" alt="">
    <h1 class="h2 text-white mt-3">Forgot your password?</h1>
    <p class="text-white-50">Enter your email address and we'll send you a link to reset your password.</p>
</div>

<div class="card card-md">
    <div class="card-body">
        @if (session('status'))
            <div class="alert alert-success alert-dismissible" role="alert">
                <div class="d-flex">
                    <div>
                        <svg xmlns="http://www.w3.org/2000/svg" class="icon alert-icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="m0 0h24v24H0z" fill="none"/><path d="M5 12l5 5l10 -10"/></svg>
                    </div>
                    <div>{{ session('status') }}</div>
                </div>
                <a class="btn-close" data-bs-dismiss="alert" aria-label="close"></a>
            </div>
        @endif

        <form action="{{ route('password.email') }}" method="POST" autocomplete="off" novalidate>
            @csrf
            <div class="mb-3">
                <label class="form-label">Email address</label>
                <input type="email" class="form-control @error('email') is-invalid @enderror" 
                       name="email" value="{{ old('email') }}" placeholder="your@email.com" 
                       autocomplete="email" autofocus>
                @error('email')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
            
            <div class="form-footer">
                <button type="submit" class="btn btn-primary w-100">
                    <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="m0 0h24v24H0z" fill="none"/><path d="M3 7a2 2 0 0 1 2 -2h14a2 2 0 0 1 2 2v10a2 2 0 0 1 -2 2h-14a2 2 0 0 1 -2 -2v-10z"/><path d="m3 7l9 6l9 -6"/></svg>
                    Send password reset email
                </button>
            </div>
        </form>
    </div>
</div>

<div class="text-center text-white-50 mt-3">
    Remember your password? 
    <a href="{{ route('login') }}" class="text-white">Sign in</a>
</div>
@endsection
