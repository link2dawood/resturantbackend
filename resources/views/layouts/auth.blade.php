<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover"/>
    <meta http-equiv="X-UA-Compatible" content="ie=edge"/>
    
    <!-- CSRF Token -->
    <meta name="csrf-token" content="{{ csrf_token() }}">
    
    <title>@yield('title', config('app.name', 'Laravel'))</title>
    
    <!-- CSS files -->
    <link href="https://cdn.jsdelivr.net/npm/@tabler/core@1.0.0-beta17/dist/css/tabler.min.css" rel="stylesheet"/>
    
    <!-- Modern Design System -->
    <link href="{{ asset('css/modern-design.css') }}" rel="stylesheet"/>
    
    <style>
        @import url('https://rsms.me/inter/inter.css');
        :root {
            --tblr-font-sans-serif: Inter, -apple-system, BlinkMacSystemFont, San Francisco, Segoe UI, Roboto, Helvetica Neue, sans-serif;
        }
        
        .page {
            background: var(--bg-secondary);
            min-height: 100vh;
        }
        
        .card {
            background: var(--bg-primary);
            border: 1px solid var(--border-color);
            border-radius: var(--border-radius);
            backdrop-filter: blur(10px);
            transition: var(--transition);
        }
        
        .card:hover {
            border-color: var(--accent-color);
        }
        
        .form-control {
            background: var(--bg-primary) !important;
            border: 1px solid var(--border-color) !important;
            border-radius: var(--border-radius-sm) !important;
            transition: var(--transition) !important;
        }
        
        .form-control:focus {
            border-color: var(--accent-color) !important;
            box-shadow: 0 0 0 3px rgba(14, 165, 233, 0.1) !important;
        }
        
        .btn-primary {
            background: var(--accent-color) !important;
            border: 1px solid var(--accent-color) !important;
            border-radius: var(--border-radius-sm) !important;
            transition: var(--transition) !important;
        }
        
        .btn-primary:hover {
            background: var(--accent-dark) !important;
            border-color: var(--accent-dark) !important;
        }
        
        .btn-white {
            background: var(--bg-primary) !important;
            border: 1px solid var(--border-color) !important;
            border-radius: var(--border-radius-sm) !important;
            color: var(--text-primary) !important;
            transition: var(--transition) !important;
        }
        
        .btn-white:hover {
            background: var(--bg-tertiary) !important;
            border-color: var(--accent-color) !important;
            color: var(--text-primary) !important;
        }
        
        .btn:active {
            transform: scale(0.98);
        }
        
        .alert {
            background: var(--bg-primary) !important;
            border: 1px solid var(--border-color) !important;
            border-radius: var(--border-radius-sm) !important;
        }
        
        .text-white {
            color: var(--text-primary) !important;
        }
        
        .text-white-50 {
            color: var(--text-secondary) !important;
        }
    </style>
</head>
<body class="d-flex flex-column">
    <script src="https://cdn.jsdelivr.net/npm/@tabler/core@1.0.0-beta17/dist/js/demo-theme.min.js"></script>
    
    <div class="page page-center">
        <div class="container container-tight py-4">
            @yield('content')
        </div>
    </div>
    
    <!-- Tabler Core -->
    <script src="https://cdn.jsdelivr.net/npm/@tabler/core@1.0.0-beta17/dist/js/tabler.min.js"></script>
</body>
</html>