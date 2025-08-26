@extends('layouts.auth')

@section('title', 'Reset Password')

@section('content')
<div class="text-center mb-4">
    <img src="https://tabler.io/static/logo.svg" height="36" alt="">
    <h1 class="h2 text-white mt-3">Reset your password</h1>
    <p class="text-white-50">Enter your new password below to complete the reset process.</p>
</div>

<div class="card card-md">
    <div class="card-body">
        <form action="{{ route('password.update') }}" method="POST" autocomplete="off" novalidate>
            @csrf
            <input type="hidden" name="token" value="{{ $token }}">
            
            <div class="mb-3">
                <label class="form-label">Email address</label>
                <input type="email" class="form-control @error('email') is-invalid @enderror" 
                       name="email" value="{{ $email ?? old('email') }}" 
                       placeholder="your@email.com" autocomplete="email" readonly>
                @error('email')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
            
            <div class="mb-3">
                <label class="form-label">New Password</label>
                <div class="input-group input-group-flat">
                    <input type="password" class="form-control @error('password') is-invalid @enderror" 
                           name="password" placeholder="Your new password" autocomplete="new-password" autofocus>
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
                <label class="form-label">Confirm New Password</label>
                <input type="password" class="form-control" name="password_confirmation" 
                       placeholder="Confirm your new password" autocomplete="new-password">
            </div>
            
            <div class="form-footer">
                <button type="submit" class="btn btn-primary w-100">
                    <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="m0 0h24v24H0z" fill="none"/><rect x="3" y="11" width="18" height="11" rx="2"/><circle cx="12" cy="16" r="1"/><path d="m7 11v-4a5 5 0 0 1 10 0v4"/></svg>
                    Reset password
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
