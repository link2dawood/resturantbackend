<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover, user-scalable=no"/>
    <meta http-equiv="X-UA-Compatible" content="ie=edge"/>
    <meta name="mobile-web-app-capable" content="yes"/>
    <meta name="apple-mobile-web-app-capable" content="yes"/>
    <meta name="apple-mobile-web-app-status-bar-style" content="default"/>
    
    <!-- CSRF Token -->
    <meta name="csrf-token" content="{{ csrf_token() }}">
    
    <title>@yield('title', config('app.name', 'Laravel'))</title>
    
    <!-- CSS files -->
    <link href="https://cdn.jsdelivr.net/npm/@tabler/core@1.0.0-beta17/dist/css/tabler.min.css" rel="stylesheet"/>
    <link href="https://cdn.jsdelivr.net/npm/@tabler/icons@1.39.1/icons-sprite.svg" rel="stylesheet"/>

    <!-- Google Material Design 3 (Primary Design System) -->
    <link href="{{ asset('css/google-material-design.css') }}" rel="stylesheet"/>

    <!-- Legacy Design System (Lower Priority) -->
    <link href="{{ asset('css/modern-design.css') }}" rel="stylesheet"/>

    <!-- Phone Formatter Styles -->
    <link href="{{ asset('css/phone-formatter.css') }}" rel="stylesheet"/>

    <!-- Date Formatter Styles -->
    <link href="{{ asset('css/date-formatter.css') }}" rel="stylesheet"/>
    
    <!-- Additional styles -->
    @stack('styles')
    
    <style>
        @import url('https://rsms.me/inter/inter.css');
        :root {
            --tblr-font-sans-serif: Inter, -apple-system, BlinkMacSystemFont, San Francisco, Segoe UI, Roboto, Helvetica Neue, sans-serif;
        }
        
        /* Clean Modern Navbar Design */
        .navbar {
            background: #ffffff !important;
            border-bottom: 1px solid #e5e5e5;
            box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1);
            padding: 0.75rem 0;
        }
        
        .navbar-brand {
            font-size: 1.125rem;
            font-weight: 600;
            color: #1e293b !important;
        }
        
        .navbar-brand svg {
            color: #0ea5e9;
        }
        
        /* Navigation styling - Clean and simple */
        .navbar-nav {
            gap: 0.25rem;
        }
        
        .nav-link {
            display: flex !important;
            align-items: center;
            padding: 0.5rem 0.75rem !important;
            border-radius: 0.375rem;
            transition: all 0.15s ease-in-out;
            color: #64748b !important;
            font-weight: 500;
            font-size: 0.875rem;
        }
        
        .nav-link:hover {
            background-color: #f1f5f9 !important;
            color: #0ea5e9 !important;
        }
        
        .nav-link.active,
        .nav-item.active .nav-link {
            background-color: #0ea5e9 !important;
            color: white !important;
            box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
        }
        
        .nav-link-icon {
            margin-right: 0.5rem;
            width: 16px;
            height: 16px;
            opacity: 0.8;
        }
        
        .nav-link-title {
            font-size: 0.875rem;
        }
        
        /* Dropdown styling - Clean and minimal */
        .dropdown-menu {
            z-index: 1060 !important;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            border-radius: 0.375rem;
            border: 1px solid #e5e5e5;
            padding: 0.25rem 0;
            margin-top: 0.25rem;
        }
        
        .dropdown-item {
            padding: 0.5rem 0.75rem;
            font-size: 0.875rem;
            font-weight: 500;
            display: flex;
            align-items: center;
            transition: all 0.15s ease-in-out;
            color: #374151;
        }
        
        .dropdown-item:hover {
            background-color: #f9fafb;
            color: #0ea5e9;
        }
        
        .dropdown-item.text-danger:hover {
            background-color: #fef2f2;
            color: #dc2626 !important;
        }
        
        /* Avatar styling - Clean and simple */
        .avatar {
            border: 1px solid #e5e5e5 !important;
            transition: all 0.15s ease-in-out !important;
        }
        
        /* Page wrapper - Clean background */
        .page {
            background: #f8fafc;
            min-height: 100vh;
        }
        
        .page-wrapper {
            padding-top: 1.5rem;
        }
        
        /* Card styling - Minimal and clean */
        .card {
            background: #ffffff;
            border: 1px solid #e5e5e5;
            border-radius: 0.5rem;
            box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1);
        }
        
        /* Form controls - Clean and minimal */
        .btn-primary {
            background: #0ea5e9 !important;
            border-color: #0ea5e9 !important;
            border-radius: 0.375rem !important;
            font-weight: 500 !important;
            font-size: 0.875rem !important;
            padding: 0.5rem 1rem !important;
        }
        
        .btn-primary:hover {
            background: #0284c7 !important;
            border-color: #0284c7 !important;
        }
        
        .form-control {
            border: 1px solid #d1d5db !important;
            border-radius: 0.375rem !important;
            font-size: 0.875rem !important;
        }
        
        .form-control:focus {
            border-color: #0ea5e9 !important;
            box-shadow: 0 0 0 2px rgba(14, 165, 233, 0.1) !important;
        }
        
        /* Responsive design */
        @media (max-width: 768px) {
            .navbar-brand span {
                display: none;
            }
            
            .nav-link {
                padding: 0.5rem !important;
            }
        }
    </style>
