<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Trial Expired — {{ config('app.name') }}</title>
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
    <style>
        body {
            min-height: 100vh;
            margin: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #f5f7fb 0%, #e9eef6 100%);
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
            color: #1d2b3a;
        }
        .trial-card {
            max-width: 520px;
            width: calc(100% - 2rem);
            background: #fff;
            border-radius: 16px;
            box-shadow: 0 18px 50px rgba(29, 43, 58, 0.12);
            padding: 2.5rem;
            text-align: center;
        }
        .trial-badge {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 64px;
            height: 64px;
            border-radius: 50%;
            background: #fdecec;
            color: #d63939;
            font-size: 1.9rem;
            margin-bottom: 1.25rem;
        }
        .trial-card h1 { font-size: 1.6rem; margin: 0 0 .5rem; font-weight: 700; }
        .trial-card p { color: #5b6b7c; line-height: 1.55; margin: 0 0 1rem; }
        .trial-meta { font-size: .9rem; color: #8a98a8; margin-bottom: 1.75rem; }
        .btn-primary-lg {
            display: inline-block;
            background: #206bc4;
            color: #fff;
            border: none;
            padding: .85rem 1.6rem;
            border-radius: 10px;
            font-weight: 600;
            font-size: 1rem;
            cursor: pointer;
            text-decoration: none;
            transition: background .15s ease;
        }
        .btn-primary-lg:hover { background: #1a59a3; }
        .alert {
            border-radius: 10px;
            padding: .85rem 1rem;
            margin-bottom: 1.5rem;
            font-size: .92rem;
        }
        .alert-success { background: #e9f7ef; color: #1f8a4c; }
        .alert-error { background: #fdecec; color: #d63939; }
        .alert-info { background: #eef3fb; color: #206bc4; }
        .trial-foot { margin-top: 1.75rem; font-size: .85rem; }
        .trial-foot a { color: #8a98a8; text-decoration: none; }
        .trial-foot a:hover { text-decoration: underline; }
    </style>
</head>
<body>
    <div class="trial-card">
        <div class="trial-badge">&#9203;</div>
        <h1>Your free trial has ended</h1>

        @if (session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif
        @if (session('error'))
            <div class="alert alert-error">{{ session('error') }}</div>
        @endif

        <p>
            Your {{ (int) config('trial.days', 30) }}-day free trial of <strong>{{ config('app.name') }}</strong>
            ended on <strong>{{ optional($owner->trial_ends_at)->format('M j, Y') }}</strong>.
            Your workspace is paused — but all of your data is safe and waiting.
        </p>

        @if ($isOwner)
            @if ($requestedAt)
                <div class="alert alert-info">
                    We received your request on {{ $requestedAt->format('M j, Y') }}. Our team will be in touch shortly.
                </div>
            @endif

            <p>Subscribe to instantly restore access, or request a call and our team will help you continue.</p>

            <a href="{{ route('billing.show') }}" class="btn-primary-lg" style="margin-bottom: .85rem;">
                Subscribe to continue
            </a>

            <form method="POST" action="{{ route('trial.request-continue') }}">
                @csrf
                <button type="submit" style="background: none; border: none; color: #8a98a8; cursor: pointer; text-decoration: underline; font-size: .9rem;">
                    {{ $requestedAt ? 'Request a call again' : 'Or request a call from our team' }}
                </button>
            </form>
        @else
            <p>Please ask your account owner (<strong>{{ $owner->email }}</strong>) to renew the subscription to restore access.</p>
        @endif

        <div class="trial-foot">
            <a href="{{ route('logout') }}"
               onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                Sign out
            </a>
            <form id="logout-form" action="{{ route('logout') }}" method="POST" style="display:none;">@csrf</form>
        </div>
    </div>
</body>
</html>
