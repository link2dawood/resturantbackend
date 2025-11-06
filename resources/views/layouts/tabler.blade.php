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
    
    <!-- Google Material Design Dashboard (Primary) -->
    <link href="{{ asset('css/google-dashboard.css') }}" rel="stylesheet"/>

    <!-- Legacy CSS files for backward compatibility -->
    <link href="https://cdn.jsdelivr.net/npm/@tabler/core@1.0.0-beta17/dist/css/tabler.min.css" rel="stylesheet"/>
    <link href="{{ asset('css/google-material-design.css') }}" rel="stylesheet"/>
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
        
        /* Override Tabler with Google Dashboard Styles */
        body {
            font-family: 'Google Sans', -apple-system, BlinkMacSystemFont, sans-serif !important;
            background: #f8f9fa !important;
        }

        /* Material Design 3 Modern Navbar */
        .navbar {
            background: var(--surface, #ffffff) !important;
            border-bottom: 1px solid var(--google-grey-200, #e8eaed);
            box-shadow: var(--elevation-2, 0 1px 3px 0 rgba(60, 64, 67, 0.08));
            padding: 1rem 0;
        }
        
        .navbar-brand {
            font-family: 'Google Sans', -apple-system, BlinkMacSystemFont, sans-serif;
            font-size: 1.375rem;
            font-weight: 400;
            color: var(--google-blue, #4285f4) !important;
            letter-spacing: -0.25px;
        }
        
        .navbar-brand svg {
            color: var(--google-blue, #4285f4);
            transition: var(--transition-standard, all 0.2s ease);
        }

        .navbar-brand:hover svg {
            color: var(--google-blue-700, #1967d2);
        }
        
        /* Material Design 3 Navigation */
        .navbar-nav {
            gap: 0.5rem;
        }
        
        .nav-link {
            display: flex !important;
            align-items: center;
            padding: 0.625rem 1rem !important;
            border-radius: 1.5rem; /* Material Design pill shape */
            transition: var(--transition-standard, all 0.2s ease);
            color: var(--google-grey-700, #3c4043) !important;
            font-family: 'Google Sans', sans-serif;
            font-weight: 400;
            font-size: 0.875rem;
            letter-spacing: 0.1px;
        }
        
        .nav-link:hover {
            background-color: var(--google-blue-50, #e8f0fe) !important;
            color: var(--google-blue-700, #1967d2) !important;
        }
        
        .nav-link.active,
        .nav-item.active .nav-link {
            background-color: var(--google-blue, #4285f4) !important;
            color: white !important;
            font-weight: 500;
            box-shadow: var(--elevation-2, 0 1px 3px 0 rgba(60, 64, 67, 0.08));
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
        
        /* Material Design 3 Dropdown */
        .dropdown-menu {
            z-index: 1060 !important;
            box-shadow: var(--elevation-3, 0 2px 6px 2px rgba(60, 64, 67, 0.15));
            border-radius: 0.75rem;
            border: 1px solid var(--google-grey-200, #e8eaed);
            padding: 0.5rem 0;
            margin-top: 0.5rem;
            background: var(--surface, #ffffff);
        }
        
        .dropdown-item {
            padding: 0.75rem 1rem;
            font-family: 'Google Sans', sans-serif;
            font-size: 0.875rem;
            font-weight: 400;
            display: flex;
            align-items: center;
            transition: var(--transition-standard, all 0.2s ease);
            color: var(--on-surface, #202124);
        }
        
        .dropdown-item:hover {
            background-color: var(--google-grey-50, #f8f9fa);
            color: var(--google-blue, #4285f4);
        }
        
        .dropdown-item.text-danger:hover {
            background-color: var(--google-red-50, #fce8e6);
            color: var(--google-red-700, #c5221f) !important;
        }
        
        /* Material Design 3 Avatar */
        .avatar {
            border: 1px solid var(--google-grey-200, #e8eaed) !important;
            transition: var(--transition-standard, all 0.2s ease) !important;
        }

        .avatar:hover {
            border-color: var(--google-blue, #4285f4) !important;
            transform: scale(1.05);
        }
        
        /* Material Design 3 Page Background */
        .page {
            background: var(--google-grey-50, #f8f9fa);
            min-height: 100vh;
        }
        
        .page-wrapper {
            padding-top: 1rem;
        }

        /* Table Styles for Consistency */
        .table {
            font-family: 'Google Sans', sans-serif !important;
            font-size: 0.875rem;
            border-collapse: collapse;
            background: var(--surface, #ffffff);
        }

        .table thead th {
            background: var(--google-grey-50, #f8f9fa) !important;
            border: 1px solid var(--google-grey-200, #e8eaed) !important;
            font-weight: 500 !important;
            color: var(--google-grey-700, #3c4043) !important;
            font-size: 0.813rem !important;
            letter-spacing: 0.3px !important;
            padding: 0.75rem 1rem !important;
            vertical-align: middle !important;
        }

        .table tbody td {
            border: 1px solid var(--google-grey-200, #e8eaed) !important;
            padding: 0.75rem 1rem !important;
            vertical-align: middle !important;
            color: var(--google-grey-900, #202124) !important;
        }

        .table tbody tr {
            transition: background-color 0.2s ease !important;
        }

        .table tbody tr:hover {
            background-color: var(--google-grey-50, #f8f9fa) !important;
        }

        .card {
            border-radius: 0.75rem !important;
            border: 1px solid var(--google-grey-200, #e8eaed) !important;
            box-shadow: var(--elevation-2, 0 1px 3px 0 rgba(60, 64, 67, 0.08)) !important;
        }

        .card-body {
            padding: 1.5rem !important;
        }

        /* Fast Loading Optimizations */
        .container-xl {
            max-width: 1200px;
        }

        /* Preload critical fonts */
        @font-face {
            font-family: 'Google Sans';
            font-display: swap;
        }

        /* Optimize animations */
        * {
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
        }

        /* Loading states */
        .loading {
            opacity: 0.6;
            pointer-events: none;
        }

        /* Smooth transitions */
        .btn, .nav-link, .card {
            transition: all 0.15s ease-in-out;
        }

        /* Responsive optimizations */
        @media (max-width: 768px) {
            .container-xl {
                padding: 0.75rem;
            }

            .card {
                margin-bottom: 1rem;
            }

            .table-responsive {
                font-size: 0.75rem;
            }
        }
        
        /* Material Design 3 Cards */
        .card {
            background: var(--surface, #ffffff);
            border: 1px solid var(--google-grey-200, #e8eaed);
            border-radius: 0.75rem;
            box-shadow: var(--elevation-2, 0 1px 3px 0 rgba(60, 64, 67, 0.08));
            transition: var(--transition-standard, all 0.2s ease);
        }

        .card:hover {
            box-shadow: var(--elevation-3, 0 2px 6px 2px rgba(60, 64, 67, 0.15));
            transform: translateY(-1px);
        }
        
        /* Material Design 3 Form Controls */
        .btn-primary {
            background: var(--google-blue, #4285f4) !important;
            border-color: var(--google-blue, #4285f4) !important;
            border-radius: 1.25rem !important; /* Material Design pill button */
            font-family: 'Google Sans', sans-serif !important;
            font-weight: 500 !important;
            font-size: 0.875rem !important;
            padding: 0.625rem 1.5rem !important;
            min-height: 2.5rem !important;
            letter-spacing: 0.1px !important;
            transition: var(--transition-standard, all 0.2s ease) !important;
        }
        
        .btn-primary:hover {
            background: var(--google-blue-700, #1967d2) !important;
            border-color: var(--google-blue-700, #1967d2) !important;
            box-shadow: var(--elevation-2, 0 1px 3px 0 rgba(60, 64, 67, 0.08)) !important;
        }

        .btn-primary:active {
            background: var(--google-blue-800, #185abc) !important;
            border-color: var(--google-blue-800, #185abc) !important;
            box-shadow: var(--elevation-1, 0 1px 2px 0 rgba(60, 64, 67, 0.08)) !important;
        }
        
        .form-control {
            border: 1px solid var(--google-grey-300, #dadce0) !important;
            border-radius: 0.5rem !important;
            font-family: 'Google Sans', sans-serif !important;
            font-size: 0.875rem !important;
            padding: 0.75rem 1rem !important;
            background: var(--surface, #ffffff) !important;
            color: var(--on-surface, #202124) !important;
            transition: var(--transition-standard, all 0.2s ease) !important;
        }
        
        .form-control:focus {
            border-color: var(--google-blue, #4285f4) !important;
            box-shadow: 0 0 0 1px var(--google-blue, #4285f4) !important;
            outline: none !important;
        }

        .form-control:hover {
            border-color: var(--google-grey-600, #5f6368) !important;
        }

        .form-control::placeholder {
            color: var(--google-grey-500, #9aa0a6) !important;
        }
        
        /* Material Design 3 Additional Button Styles */
        .btn {
            font-family: 'Google Sans', sans-serif !important;
            font-weight: 500 !important;
            border-radius: 1.25rem !important;
            transition: var(--transition-standard, all 0.2s ease) !important;
            letter-spacing: 0.1px !important;
        }

        .btn-outline-primary {
            color: var(--google-blue, #4285f4) !important;
            border-color: var(--google-grey-300, #dadce0) !important;
        }

        .btn-outline-primary:hover {
            background: var(--google-blue-50, #e8f0fe) !important;
            border-color: var(--google-blue, #4285f4) !important;
            color: var(--google-blue-700, #1967d2) !important;
        }

        .btn-success {
            background: var(--google-green, #34a853) !important;
            border-color: var(--google-green, #34a853) !important;
        }

        .btn-success:hover {
            background: var(--google-green-700, #0d652d) !important;
            border-color: var(--google-green-700, #0d652d) !important;
        }

        .btn-danger {
            background: var(--google-red, #ea4335) !important;
            border-color: var(--google-red, #ea4335) !important;
        }

        .btn-danger:hover {
            background: var(--google-red-700, #c5221f) !important;
            border-color: var(--google-red-700, #c5221f) !important;
        }

        .btn-warning {
            background: var(--google-yellow, #fbbc04) !important;
            border-color: var(--google-yellow, #fbbc04) !important;
            color: var(--google-grey-900, #202124) !important;
        }

        .btn-warning:hover {
            background: var(--google-yellow-700, #f29900) !important;
            border-color: var(--google-yellow-700, #f29900) !important;
            color: var(--google-grey-900, #202124) !important;
        }

        /* Material Design 3 Form Labels */
        .form-label {
            font-family: 'Google Sans', sans-serif !important;
            font-weight: 500 !important;
            color: var(--google-grey-700, #3c4043) !important;
            margin-bottom: 0.375rem !important;
        }

        /* Material Design 3 Typography Classes */
        .google-display {
            font-family: 'Google Sans', sans-serif !important;
            font-size: 2.25rem !important;
            font-weight: 400 !important;
            line-height: 1.2 !important;
            letter-spacing: -0.25px !important;
            margin: 0 0 1rem 0 !important;
            color: var(--on-surface, #202124) !important;
        }

        .google-headline {
            font-family: 'Google Sans', sans-serif !important;
            font-size: 1.75rem !important;
            font-weight: 400 !important;
            line-height: 1.25 !important;
            letter-spacing: -0.25px !important;
            margin: 0 0 0.75rem 0 !important;
            color: var(--on-surface, #202124) !important;
        }

        .google-title {
            font-family: 'Google Sans', sans-serif !important;
            font-size: 1.375rem !important;
            font-weight: 400 !important;
            line-height: 1.3 !important;
            margin: 0 0 0.5rem 0 !important;
            color: var(--on-surface, #202124) !important;
        }

        .google-title-medium {
            font-family: 'Google Sans', sans-serif !important;
            font-size: 1.125rem !important;
            font-weight: 500 !important;
            line-height: 1.4 !important;
            margin: 0 0 0.5rem 0 !important;
            color: var(--on-surface, #202124) !important;
        }

        .google-title-small {
            font-family: 'Google Sans', sans-serif !important;
            font-size: 1rem !important;
            font-weight: 500 !important;
            line-height: 1.5 !important;
            margin: 0 0 0.25rem 0 !important;
            color: var(--on-surface, #202124) !important;
        }

        .google-body {
            font-family: 'Google Sans', sans-serif !important;
            font-size: 1rem !important;
            font-weight: 400 !important;
            line-height: 1.5 !important;
            margin: 0 0 1rem 0 !important;
            color: var(--on-surface, #202124) !important;
        }

        .google-body-small {
            font-family: 'Google Sans', sans-serif !important;
            font-size: 0.875rem !important;
            font-weight: 400 !important;
            line-height: 1.4 !important;
            color: var(--on-surface-variant, #5f6368) !important;
        }

        .google-caption {
            font-family: 'Google Sans', sans-serif !important;
            font-size: 0.75rem !important;
            font-weight: 400 !important;
            line-height: 1.3 !important;
            letter-spacing: 0.4px !important;
            color: var(--on-surface-variant, #5f6368) !important;
        }

        /* Override Bootstrap headings with Material Design typography */
        h1 {
            font-family: 'Google Sans', sans-serif !important;
            font-size: 2.25rem !important;
            font-weight: 400 !important;
            line-height: 1.2 !important;
            letter-spacing: -0.25px !important;
            color: var(--on-surface, #202124) !important;
        }

        h2 {
            font-family: 'Google Sans', sans-serif !important;
            font-size: 1.75rem !important;
            font-weight: 400 !important;
            line-height: 1.25 !important;
            letter-spacing: -0.25px !important;
            color: var(--on-surface, #202124) !important;
        }

        h3 {
            font-family: 'Google Sans', sans-serif !important;
            font-size: 1.375rem !important;
            font-weight: 400 !important;
            line-height: 1.3 !important;
            color: var(--on-surface, #202124) !important;
        }

        h4 {
            font-family: 'Google Sans', sans-serif !important;
            font-size: 1.125rem !important;
            font-weight: 500 !important;
            line-height: 1.4 !important;
            color: var(--on-surface, #202124) !important;
        }

        h5 {
            font-family: 'Google Sans', sans-serif !important;
            font-size: 1rem !important;
            font-weight: 500 !important;
            line-height: 1.5 !important;
            color: var(--on-surface, #202124) !important;
        }

        h6 {
            font-family: 'Google Sans', sans-serif !important;
            font-size: 0.875rem !important;
            font-weight: 500 !important;
            line-height: 1.5 !important;
            letter-spacing: 0.1px !important;
            color: var(--on-surface, #202124) !important;
        }

        p {
            font-family: 'Google Sans', sans-serif !important;
            font-size: 1rem !important;
            font-weight: 400 !important;
            line-height: 1.5 !important;
            color: var(--on-surface, #202124) !important;
        }

        /* Material Design 3 helper text styles */
        .text-muted,
        .text-secondary {
            color: var(--on-surface-variant, #5f6368) !important;
        }

        /* Responsive design */
        @media (max-width: 768px) {
            .navbar-brand span {
                display: none;
            }

            .nav-link {
                padding: 0.5rem !important;
            }

            .btn {
                padding: 0.75rem 1.25rem !important;
            }

            /* Responsive typography */
            .google-display {
                font-size: 1.875rem !important;
            }

            .google-headline {
                font-size: 1.5rem !important;
            }

            .google-title {
                font-size: 1.25rem !important;
            }

            h1 {
                font-size: 1.875rem !important;
            }

            h2 {
                font-size: 1.5rem !important;
            }

            h3 {
                font-size: 1.25rem !important;
            }
        }
    </style>
</head>
<body>
    <script src="https://cdn.jsdelivr.net/npm/@tabler/core@1.0.0-beta17/dist/js/demo-theme.min.js"></script>
    
    <div class="page">
        @auth
        <!-- Top Navigation Bar -->
        <nav class="navbar navbar-expand-lg sticky-top" style="background: linear-gradient(90deg, #ffffff 0%, #f8f9fa 100%); border-bottom: 1px solid #e0e0e0; box-shadow: 0 2px 4px rgba(0,0,0,0.1); padding: 0.5rem 0; z-index: 1000;">
            <div class="container-fluid px-4">
                <!-- Brand Logo -->
                <a class="navbar-brand d-flex align-items-center" href="{{ url('/home') }}" style="text-decoration: none;">
                    <img src="{{ asset('images/logo.jpg') }}" height="40" alt="Restaurant Logo" style="border-radius: 8px;">
                </a>

                <!-- Mobile menu toggle -->
                <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation" style="box-shadow: none;">
                    <span style="display: block; width: 25px; height: 3px; background: #333; margin: 5px 0; transition: 0.3s;"></span>
                    <span style="display: block; width: 25px; height: 3px; background: #333; margin: 5px 0; transition: 0.3s;"></span>
                    <span style="display: block; width: 25px; height: 3px; background: #333; margin: 5px 0; transition: 0.3s;"></span>
                </button>

                <!-- Navigation Menu -->
                <div class="collapse navbar-collapse" id="navbarNav">
                    <!-- Main Navigation Links -->
                    <ul class="navbar-nav me-auto mb-2 mb-lg-0" style="gap: 0.25rem;">
                        <!-- Dashboard -->
                        <li class="nav-item">
                            <a class="nav-link d-flex align-items-center {{ request()->routeIs('home') ? 'active' : '' }}" href="{{ route('home') }}" style="padding: 8px 16px; border-radius: 20px; font-family: 'Google Sans', sans-serif; font-weight: 500; font-size: 14px; transition: all 0.2s ease; {{ request()->routeIs('home') ? 'background: #4285f4; color: white;' : 'color: #5f6368;' }}" onmouseover="if(!this.classList.contains('active')) { this.style.background='#f1f3f4'; this.style.color='#1a73e8'; }" onmouseout="if(!this.classList.contains('active')) { this.style.background='transparent'; this.style.color='#5f6368'; }">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="me-2">
                                    <path d="M3 9l9-7 9 7v11a2 2 0 01-2 2H5a2 2 0 01-2-2z"/>
                                    <polyline points="9,22 9,12 15,12 15,22"/>
                                </svg>
                                Dashboard
                            </a>
                        </li>

                        <!-- Stores (Admin Only) -->
                        @if(Auth::user()->hasPermission('manage_stores'))
                        <li class="nav-item">
                            <a class="nav-link d-flex align-items-center {{ request()->routeIs('stores.*') ? 'active' : '' }}" href="{{ route('stores.index') }}" style="padding: 8px 16px; border-radius: 20px; font-family: 'Google Sans', sans-serif; font-weight: 500; font-size: 14px; transition: all 0.2s ease; {{ request()->routeIs('stores.*') ? 'background: #4285f4; color: white;' : 'color: #5f6368;' }}" onmouseover="if(!this.classList.contains('active')) { this.style.background='#f1f3f4'; this.style.color='#1a73e8'; }" onmouseout="if(!this.classList.contains('active')) { this.style.background='transparent'; this.style.color='#5f6368'; }">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="me-2">
                                    <path d="M2 3h6a4 4 0 014 4v14a3 3 0 00-3-3H2z"/>
                                    <path d="M22 3h-6a4 4 0 00-4 4v14a3 3 0 013-3h7z"/>
                                </svg>
                                Stores
                            </a>
                        </li>
                        @endif

                        <!-- Owners -->
                        @if(Auth::user()->hasPermission('manage_owners'))
                        <li class="nav-item">
                            <a class="nav-link d-flex align-items-center {{ request()->routeIs('owners.*') ? 'active' : '' }}" href="{{ route('owners.index') }}" style="padding: 8px 16px; border-radius: 20px; font-family: 'Google Sans', sans-serif; font-weight: 500; font-size: 14px; transition: all 0.2s ease; {{ request()->routeIs('owners.*') ? 'background: #4285f4; color: white;' : 'color: #5f6368;' }}" onmouseover="if(!this.classList.contains('active')) { this.style.background='#f1f3f4'; this.style.color='#1a73e8'; }" onmouseout="if(!this.classList.contains('active')) { this.style.background='transparent'; this.style.color='#5f6368'; }">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="me-2">
                                    <circle cx="12" cy="7" r="4"/>
                                    <path d="M6 21v-2a4 4 0 014-4h4a4 4 0 014 4v2"/>
                                </svg>
                                Owners
                            </a>
                        </li>
                        @endif

                        <!-- Managers -->
                        @if(Auth::user()->hasPermission('manage_managers'))
                        <li class="nav-item">
                            <a class="nav-link d-flex align-items-center {{ request()->routeIs('managers.*') ? 'active' : '' }}" href="{{ route('managers.index') }}" style="padding: 8px 16px; border-radius: 20px; font-family: 'Google Sans', sans-serif; font-weight: 500; font-size: 14px; transition: all 0.2s ease; {{ request()->routeIs('managers.*') ? 'background: #4285f4; color: white;' : 'color: #5f6368;' }}" onmouseover="if(!this.classList.contains('active')) { this.style.background='#f1f3f4'; this.style.color='#1a73e8'; }" onmouseout="if(!this.classList.contains('active')) { this.style.background='transparent'; this.style.color='#5f6368'; }">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="me-2">
                                    <path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/>
                                    <circle cx="9" cy="7" r="4"/>
                                    <path d="M23 21v-2a4 4 0 00-3-3.87"/>
                                    <path d="M16 3.13a4 4 0 010 7.75"/>
                                </svg>
                                Managers
                            </a>
                        </li>
                        @endif

                        <!-- Transaction Management -->
                        @if(Auth::user()->hasPermission('manage_transaction_types'))
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle d-flex align-items-center {{ request()->routeIs('transaction-types.*') || request()->routeIs('revenue-income-types.*') || request()->routeIs('admin.coa.*') || request()->routeIs('admin.vendors.*') || request()->routeIs('admin.expenses.*') ? 'active' : '' }}" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false" style="padding: 8px 16px; border-radius: 20px; font-family: 'Google Sans', sans-serif; font-weight: 500; font-size: 14px; transition: all 0.2s ease; {{ request()->routeIs('transaction-types.*') || request()->routeIs('revenue-income-types.*') || request()->routeIs('admin.coa.*') || request()->routeIs('admin.vendors.*') || request()->routeIs('admin.expenses.*') ? 'background: #4285f4; color: white;' : 'color: #5f6368;' }}" onmouseover="if(!this.classList.contains('active')) { this.style.background='#f1f3f4'; this.style.color='#1a73e8'; }" onmouseout="if(!this.classList.contains('active')) { this.style.background='transparent'; this.style.color='#5f6368'; }">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="me-2">
                                    <rect x="1" y="4" width="22" height="16" rx="2" ry="2"/>
                                    <line x1="1" y1="10" x2="23" y2="10"/>
                                </svg>
                                Transactions
                            </a>
                            <ul class="dropdown-menu" style="border-radius: 12px; border: 1px solid #e0e0e0; box-shadow: 0 4px 12px rgba(0,0,0,0.15); padding: 8px 0;">
                                <li><a class="dropdown-item d-flex align-items-center" href="{{ route('transaction-types.index') }}" style="padding: 8px 16px; font-family: 'Google Sans', sans-serif; font-size: 14px; border-radius: 8px; margin: 0 8px;">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="me-2">
                                        <rect x="3" y="4" width="18" height="18" rx="2" ry="2"/>
                                        <line x1="16" y1="2" x2="16" y2="6"/>
                                        <line x1="8" y1="2" x2="8" y2="6"/>
                                        <line x1="3" y1="10" x2="21" y2="10"/>
                                    </svg>
                                    Transaction Types
                                </a></li>
                                <li><a class="dropdown-item d-flex align-items-center" href="{{ route('revenue-income-types.index') }}" style="padding: 8px 16px; font-family: 'Google Sans', sans-serif; font-size: 14px; border-radius: 8px; margin: 0 8px;">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="me-2">
                                        <polyline points="22,12 18,12 15,21 9,3 6,12 2,12"/>
                                    </svg>
                                    Revenue Types
                                </a></li>
                                <li><hr class="dropdown-divider" style="margin: 8px 0;"></li>
                                <li><a class="dropdown-item d-flex align-items-center" href="{{ route('admin.coa.index') }}" style="padding: 8px 16px; font-family: 'Google Sans', sans-serif; font-size: 14px; border-radius: 8px; margin: 0 8px;">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="me-2">
                                        <path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/>
                                        <polyline points="14,2 14,8 20,8"/>
                                        <line x1="16" y1="13" x2="8" y2="13"/>
                                        <line x1="16" y1="17" x2="8" y2="17"/>
                                        <line x1="10" y1="9" x2="8" y2="9"/>
                                    </svg>
                                    Chart of Accounts
                                </a></li>
                                <li><a class="dropdown-item d-flex align-items-center" href="{{ route('admin.vendors.index') }}" style="padding: 8px 16px; font-family: 'Google Sans', sans-serif; font-size: 14px; border-radius: 8px; margin: 0 8px;">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="me-2">
                                        <path d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                                    </svg>
                                    Vendors
                                </a></li>
                                <li><hr class="dropdown-divider" style="margin: 8px 0;"></li>
                                <li><a class="dropdown-item d-flex align-items-center" href="{{ route('admin.expenses.index') }}" style="padding: 8px 16px; font-family: 'Google Sans', sans-serif; font-size: 14px; border-radius: 8px; margin: 0 8px;">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="me-2">
                                        <line x1="12" y1="1" x2="12" y2="23"/>
                                        <path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/>
                                    </svg>
                                    Expense Ledger
                                </a></li>
                                <li><a class="dropdown-item d-flex align-items-center" href="{{ route('admin.bank.accounts.index') }}" style="padding: 8px 16px; font-family: 'Google Sans', sans-serif; font-size: 14px; border-radius: 8px; margin: 0 8px;">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="me-2">
                                        <rect x="2" y="6" width="20" height="12" rx="2"/>
                                        <path d="M6 10h.01M10 10h.01"/>
                                    </svg>
                                    Bank Accounts
                                </a></li>
                                <li><a class="dropdown-item d-flex align-items-center" href="{{ route('admin.merchant-fees.index') }}" style="padding: 8px 16px; font-family: 'Google Sans', sans-serif; font-size: 14px; border-radius: 8px; margin: 0 8px;">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="me-2">
                                        <line x1="12" y1="1" x2="12" y2="23"/>
                                        <path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/>
                                    </svg>
                                    Merchant Fee Analytics
                                </a></li>
                                @if(Auth::user()->isAdmin() || Auth::user()->isOwner())
                                <li><a class="dropdown-item d-flex align-items-center" href="{{ route('admin.reports.profit-loss.index') }}" style="padding: 8px 16px; font-family: 'Google Sans', sans-serif; font-size: 14px; border-radius: 8px; margin: 0 8px;">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="me-2">
                                        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                                        <polyline points="14,2 14,8 20,8"/>
                                        <line x1="16" y1="13" x2="8" y2="13"/>
                                        <line x1="16" y1="17" x2="8" y2="17"/>
                                        <polyline points="10,9 9,9 8,9"/>
                                    </svg>
                                    P&L Reports
                                </a></li>
                                @endif
                            </ul>
                        </li>
                        @endif

                        <!-- Daily Reports -->
                        @if(Auth::user()->hasPermission('create_reports'))
                        <li class="nav-item">
                            <a class="nav-link d-flex align-items-center {{ request()->routeIs('daily-reports.*') ? 'active' : '' }}" href="{{ route('daily-reports.index') }}" style="padding: 8px 16px; border-radius: 20px; font-family: 'Google Sans', sans-serif; font-weight: 500; font-size: 14px; transition: all 0.2s ease; {{ request()->routeIs('daily-reports.*') ? 'background: #4285f4; color: white;' : 'color: #5f6368;' }}" onmouseover="if(!this.classList.contains('active')) { this.style.background='#f1f3f4'; this.style.color='#1a73e8'; }" onmouseout="if(!this.classList.contains('active')) { this.style.background='transparent'; this.style.color='#5f6368'; }">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="me-2">
                                    <path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/>
                                    <polyline points="14,2 14,8 20,8"/>
                                    <line x1="16" y1="13" x2="8" y2="13"/>
                                    <line x1="16" y1="17" x2="8" y2="17"/>
                                </svg>
                                Daily Reports
                            </a>
                        </li>
                        @endif
                        @if(Auth::user()->hasPermission('view_reports'))
                        <li class="nav-item">
                            <a class="nav-link d-flex align-items-center {{ request()->routeIs('reports.*') ? 'active' : '' }}" href="{{ route('reports.index') }}" style="padding: 8px 16px; border-radius: 20px; font-family: 'Google Sans', sans-serif; font-weight: 500; font-size: 14px; transition: all 0.2s ease; {{ request()->routeIs('daily-reports.*') ? 'background: #4285f4; color: white;' : 'color: #5f6368;' }}" onmouseover="if(!this.classList.contains('active')) { this.style.background='#f1f3f4'; this.style.color='#1a73e8'; }" onmouseout="if(!this.classList.contains('active')) { this.style.background='transparent'; this.style.color='#5f6368'; }">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="me-2">
                                    <path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/>
                                    <polyline points="14,2 14,8 20,8"/>
                                    <line x1="16" y1="13" x2="8" y2="13"/>
                                    <line x1="16" y1="17" x2="8" y2="17"/>
                                </svg>
                                Reports
                            </a>
                        </li>
                        @endif
                    </ul>

                    <!-- User Profile Menu -->
                    <div class="d-flex align-items-center">
                        <div class="dropdown">
                            @if(Auth::user()->avatar_url && Auth::user()->name && Auth::user()->email)
                            <a href="#" class="d-flex align-items-center text-decoration-none dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false" style="padding: 6px 12px; border-radius: 25px; transition: all 0.2s ease;" onmouseover="this.style.background='#f1f3f4'" onmouseout="this.style.background='transparent'">
                                <img src="{{ Auth::user()->avatar_url }}" alt="{{ Auth::user()->name }}" class="rounded-circle me-2" style="width: 32px; height: 32px; object-fit: cover; border: 2px solid #e0e0e0;">
                                <div class="d-none d-lg-block text-start">
                                    <div style="font-family: 'Google Sans', sans-serif; font-size: 14px; font-weight: 500; color: #202124; line-height: 1.2;">{{ Auth::user()->name }}</div>
                                    @if(Auth::user()->isManager() && Auth::user()->store)
                                        <div style="font-family: 'Google Sans', sans-serif; font-size: 11px; color: #1967d2; line-height: 1.2; font-weight: 500;">{{ Auth::user()->store->store_info }}</div>
                                    @endif
                                    <div style="font-family: 'Google Sans', sans-serif; font-size: 12px; color: #5f6368; line-height: 1.2;">{{ Auth::user()->email }}</div>
                                </div>
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#5f6368" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="ms-1">
                                    <polyline points="6,9 12,15 18,9"/>
                                </svg>
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end" style="border-radius: 12px; border: 1px solid #e0e0e0; box-shadow: 0 4px 12px rgba(0,0,0,0.15); padding: 8px 0; min-width: 200px;">
                                <li><a class="dropdown-item d-flex align-items-center" href="{{ route('profile.show') }}" style="padding: 10px 16px; font-family: 'Google Sans', sans-serif; font-size: 14px; border-radius: 8px; margin: 0 8px;">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="me-3">
                                        <circle cx="12" cy="7" r="4"/>
                                        <path d="M6 21v-2a4 4 0 014-4h4a4 4 0 014 4v2"/>
                                    </svg>
                                    Profile Settings
                                </a></li>
                                <li><hr class="dropdown-divider" style="margin: 8px 0;"></li>
                                <li>
                                    <form action="{{ route('logout') }}" method="POST" class="m-0">
                                        @csrf
                                        <button type="submit" class="dropdown-item d-flex align-items-center text-danger" style="padding: 10px 16px; font-family: 'Google Sans', sans-serif; font-size: 14px; border-radius: 8px; margin: 0 8px; border: none; background: none; width: calc(100% - 16px);">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="me-3">
                                                <path d="M9 21H5a2 2 0 01-2-2V5a2 2 0 012-2h4"/>
                                                <polyline points="16,17 21,12 16,7"/>
                                                <line x1="21" y1="12" x2="9" y2="12"/>
                                            </svg>
                                            Sign Out
                                        </button>
                                    </form>
                                </li>
                            </ul>
                            @else
                            <span class="badge bg-danger" style="font-family: 'Google Sans', sans-serif; font-size: 12px;">User data incomplete</span>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </nav>
        @endauth

        <!-- Impersonation Banner -->
        @if(Session::has('impersonating_admin_id') && Session::has('impersonating_user_id'))
        @php
            $originalAdmin = \App\Models\User::find(Session::get('impersonating_admin_id'));
            $impersonatedUser = \App\Models\User::find(Session::get('impersonating_user_id'));
        @endphp
        <div class="alert alert-warning d-flex justify-content-between align-items-center m-0" style="background: linear-gradient(90deg, #fef7cd 0%, #fff3b0 100%); border: none; border-radius: 0; padding: 12px 24px; border-bottom: 2px solid #f9ca24;">
            <div class="d-flex align-items-center">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#f57c00" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="me-3">
                    <path d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z"/>
                </svg>
                <div>
                    <strong style="font-family: 'Google Sans', sans-serif; color: #e65100;">⚡ You are impersonating {{ $impersonatedUser->name ?? 'Unknown User' }}</strong>
                    <div style="font-family: 'Google Sans', sans-serif; font-size: 13px; color: #bf360c;">
                        Logged in as {{ $impersonatedUser->email ?? 'unknown@email.com' }}
                        ({{ ucfirst($impersonatedUser->role?->value ?? 'unknown') }})
                        • Original admin: {{ $originalAdmin->name ?? 'Unknown Admin' }}
                    </div>
                </div>
            </div>
            <form method="POST" action="{{ route('impersonate.stop') }}" class="m-0">
                @csrf
                <button type="submit" class="btn btn-sm btn-outline-danger" style="font-family: 'Google Sans', sans-serif; border-radius: 20px; padding: 6px 16px;" title="Stop impersonating and return to admin account">
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="me-1">
                        <path d="M18 6 6 18"/>
                        <path d="m6 6 12 12"/>
                    </svg>
                    Exit Impersonation
                </button>
            </form>
        </div>
        @endif

        <!-- Main Content Area -->
        <div class="main-content" style="min-height: calc(100vh - 80px); background: #f8f9fa; padding-top: 1rem;">
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
    
    <!-- Performance Optimizations -->
    <script>
        // Defer non-critical scripts
        document.addEventListener('DOMContentLoaded', function() {
            // Add loading states to buttons
            document.querySelectorAll('form').forEach(form => {
                form.addEventListener('submit', function() {
                    const submitBtn = form.querySelector('button[type="submit"]');
                    if (submitBtn && !submitBtn.disabled) {
                        submitBtn.classList.add('loading');
                        submitBtn.disabled = true;

                        // Re-enable after a timeout as fallback
                        setTimeout(() => {
                            submitBtn.classList.remove('loading');
                            submitBtn.disabled = false;
                        }, 10000);
                    }
                });
            });

            // Optimize table interactions
            document.querySelectorAll('.table tbody tr').forEach(row => {
                row.addEventListener('mouseenter', function() {
                    this.style.transform = 'scale(1.01)';
                });
                row.addEventListener('mouseleave', function() {
                    this.style.transform = '';
                });
            });

            // Lazy load images if any
            if ('IntersectionObserver' in window) {
                const imageObserver = new IntersectionObserver((entries) => {
                    entries.forEach(entry => {
                        if (entry.isIntersecting) {
                            const img = entry.target;
                            if (img.dataset.src) {
                                img.src = img.dataset.src;
                                img.removeAttribute('data-src');
                                imageObserver.unobserve(img);
                            }
                        }
                    });
                });

                document.querySelectorAll('img[data-src]').forEach(img => {
                    imageObserver.observe(img);
                });
            }
        });

        // Optimize page visibility changes
        document.addEventListener('visibilitychange', function() {
            if (document.hidden) {
                // Pause any intensive operations when page is hidden
                document.querySelectorAll('video, audio').forEach(media => {
                    if (!media.paused) media.pause();
                });
            }
        });
    </script>

    <!-- Additional scripts -->
    @stack('scripts')
</body>
</html>