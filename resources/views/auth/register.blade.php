@extends('layouts.auth')

@section('title', 'Sign Up')

@section('content')
<div class="text-center mb-4">
    <img src="https://tabler.io/static/logo.svg" height="36" alt="">
    <h1 class="h2 text-white mt-3">Create new account</h1>
    <p class="text-white-50">Enter your details to get started with your new account</p>
</div>

<div class="card card-md">
    <div class="card-body">
        <form action="{{ route('register') }}" method="POST" autocomplete="off" novalidate>
            @csrf
            
            <div class="mb-3">
                <label class="form-label">Name</label>
                <input type="text" class="form-control @error('name') is-invalid @enderror" 
                       name="name" value="{{ old('name') }}" placeholder="Enter your name" 
                       autocomplete="name" autofocus>
                @error('name')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
            
            <div class="mb-3">
                <label class="form-label">Email address</label>
                <input type="email" class="form-control @error('email') is-invalid @enderror" 
                       name="email" value="{{ old('email') }}" placeholder="your@email.com" 
                       autocomplete="email">
                @error('email')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
            
            <div class="mb-3">
                <label class="form-label">Password</label>
                <div class="input-group input-group-flat">
                    <input type="password" class="form-control @error('password') is-invalid @enderror" 
                           name="password" placeholder="Your password" autocomplete="new-password">
                    <span class="input-group-text">
                        <a href="#" class="link-secondary" title="Show password" data-bs-toggle="tooltip">
                            <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="m0 0h24v24H0z" fill="none"/><circle cx="12" cy="12" r="2"/><path d="m22 12c-2.667 4.667 -6 7 -10 7s-7.333 -2.333 -10 -7c2.667 -4.667 6 -7 10 -7s7.333 2.333 10 7"/></svg>
                        </a>
                    </span>
                </div>
                @error('password')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
            
            <div class="mb-3">
                <label class="form-label">Confirm Password</label>
                <input type="password" class="form-control" name="password_confirmation" 
                       placeholder="Confirm your password" autocomplete="new-password">
            </div>
            
            <div class="mb-3">
                <label class="form-check">
                    <input type="checkbox" class="form-check-input" required/>
                    <span class="form-check-label">Agree the <a href="#" tabindex="-1">terms and policy</a>.</span>
                </label>
            </div>
            
            <div class="form-footer">
                <button type="submit" class="btn btn-primary w-100">Create new account</button>
            </div>
        </form>
    </div>
    
    <div class="hr-text">or</div>
    
    <div class="card-body">
        <div class="row">
            <div class="col">
                <a href="{{ route('google.signin') }}" class="btn btn-white w-100">
                    <svg xmlns="http://www.w3.org/2000/svg" class="icon text-google" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="m0 0h24v24H0z" fill="none"/><path d="M17.788 5.108A9 9 0 1 0 21 12h-8"/></svg>
                    Sign up with Google
                </a>
            </div>
        </div>
    </div>
</div>

<div class="text-center text-white-50 mt-3">
    Already have account? 
    <a href="{{ route('login') }}" class="text-white" tabindex="-1">Sign in</a>
</div>
@endsection