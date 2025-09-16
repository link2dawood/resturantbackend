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

    <!-- Google Material Design 3 Dashboard -->
    <link href="{{ asset('css/google-dashboard.css') }}" rel="stylesheet"/>

    <!-- Additional styles -->
    @stack('styles')
</head>
<body>
    <div class="gd-dashboard">
        @auth
        <!-- Top Navigation Header -->
        <header role="banner" style="background: var(--surface, #ffffff); border-bottom: 1px solid var(--google-grey-200, #e8eaed); box-shadow: var(--elevation-2, 0 1px 3px 0 rgba(60, 64, 67, 0.08)); position: sticky; top: 0; z-index: 1000;">
            <div style="max-width: 1200px; margin: 0 auto; padding: 0.75rem 1.5rem; display: flex; align-items: center; justify-content: space-between;">
                <!-- Brand -->
                <a href="{{ route('home') }}" style="display: flex; align-items: center; text-decoration: none; margin-right: auto; margin-left: 20px">
                    <span class="material-symbols-outlined" style="font-size: 28px; color: var(--google-blue, #4285f4);">restaurant_menu</span>
                </a>
                
                <!-- Mobile Menu Toggle -->
                <button id="mobile-menu-toggle" style="display: none; background: none; border: none; padding: 0.5rem; border-radius: 0.5rem; color: var(--google-grey-700, #3c4043);" aria-label="Toggle navigation menu">
                    <span class="material-symbols-outlined">menu</span>
                </button>

                <!-- Navigation Links -->
                <nav style="display: flex; align-items: center; gap: 0.5rem;" id="main-nav">
                    <!-- Dashboard -->
                    <a href="{{ route('home') }}" style="display: flex; align-items: center; padding: 0.5rem 1rem; border-radius: 1.25rem; text-decoration: none; font-family: 'Google Sans', sans-serif; font-size: 0.875rem; font-weight: 500; color: {{ request()->routeIs('home') ? 'white' : 'var(--google-grey-700, #3c4043)' }}; background: {{ request()->routeIs('home') ? 'var(--google-blue, #4285f4)' : 'transparent' }}; transition: all 0.2s ease;"
                       onmouseover="if (!this.style.background.includes('#4285f4')) { this.style.background = 'var(--google-blue-50, #e8f0fe)'; this.style.color = 'var(--google-blue-700, #1967d2)'; }"
                       onmouseout="if (!this.style.background.includes('#4285f4')) { this.style.background = 'transparent'; this.style.color = 'var(--google-grey-700, #3c4043)'; }">
                        <span class="material-symbols-outlined" style="font-size: 16px; margin-right: 0.25rem;">dashboard</span>
                        Dashboard
                    </a>

                    <!-- Stores -->
                    <a href="{{ route('stores.index') }}" style="display: flex; align-items: center; padding: 0.5rem 1rem; border-radius: 1.25rem; text-decoration: none; font-family: 'Google Sans', sans-serif; font-size: 0.875rem; font-weight: 500; color: {{ request()->routeIs('stores.*') ? 'white' : 'var(--google-grey-700, #3c4043)' }}; background: {{ request()->routeIs('stores.*') ? 'var(--google-blue, #4285f4)' : 'transparent' }}; transition: all 0.2s ease;"
                       onmouseover="if (!this.style.background.includes('#4285f4')) { this.style.background = 'var(--google-blue-50, #e8f0fe)'; this.style.color = 'var(--google-blue-700, #1967d2)'; }"
                       onmouseout="if (!this.style.background.includes('#4285f4')) { this.style.background = 'transparent'; this.style.color = 'var(--google-grey-700, #3c4043)'; }">
                        <span class="material-symbols-outlined" style="font-size: 16px; margin-right: 0.25rem;">store</span>
                        Stores
                    </a>

                    <!-- Owners -->
                    @if(Auth::user()->hasPermission('manage_owners'))
                    <a href="{{ route('owners.index') }}" style="display: flex; align-items: center; padding: 0.5rem 1rem; border-radius: 1.25rem; text-decoration: none; font-family: 'Google Sans', sans-serif; font-size: 0.875rem; font-weight: 500; color: {{ request()->routeIs('owners.*') ? 'white' : 'var(--google-grey-700, #3c4043)' }}; background: {{ request()->routeIs('owners.*') ? 'var(--google-blue, #4285f4)' : 'transparent' }}; transition: all 0.2s ease;"
                       onmouseover="if (!this.style.background.includes('#4285f4')) { this.style.background = 'var(--google-blue-50, #e8f0fe)'; this.style.color = 'var(--google-blue-700, #1967d2)'; }"
                       onmouseout="if (!this.style.background.includes('#4285f4')) { this.style.background = 'transparent'; this.style.color = 'var(--google-grey-700, #3c4043)'; }">
                        <span class="material-symbols-outlined" style="font-size: 16px; margin-right: 0.25rem;">admin_panel_settings</span>
                        Owners
                    </a>
                    @endif

                    <!-- Managers -->
                    @if(Auth::user()->hasPermission('manage_managers'))
                    <a href="{{ route('managers.index') }}" style="display: flex; align-items: center; padding: 0.5rem 1rem; border-radius: 1.25rem; text-decoration: none; font-family: 'Google Sans', sans-serif; font-size: 0.875rem; font-weight: 500; color: {{ request()->routeIs('managers.*') ? 'white' : 'var(--google-grey-700, #3c4043)' }}; background: {{ request()->routeIs('managers.*') ? 'var(--google-blue, #4285f4)' : 'transparent' }}; transition: all 0.2s ease;"
                       onmouseover="if (!this.style.background.includes('#4285f4')) { this.style.background = 'var(--google-blue-50, #e8f0fe)'; this.style.color = 'var(--google-blue-700, #1967d2)'; }"
                       onmouseout="if (!this.style.background.includes('#4285f4')) { this.style.background = 'transparent'; this.style.color = 'var(--google-grey-700, #3c4043)'; }">
                        <span class="material-symbols-outlined" style="font-size: 16px; margin-right: 0.25rem;">manage_accounts</span>
                        Managers
                    </a>
                    @endif

                    <!-- Transaction Management Dropdown -->
                    @if(Auth::user()->hasPermission('manage_transaction_types'))
                    <div style="position: relative;">
                        <button onclick="toggleTransactionDropdown()" style="display: flex; align-items: center; padding: 0.5rem 1rem; border-radius: 1.25rem; background: {{ request()->routeIs('transaction-types.*') || request()->routeIs('revenue-income-types.*') ? 'var(--google-blue, #4285f4)' : 'transparent' }}; color: {{ request()->routeIs('transaction-types.*') || request()->routeIs('revenue-income-types.*') ? 'white' : 'var(--google-grey-700, #3c4043)' }}; border: none; font-family: 'Google Sans', sans-serif; font-size: 0.875rem; font-weight: 500; cursor: pointer; transition: all 0.2s ease;"
                                onmouseover="if (!this.style.background.includes('#4285f4')) { this.style.background = 'var(--google-blue-50, #e8f0fe)'; this.style.color = 'var(--google-blue-700, #1967d2)'; }"
                                onmouseout="if (!this.style.background.includes('#4285f4')) { this.style.background = 'transparent'; this.style.color = 'var(--google-grey-700, #3c4043)'; }">
                            <span class="material-symbols-outlined" style="font-size: 16px; margin-right: 0.25rem;">account_balance_wallet</span>
                            Transactions
                            <span class="material-symbols-outlined" style="font-size: 16px; margin-left: 0.25rem;">expand_more</span>
                        </button>
                        <div id="transaction-dropdown" style="position: absolute; top: 100%; right: 0; background: white; border-radius: 0.75rem; box-shadow: 0 2px 6px 2px rgba(60, 64, 67, 0.15); border: 1px solid var(--google-grey-200, #e8eaed); padding: 0.5rem 0; margin-top: 0.5rem; min-width: 200px; display: none; z-index: 1000;">
                            <a href="{{ route('transaction-types.index') }}" style="display: flex; align-items: center; padding: 0.75rem 1rem; text-decoration: none; color: var(--on-surface, #202124); font-family: 'Google Sans', sans-serif; font-size: 0.875rem; transition: background-color 0.2s ease;"
                               onmouseover="this.style.background = 'var(--google-grey-50, #f8f9fa)'"
                               onmouseout="this.style.background = 'transparent'">
                                <span class="material-symbols-outlined" style="font-size: 16px; margin-right: 0.5rem;">category</span>
                                Transaction Types
                            </a>
                            <a href="{{ route('revenue-income-types.index') }}" style="display: flex; align-items: center; padding: 0.75rem 1rem; text-decoration: none; color: var(--on-surface, #202124); font-family: 'Google Sans', sans-serif; font-size: 0.875rem; transition: background-color 0.2s ease;"
                               onmouseover="this.style.background = 'var(--google-grey-50, #f8f9fa)'"
                               onmouseout="this.style.background = 'transparent'">
                                <span class="material-symbols-outlined" style="font-size: 16px; margin-right: 0.5rem;">trending_up</span>
                                Revenue Types
                            </a>
                        </div>
                    </div>
                    @endif

                    <!-- Daily Reports -->
                    @if(Auth::user()->hasPermission('create_reports'))
                    <a href="{{ route('daily-reports.index') }}" style="display: flex; align-items: center; padding: 0.5rem 1rem; border-radius: 1.25rem; text-decoration: none; font-family: 'Google Sans', sans-serif; font-size: 0.875rem; font-weight: 500; color: {{ request()->routeIs('daily-reports.*') ? 'white' : 'var(--google-grey-700, #3c4043)' }}; background: {{ request()->routeIs('daily-reports.*') ? 'var(--google-blue, #4285f4)' : 'transparent' }}; transition: all 0.2s ease;"
                       onmouseover="if (!this.style.background.includes('#4285f4')) { this.style.background = 'var(--google-blue-50, #e8f0fe)'; this.style.color = 'var(--google-blue-700, #1967d2)'; }"
                       onmouseout="if (!this.style.background.includes('#4285f4')) { this.style.background = 'transparent'; this.style.color = 'var(--google-grey-700, #3c4043)'; }">
                        <span class="material-symbols-outlined" style="font-size: 16px; margin-right: 0.25rem;">assessment</span>
                        Daily Reports
                    </a>
                    @endif

                    <!-- US States -->
                    <a href="{{ route('states.index') }}" style="display: flex; align-items: center; padding: 0.5rem 1rem; border-radius: 1.25rem; text-decoration: none; font-family: 'Google Sans', sans-serif; font-size: 0.875rem; font-weight: 500; color: {{ request()->routeIs('states.*') ? 'white' : 'var(--google-grey-700, #3c4043)' }}; background: {{ request()->routeIs('states.*') ? 'var(--google-blue, #4285f4)' : 'transparent' }}; transition: all 0.2s ease;"
                       onmouseover="if (!this.style.background.includes('#4285f4')) { this.style.background = 'var(--google-blue-50, #e8f0fe)'; this.style.color = 'var(--google-blue-700, #1967d2)'; }"
                       onmouseout="if (!this.style.background.includes('#4285f4')) { this.style.background = 'transparent'; this.style.color = 'var(--google-grey-700, #3c4043)'; }">
                        <span class="material-symbols-outlined" style="font-size: 16px; margin-right: 0.25rem;">location_on</span>
                        US States
                    </a>
                </nav>

                <!-- User Menu -->
                <div style="position: relative; margin-left: 1rem;">
                    @if(Auth::user()->avatar_url && Auth::user()->name && Auth::user()->email)
                    <button onclick="toggleUserMenu()" style="display: flex; align-items: center; gap: 0.5rem; padding: 0.5rem; border-radius: 0.5rem; background: none; border: none; cursor: pointer; transition: background-color 0.2s ease;"
                            onmouseover="this.style.background = 'var(--google-grey-100, #f1f3f4)'"
                            onmouseout="this.style.background = 'transparent'"
                            aria-expanded="false" aria-haspopup="true">
                        <img src="{{ Auth::user()->avatar_url }}" alt="{{ Auth::user()->name }}" style="width: 2rem; height: 2rem; border-radius: 50%; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
                        <div style="display: none; text-align: left;" class="user-info">
                            <div style="font-family: 'Google Sans', sans-serif; font-size: 0.875rem; font-weight: 500; color: var(--google-grey-900, #202124);">{{ Auth::user()->name }}</div>
                            <div style="font-family: 'Google Sans', sans-serif; font-size: 0.75rem; color: var(--google-grey-600, #5f6368);">{{ Auth::user()->email }}</div>
                        </div>
                        <span class="material-symbols-outlined" style="color: var(--google-grey-600, #5f6368);">expand_more</span>
                    </button>

                    <!-- User Dropdown Menu -->
                    <div id="user-menu" style="position: absolute; top: 100%; right: 0; background: white; border-radius: 0.75rem; box-shadow: 0 2px 6px 2px rgba(60, 64, 67, 0.15); border: 1px solid var(--google-grey-200, #e8eaed); padding: 0.5rem 0; margin-top: 0.5rem; min-width: 200px; display: none; z-index: 1000;" role="menu">
                        <a href="{{ route('profile.show') }}" style="display: flex; align-items: center; padding: 0.75rem 1rem; text-decoration: none; color: var(--on-surface, #202124); font-family: 'Google Sans', sans-serif; font-size: 0.875rem; transition: background-color 0.2s ease;"
                           onmouseover="this.style.background = 'var(--google-grey-50, #f8f9fa)'"
                           onmouseout="this.style.background = 'transparent'"
                           role="menuitem">
                            <span class="material-symbols-outlined" style="font-size: 16px; margin-right: 0.5rem;">person</span>
                            Profile
                        </a>
                        <div style="height: 1px; background: var(--google-grey-200, #e8eaed); margin: 0.5rem 0;"></div>
                        <form action="{{ route('logout') }}" method="POST">
                            @csrf
                            <button type="submit" style="display: flex; align-items: center; width: 100%; padding: 0.75rem 1rem; background: none; border: none; text-align: left; color: var(--google-red, #ea4335); font-family: 'Google Sans', sans-serif; font-size: 0.875rem; cursor: pointer; transition: background-color 0.2s ease;"
                                    onmouseover="this.style.background = 'var(--google-red-50, #fce8e6)'"
                                    onmouseout="this.style.background = 'transparent'"
                                    role="menuitem">
                                <span class="material-symbols-outlined" style="font-size: 16px; margin-right: 0.5rem;">logout</span>
                                Sign out
                            </button>
                        </form>
                    </div>
                    @else
                    <div style="background: var(--google-red-50, #fce8e6); color: var(--google-red, #ea4335); padding: 0.5rem 1rem; border-radius: 0.5rem;">
                        <span style="font-family: 'Google Sans', sans-serif; font-size: 0.75rem; font-weight: 500;">User data incomplete</span>
                    </div>
                    @endif
                </div>
            </div>
        </header>

        <!-- Main Content -->
        <main style="padding: 2rem 1.5rem; max-width: 1200px; margin: 0 auto;" role="main" id="main-content" tabindex="-1">
            <!-- Page Header -->
            @hasSection('page-header')
            <div class="gd-flex gd-items-center gd-justify-between gd-mb-xl">
                <div>
                    <h1 class="gd-headline-medium">@yield('page-title', 'Dashboard')</h1>
                    @hasSection('page-subtitle')
                    <p class="gd-body-large gd-text-secondary gd-mt-xs">@yield('page-subtitle')</p>
                    @endif
                </div>
                @hasSection('page-actions')
                <div class="gd-flex gd-items-center gd-gap-sm">
                    @yield('page-actions')
                </div>
                @endif
            </div>
            @endif

            <!-- Flash Messages -->
            @if(session('success'))
            <div class="gd-alert gd-alert-success" role="alert">
                <span class="material-symbols-outlined">check_circle</span>
                <div>
                    <div class="gd-label-medium">Success</div>
                    <div class="gd-body-small">{{ session('success') }}</div>
                </div>
                <button class="gd-button-text gd-p-xs ml-auto" onclick="this.parentElement.remove()" aria-label="Dismiss">
                    <span class="material-symbols-outlined">close</span>
                </button>
            </div>
            @endif

            @if(session('error'))
            <div class="gd-alert gd-alert-error" role="alert">
                <span class="material-symbols-outlined">error</span>
                <div>
                    <div class="gd-label-medium">Error</div>
                    <div class="gd-body-small">{{ session('error') }}</div>
                </div>
                <button class="gd-button-text gd-p-xs ml-auto" onclick="this.parentElement.remove()" aria-label="Dismiss">
                    <span class="material-symbols-outlined">close</span>
                </button>
            </div>
            @endif

            @if(session('warning'))
            <div class="gd-alert gd-alert-warning" role="alert">
                <span class="material-symbols-outlined">warning</span>
                <div>
                    <div class="gd-label-medium">Warning</div>
                    <div class="gd-body-small">{{ session('warning') }}</div>
                </div>
                <button class="gd-button-text gd-p-xs ml-auto" onclick="this.parentElement.remove()" aria-label="Dismiss">
                    <span class="material-symbols-outlined">close</span>
                </button>
            </div>
            @endif

            <!-- Page Content -->
            @yield('content')
        </main>
        @endauth
    </div>

    <!-- Mobile Menu Overlay -->
    <div id="mobile-overlay" style="position: fixed; inset: 0; background: rgba(0,0,0,0.5); display: none; z-index: 999;" onclick="closeMobileMenu()"></div>

    <!-- JavaScript for Interactions -->
    <script>
        // Mobile menu toggle
        function toggleMobileMenu() {
            const nav = document.getElementById('main-nav');
            const overlay = document.getElementById('mobile-overlay');
            const toggle = document.getElementById('mobile-menu-toggle');

            if (nav.style.display === 'flex') {
                closeMobileMenu();
            } else {
                nav.style.display = 'flex';
                nav.style.position = 'absolute';
                nav.style.top = '100%';
                nav.style.left = '0';
                nav.style.right = '0';
                nav.style.background = 'white';
                nav.style.flexDirection = 'column';
                nav.style.padding = '1rem';
                nav.style.boxShadow = '0 2px 6px 2px rgba(60, 64, 67, 0.15)';
                nav.style.borderRadius = '0 0 0.75rem 0.75rem';
                overlay.style.display = 'block';
                document.body.style.overflow = 'hidden';
            }
        }

        function closeMobileMenu() {
            const nav = document.getElementById('main-nav');
            const overlay = document.getElementById('mobile-overlay');

            nav.style.display = '';
            nav.style.position = '';
            nav.style.top = '';
            nav.style.left = '';
            nav.style.right = '';
            nav.style.background = '';
            nav.style.flexDirection = '';
            nav.style.padding = '';
            nav.style.boxShadow = '';
            nav.style.borderRadius = '';
            overlay.style.display = 'none';
            document.body.style.overflow = '';
        }

        // User menu toggle
        function toggleUserMenu() {
            const menu = document.getElementById('user-menu');
            const transactionDropdown = document.getElementById('transaction-dropdown');

            // Close transaction dropdown
            if (transactionDropdown) {
                transactionDropdown.style.display = 'none';
            }

            if (menu.style.display === 'block') {
                menu.style.display = 'none';
            } else {
                menu.style.display = 'block';
            }
        }

        // Transaction dropdown toggle
        function toggleTransactionDropdown() {
            const dropdown = document.getElementById('transaction-dropdown');
            const userMenu = document.getElementById('user-menu');

            // Close user menu
            if (userMenu) {
                userMenu.style.display = 'none';
            }

            if (dropdown.style.display === 'block') {
                dropdown.style.display = 'none';
            } else {
                dropdown.style.display = 'block';
            }
        }

        // Close dropdowns when clicking outside
        document.addEventListener('click', function(event) {
            const userMenu = document.getElementById('user-menu');
            const transactionDropdown = document.getElementById('transaction-dropdown');

            // Close user menu if clicking outside
            const userButton = event.target.closest('button[onclick="toggleUserMenu()"], #user-menu');
            if (!userButton && userMenu) {
                userMenu.style.display = 'none';
            }

            // Close transaction dropdown if clicking outside
            const transactionButton = event.target.closest('button[onclick="toggleTransactionDropdown()"], #transaction-dropdown');
            if (!transactionButton && transactionDropdown) {
                transactionDropdown.style.display = 'none';
            }
        });

        // Mobile menu toggle event listener
        document.getElementById('mobile-menu-toggle')?.addEventListener('click', toggleMobileMenu);

        // Keyboard navigation support
        document.addEventListener('keydown', function(event) {
            // Close menus on Escape
            if (event.key === 'Escape') {
                closeMobileMenu();
                const userMenu = document.getElementById('user-menu');
                const transactionDropdown = document.getElementById('transaction-dropdown');
                if (userMenu) userMenu.style.display = 'none';
                if (transactionDropdown) transactionDropdown.style.display = 'none';
            }
        });

        // Responsive behavior
        function updateResponsiveLayout() {
            const nav = document.getElementById('main-nav');
            const toggle = document.getElementById('mobile-menu-toggle');
            const userInfo = document.querySelector('.user-info');

            if (window.innerWidth <= 768) {
                toggle.style.display = 'block';
                nav.style.display = 'none';
                if (userInfo) userInfo.style.display = 'none';
            } else {
                toggle.style.display = 'none';
                nav.style.display = 'flex';
                nav.style.position = '';
                nav.style.top = '';
                nav.style.left = '';
                nav.style.right = '';
                nav.style.background = '';
                nav.style.flexDirection = '';
                nav.style.padding = '';
                nav.style.boxShadow = '';
                nav.style.borderRadius = '';
                if (userInfo) userInfo.style.display = 'block';
            }
        }

        window.addEventListener('resize', updateResponsiveLayout);
        window.addEventListener('load', updateResponsiveLayout);

        // Loading state management
        function showLoading(element) {
            element.classList.add('gd-loading');
            element.disabled = true;
        }

        function hideLoading(element) {
            element.classList.remove('gd-loading');
            element.disabled = false;
        }

        // Form submission with loading states
        document.querySelectorAll('form').forEach(form => {
            form.addEventListener('submit', function() {
                const submitButton = form.querySelector('button[type="submit"]');
                if (submitButton) {
                    showLoading(submitButton);
                }
            });
        });

        // Toast notification system
        function showToast(message, type = 'info', duration = 5000) {
            const toast = document.createElement('div');
            toast.className = `gd-toast gd-alert-${type}`;

            const icon = {
                success: 'check_circle',
                error: 'error',
                warning: 'warning',
                info: 'info'
            }[type] || 'info';

            toast.innerHTML = `
                <span class="material-symbols-outlined">${icon}</span>
                <div class="gd-flex-1">
                    <div class="gd-body-medium">${message}</div>
                </div>
                <button onclick="this.parentElement.remove()" class="gd-button-text gd-p-xs">
                    <span class="material-symbols-outlined">close</span>
                </button>
            `;

            document.body.appendChild(toast);

            setTimeout(() => {
                if (toast.parentElement) {
                    toast.remove();
                }
            }, duration);
        }

        // Progressive enhancement - add interaction feedback
        document.querySelectorAll('.gd-button, .gd-nav-link, .gd-stat-card').forEach(el => {
            el.addEventListener('click', function() {
                // Add ripple effect
                const ripple = document.createElement('span');
                ripple.style.cssText = `
                    position: absolute;
                    border-radius: 50%;
                    background: rgba(255,255,255,0.3);
                    pointer-events: none;
                    animation: gd-ripple 0.6s linear;
                    z-index: 1;
                `;

                const rect = this.getBoundingClientRect();
                const size = Math.max(rect.width, rect.height);
                ripple.style.width = ripple.style.height = size + 'px';
                ripple.style.left = (event.clientX - rect.left - size / 2) + 'px';
                ripple.style.top = (event.clientY - rect.top - size / 2) + 'px';

                this.style.position = 'relative';
                this.appendChild(ripple);

                setTimeout(() => {
                    if (ripple.parentElement) {
                        ripple.remove();
                    }
                }, 600);
            });
        });

        // Add ripple animation to CSS
        const style = document.createElement('style');
        style.textContent = `
            @keyframes gd-ripple {
                to {
                    transform: scale(2);
                    opacity: 0;
                }
            }
        `;
        document.head.appendChild(style);

        // Smooth scrolling for anchor links
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    target.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                }
            });
        });
    </script>

    <!-- Additional scripts -->
    @stack('scripts')
</body>
</html>