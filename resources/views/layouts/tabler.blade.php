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
    <link href="https://cdn.jsdelivr.net/npm/@tabler/icons@1.39.1/icons-sprite.svg" rel="stylesheet"/>
    
    <!-- Modern Design System -->
    <link href="{{ asset('css/modern-design.css') }}" rel="stylesheet"/>
    
    <!-- Additional styles -->
    @stack('styles')
    
    <style>
        @import url('https://rsms.me/inter/inter.css');
        :root {
            --tblr-font-sans-serif: Inter, -apple-system, BlinkMacSystemFont, San Francisco, Segoe UI, Roboto, Helvetica Neue, sans-serif;
        }
        
        /* Modern Tabler Integration */
        .navbar {
            background: var(--bg-primary) !important;
            backdrop-filter: blur(10px);
            border-bottom: 1px solid var(--border-color);
            z-index: 1050;
        }
        
        .navbar-toggler {
            border: none;
        }
        
        .navbar-brand {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .navbar-nav .nav-item {
            margin: 0 10px;
        }
        
        .page {
            background: var(--bg-secondary);
            min-height: 100vh;
        }
        
        .card {
            background: var(--bg-primary);
            border: 1px solid var(--border-color);
            border-radius: var(--border-radius);
            transition: var(--transition);
        }
        
        .card:hover {
            border-color: var(--accent-color);
        }
        
        .btn-primary {
            background: var(--accent-color) !important;
            border-color: var(--accent-color) !important;
            border-radius: var(--border-radius-sm) !important;
            transition: var(--transition) !important;
        }
        
        .btn-primary:hover {
            background: var(--accent-dark) !important;
            border-color: var(--accent-dark) !important;
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
        
        .avatar {
            border: 2px solid var(--border-color) !important;
            transition: var(--transition) !important;
        }
        
        .avatar:hover {
            border-color: var(--accent-color) !important;
        }
        
        .badge {
            border-radius: var(--border-radius-sm) !important;
        }
        
        .alert {
            border-radius: var(--border-radius-sm) !important;
            border: 1px solid var(--border-color) !important;
        }
        
        .dropdown-menu {
            z-index: 1060 !important;
            box-shadow: 0px 4px 6px rgba(0, 0, 0, 0.1);
            border-radius: 0.25rem;
        }
        
        .dropdown-menu .dropdown-item {
            padding: 10px 15px;
            font-size: 0.9rem;
        }
        
        .dropdown-menu .dropdown-divider {
            margin: 0.5rem 0;
        }
    </style>
</head>
<body>
    <script src="https://cdn.jsdelivr.net/npm/@tabler/core@1.0.0-beta17/dist/js/demo-theme.min.js"></script>
    
    <div class="page">
        @auth
        <!-- Navbar -->
        <header class="navbar navbar-expand-md navbar-light d-print-none">
                <div class="container-xl">
                    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbar-menu">
                        <span class="navbar-toggler-icon"></span>
                    </button>
                    <h1 class="navbar-brand navbar-brand-autodark d-none-navbar-horizontal pe-0 pe-md-3">
                        <a href="{{ url('/home') }}">
                            <img src="https://tabler.io/static/logo-white.svg" width="110" height="32" alt="Tabler" class="navbar-brand-image">
                        </a>
                    </h1>
            </div>
    </header>
    <div class="row">
        <!-- Navbar -->
        <div class="navbar-expand-md">
            <div class="collapse navbar-collapse" id="navbar-menu">
                <div class="navbar navbar-light">
                    <div class="container-xl">
                        <ul class="navbar-nav">
                            <li class="nav-item {{ request()->routeIs('home') ? 'active' : '' }}">
                                <a class="nav-link" href="{{ route('home') }}">
                                    <span class="nav-link-icon d-md-none d-lg-inline-block">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="m0 0h24v24H0z" fill="none"/><polyline points="5 12 3 12 12 3 21 12 19 12"/><path d="m5 12v7a2 2 0 0 0 2 2h10a2 2 0 0 0 2 -2v-7"/><path d="m9 21v-6a2 2 0 0 1 2 -2h2a2 2 0 0 1 2 2v6"/></svg>
                                    </span>
                                    <span class="nav-link-title">
                                        Home
                                    </span>
                                </a>
                            </li>
                                {{-- <li class="nav-item {{ request()->routeIs('profile.*') ? 'active' : '' }}">
                                    <a class="nav-link" href="{{ route('profile.show') }}">
                                        <span class="nav-link-icon d-md-none d-lg-inline-block">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="m0 0h24v24H0z" fill="none"/><circle cx="12" cy="7" r="4"/><path d="m6 21v-2a4 4 0 0 1 4 -4h4a4 4 0 0 1 4 4v2"/></svg>
                                        </span>
                                        <span class="nav-link-title">
                                            Profile
                                        </span>
                                    </a>
                                </li> --}}
                                @if(Auth::user()->role == 'admin')
                                <li class="nav-item {{ request()->routeIs('owners.*') ? 'active' : '' }}">
                                    <a class="nav-link" href="{{ route('owners.index') }}">
                                        <span class="nav-link-icon d-md-none d-lg-inline-block">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="m0 0h24v24H0z" fill="none"/><circle cx="12" cy="7" r="4"/><path d="m6 21v-2a4 4 0 0 1 4 -4h4a4 4 0 0 1 4 4v2"/></svg>
                                        </span>
                                        <span class="nav-link-title">
                                            Owners
                                        </span>
                                    </a>
                                </li>
                                @endif
                                <li class="nav-item {{ request()->routeIs('stores.*') ? 'active' : '' }}">
                                    <a class="nav-link" href="{{ route('stores.index') }}">
                                        <span class="nav-link-icon d-md-none d-lg-inline-block">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                                <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                                <rect x="3" y="4" width="18" height="12" rx="1" />
                                                <line x1="7" y1="20" x2="17" y2="20" />
                                                <line x1="9" y1="16" x2="9" y2="20" />
                                                <line x1="15" y1="16" x2="15" y2="20" />
                                            </svg>
                                        </span>
                                        <span class="nav-link-title">
                                            Stores
                                        </span>
                                    </a>
                                </li>
                                  @if(Auth::user()->role == 'owner' || Auth::user()->role == 'admin')
                                <li class="nav-item {{ request()->routeIs('managers.*') ? 'active' : '' }}">
                                    <a class="nav-link" href="{{ route('managers.index') }}">
                                        <span class="nav-link-icon d-md-none d-lg-inline-block">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                                <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                                <circle cx="12" cy="7" r="4" />
                                                <path d="M6 21v-2a4 4 0 0 1 4 -4h4a4 4 0 0 1 4 4v2" />
                                            </svg>
                                        </span>
                                        <span class="nav-link-title">
                                            Managers
                                        </span>
                                    </a>
                                </li>
                                @endif

                                @if(Auth::user()->role == 'admin')
                                <li class="nav-item {{ request()->routeIs('transaction-types.*') ? 'active' : '' }}">
                                    <a class="nav-link" href="{{ route('transaction-types.index') }}">
                                        <span class="nav-link-icon d-md-none d-lg-inline-block">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                                <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                                <rect x="5" y="11" width="14" height="10" rx="2" />
                                                <circle cx="12" cy="16" r="1" />
                                                <path d="M8 11v-4a4 4 0 0 1 8 0v4" />
                                            </svg>
                                        </span>
                                        <span class="nav-link-title">
                                            Transaction Types
                                        </span>
                                    </a>
                                </li>
                                @endif
                                 @if(Auth::user()->role == 'owner' || Auth::user()->role == 'manager')
                                <li class="nav-item {{ request()->routeIs('daily-reports.*') ? 'active' : '' }}">
                                    <a class="nav-link" href="{{ route('daily-reports.index') }}">
                                        <span class="nav-link-icon d-md-none d-lg-inline-block">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                                <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                                <circle cx="12" cy="7" r="4" />
                                                <path d="M6 21v-2a4 4 0 0 1 4 -4h4a4 4 0 0 1 4 4v2" />
                                            </svg>
                                        </span>
                                        <span class="nav-link-title">
                                            Daily Reports
                                        </span>
                                    </a>
                                </li>
                                @endif
                            </ul>
                             <div class="navbar-nav flex-row order-md-last">
                        <div class="nav-item dropdown">
                            @if(Auth::user()->avatar_url && Auth::user()->name && Auth::user()->email)
                            <a href="#" class="nav-link d-flex lh-1 text-reset p-0" data-bs-toggle="dropdown" aria-label="Open user menu">
                                <span class="avatar avatar-sm" style="background-image: url({{ Auth::user()->avatar_url }})"></span>
                                <div class="d-none d-xl-block ps-2">
                                    <div>{{ Auth::user()->name }}</div>
                                    <div class="mt-1 small text-muted">{{ Auth::user()->email }}</div>
                                </div>
                            </a>
                            <div class="dropdown-menu dropdown-menu-end dropdown-menu-arrow">
                                <a href="{{ route('profile.show') }}" class="dropdown-item">Profile</a>
                                <a href="#" class="dropdown-item">Settings</a>
                                <div class="dropdown-divider"></div>
                                <a href="{{ route('logout') }}" class="dropdown-item"
                                onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                                Logout
                            </a>
                            <form id="logout-form" action="{{ route('logout') }}" method="POST" class="d-none">
                                @csrf
                            </form>
                        </div>
                        @else
                        <span class="text-danger">User data is incomplete.</span>
                        @endif
                    </div>
                </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        @endauth
        
        <div class="page-wrapper">
            @yield('content')
        </div>
    </div>
    
    <!-- Tabler Core -->
    <script src="https://cdn.jsdelivr.net/npm/@tabler/core@1.0.0-beta17/dist/js/tabler.min.js"></script>
    
    <!-- Neumorphic Dropzone -->
    <script src="{{ asset('js/neumorphic-dropzone.js') }}"></script>
    
    <!-- Initialize dropzone -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Initialize dropzone
            const avatarDropzone = document.getElementById('avatarDropzone');
            if (avatarDropzone) {
                window.dropzone = new NeumorphicDropzone(avatarDropzone, {
                    uploadUrl: '{{ route("profile.avatar.update") }}',
                    maxFileSize: 2 * 1024 * 1024,
                    allowedTypes: ['image/jpeg', 'image/png', 'image/jpg', 'image/gif']
                });
                
                // Show upload button when files are selected
                const originalUpdateUI = dropzone.updateUI.bind(dropzone);
                dropzone.updateUI = function() {
                    originalUpdateUI();
                    const uploadBtn = document.getElementById('uploadBtn');
                    if (uploadBtn) {
                        uploadBtn.style.display = this.files.length > 0 ? 'inline-flex' : 'none';
                    }
                };
            }
        });
    </script>
    
    <!-- Additional scripts -->
    @stack('scripts')
</body>
</html>