</head>
<body>
    <script src="https://cdn.jsdelivr.net/npm/@tabler/core@1.0.0-beta17/dist/js/demo-theme.min.js"></script>
    
    <div class="page">
        @auth
        <!-- Main Navigation Header -->
        <header class="navbar navbar-expand-md navbar-light sticky-top d-print-none">
            <div class="container-xl">
                <!-- Brand -->
                <h1 class="navbar-brand navbar-brand-autodark pe-0 pe-md-3">
                    <a href="{{ url('/home') }}" class="d-flex align-items-center text-decoration-none">
                        <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="me-2">
                            <path d="M3 9l9-7 9 7v11a2 2 0 01-2 2H5a2 2 0 01-2-2z"/>
                            <polyline points="9,22 9,12 15,12 15,22"/>
                        </svg>
                        <span class="fw-bold">Restaurant Manager</span>
                    </a>
                </h1>

                <!-- Mobile Toggle -->
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbar-menu">
                    <span class="navbar-toggler-icon"></span>
                </button>

                <!-- User Menu -->
                <div class="navbar-nav flex-row order-md-last">
                    <div class="nav-item dropdown">
                        @if(Auth::user()->avatar_url && Auth::user()->name && Auth::user()->email)
                        <a href="#" class="nav-link d-flex lh-1 text-reset p-0" data-bs-toggle="dropdown">
                            <span class="avatar avatar-sm" style="background-image: url({{ Auth::user()->avatar_url }})"></span>
                            <div class="d-none d-xl-block ps-2">
                                <div class="fw-medium">{{ Auth::user()->name }}</div>
                                <div class="mt-1 small text-muted">{{ Auth::user()->email }}</div>
                            </div>
                        </a>
                        <div class="dropdown-menu dropdown-menu-end dropdown-menu-arrow">
                            <a href="{{ route('profile.show') }}" class="dropdown-item">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="me-2">
                                    <circle cx="12" cy="7" r="4"/>
                                    <path d="M6 21v-2a4 4 0 014-4h4a4 4 0 014 4v2"/>
                                </svg>
                                Profile
                            </a>
                            <div class="dropdown-divider"></div>
                            <a href="{{ route('logout') }}" class="dropdown-item text-danger" onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="me-2">
                                    <path d="M9 21H5a2 2 0 01-2-2V5a2 2 0 012-2h4"/>
                                    <polyline points="16,17 21,12 16,7"/>
                                    <line x1="21" y1="12" x2="9" y2="12"/>
                                </svg>
                                Logout
                            </a>
                            <form id="logout-form" action="{{ route('logout') }}" method="POST" class="d-none">
                                @csrf
                            </form>
                        </div>
                        @else
                        <span class="badge badge-outline text-red">User data incomplete</span>
                        @endif
                    </div>
                </div>
            </div>
        </header>

        <!-- Navigation Menu -->
        <header class="navbar-expand-md">
            <div class="collapse navbar-collapse" id="navbar-menu">
                <div class="navbar navbar-light border-bottom">
                    <div class="container-xl">
                        <ul class="navbar-nav">
                            <!-- Home -->
                            <li class="nav-item {{ request()->routeIs('home') ? 'active' : '' }}">
                                <a class="nav-link" href="{{ route('home') }}">
                                    <span class="nav-link-icon d-md-none d-lg-inline-block">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                            <path d="M3 9l9-7 9 7v11a2 2 0 01-2 2H5a2 2 0 01-2-2z"/>
                                            <polyline points="9,22 9,12 15,12 15,22"/>
                                        </svg>
                                    </span>
                                    <span class="nav-link-title">Dashboard</span>
                                </a>
                            </li>

                            <!-- Stores -->
                            <li class="nav-item {{ request()->routeIs('stores.*') ? 'active' : '' }}">
                                <a class="nav-link" href="{{ route('stores.index') }}">
                                    <span class="nav-link-icon d-md-none d-lg-inline-block">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                            <path d="M2 3h6a4 4 0 014 4v14a3 3 0 00-3-3H2z"/>
                                            <path d="M22 3h-6a4 4 0 00-4 4v14a3 3 0 013-3h7z"/>
                                        </svg>
                                    </span>
                                    <span class="nav-link-title">Stores</span>
                                </a>
                            </li>

                            <!-- Owners -->
                            @if(Auth::user()->hasPermission('manage_owners'))
                            <li class="nav-item {{ request()->routeIs('owners.*') ? 'active' : '' }}">
                                <a class="nav-link" href="{{ route('owners.index') }}">
                                    <span class="nav-link-icon d-md-none d-lg-inline-block">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                            <circle cx="12" cy="7" r="4"/>
                                            <path d="M6 21v-2a4 4 0 014-4h4a4 4 0 014 4v2"/>
                                        </svg>
                                    </span>
                                    <span class="nav-link-title">Owners</span>
                                </a>
                            </li>
                            @endif

                            <!-- Managers -->
                            @if(Auth::user()->hasPermission('manage_managers'))
                            <li class="nav-item {{ request()->routeIs('managers.*') ? 'active' : '' }}">
                                <a class="nav-link" href="{{ route('managers.index') }}">
                                    <span class="nav-link-icon d-md-none d-lg-inline-block">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                            <path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/>
                                            <circle cx="9" cy="7" r="4"/>
                                            <path d="M23 21v-2a4 4 0 00-3-3.87"/>
                                            <path d="M16 3.13a4 4 0 010 7.75"/>
                                        </svg>
                                    </span>
                                    <span class="nav-link-title">Managers</span>
                                </a>
                            </li>
                            @endif

                            <!-- Transaction Management -->
                            @if(Auth::user()->hasPermission('manage_transaction_types'))
                            <li class="nav-item dropdown {{ request()->routeIs('transaction-types.*') || request()->routeIs('revenue-income-types.*') ? 'active' : '' }}">
                                <a class="nav-link dropdown-toggle" href="#navbar-extra" data-bs-toggle="dropdown" role="button" aria-expanded="false">
                                    <span class="nav-link-icon d-md-none d-lg-inline-block">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                            <rect x="1" y="4" width="22" height="16" rx="2" ry="2"/>
                                            <line x1="1" y1="10" x2="23" y2="10"/>
                                        </svg>
                                    </span>
                                    <span class="nav-link-title">Transactions</span>
                                </a>
                                <div class="dropdown-menu">
                                    <a class="dropdown-item" href="{{ route('transaction-types.index') }}">
                                        Transaction Types
                                    </a>
                                    <a class="dropdown-item" href="{{ route('revenue-income-types.index') }}">
                                        Revenue Income Types
                                    </a>
                                </div>
                            </li>
                            @endif

                            <!-- Reports -->
                            @if(Auth::user()->hasPermission('create_reports'))
                            <li class="nav-item {{ request()->routeIs('daily-reports.*') ? 'active' : '' }}">
                                <a class="nav-link" href="{{ route('daily-reports.index') }}">
                                    <span class="nav-link-icon d-md-none d-lg-inline-block">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                            <path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/>
                                            <polyline points="14,2 14,8 20,8"/>
                                            <line x1="16" y1="13" x2="8" y2="13"/>
                                            <line x1="16" y1="17" x2="8" y2="17"/>
                                            <polyline points="10,9 9,9 8,9"/>
                                        </svg>
                                    </span>
                                    <span class="nav-link-title">Daily Reports</span>
                                </a>
                            </li>
                            @endif

                            <!-- US States -->
                            <li class="nav-item {{ request()->routeIs('states.*') ? 'active' : '' }}">
                                <a class="nav-link" href="{{ route('states.index') }}">
                                    <span class="nav-link-icon d-md-none d-lg-inline-block">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                            <circle cx="12" cy="12" r="10"/>
                                            <line x1="2" y1="12" x2="22" y2="12"/>
                                            <path d="M12 2a15.3 15.3 0 014 10 15.3 15.3 0 01-4 10 15.3 15.3 0 01-4-10 15.3 15.3 0 014-10z"/>
                                        </svg>
                                    </span>
                                    <span class="nav-link-title">US States</span>
                                </a>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </header>
        @endauth
        
        <div class="page-wrapper">
            @yield('content')
        </div>
    </div>
    
    <!-- Tabler Core -->
    <script src="https://cdn.jsdelivr.net/npm/@tabler/core@1.0.0-beta17/dist/js/tabler.min.js"></script>
    
    <!-- Neumorphic Dropzone -->
    <script src="{{ asset('js/neumorphic-dropzone.js') }}"></script>
    
    <!-- Phone Formatter -->
    <script src="{{ asset('js/phone-formatter.js') }}"></script>
    
    <!-- Date Formatter -->
    <script src="{{ asset('js/date-formatter.js') }}"></script>
    
    <!-- Initialize dropzone -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Initialize dropzone
            const pictureDropzone = document.getElementById('avatarDropzone');
            if (pictureDropzone) {
                window.dropzone = new NeumorphicDropzone(pictureDropzone, {
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
    
    <!-- Responsive JavaScript -->
    <script src="{{ asset('js/responsive.js') }}"></script>
    
    <!-- Additional scripts -->
    @stack('scripts')
</body>
</html>