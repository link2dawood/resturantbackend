@extends('layouts.auth')

@section('title', 'Sign In')

@section('content')
<div class="text-center mb-4">
    <img src="https://tabler.io/static/logo.svg" height="36" alt="">
    <h1 class="h2 text-white mt-3">Login to your account</h1>
    <p class="text-white-50">Enter your email address and password to access your account</p>
</div>

<div class="card card-md">
    <div class="card-body">
        <form action="{{ route('login') }}" method="POST" autocomplete="off" novalidate>
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
            
            <div class="mb-2">
                <label class="form-label">
                    Password
                    <span class="form-label-description">
                        <a href="{{ route('password.request') }}">I forgot password</a>
                    </span>
                </label>
                <div class="input-group input-group-flat">
                    <input type="password" class="form-control @error('password') is-invalid @enderror" 
                           name="password" placeholder="Your password" autocomplete="current-password">
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
            
            <div class="mb-2">
                <label class="form-check">
                    <input type="checkbox" class="form-check-input" name="remember" {{ old('remember') ? 'checked' : '' }}/>
                    <span class="form-check-label">Remember me on this device</span>
                </label>
            </div>
            
            <div class="form-footer">
                <button type="submit" class="btn btn-primary w-100">Sign in</button>
            </div>
        </form>
    </div>
</div>

@endsection