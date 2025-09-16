@extends('layouts.tabler')

@section('title', 'Dashboard')

@section('content')
<div class="container-xl mt-4">
    <!-- Welcome Section -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card" style="background: linear-gradient(135deg, var(--google-blue, #4285f4) 0%, var(--google-blue-600, #1a73e8) 100%); color: white; border: none;">
                <div class="card-body py-5">
                    <div class="row align-items-center">
                        <div class="col-md-8">
                            <h1 class="mb-2" style="font-family: 'Google Sans', sans-serif; font-size: 2rem; font-weight: 400;">Welcome back, {{ Auth::user()->name }}!</h1>
                            <p class="mb-0 opacity-75" style="font-family: 'Google Sans', sans-serif; font-size: 1.125rem;">Ready to manage your restaurant operations today?</p>
                        </div>
                        <div class="col-md-4 text-center">
                            <div style="font-size: 4rem; opacity: 0.3;">
                                <svg xmlns="http://www.w3.org/2000/svg" width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M3 9l9-7 9 7v11a2 2 0 01-2 2H5a2 2 0 01-2-2z"/>
                                    <polyline points="9,22 9,12 15,12 15,22"/>
                                </svg>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @if (session('status'))
    <div class="alert alert-success d-flex align-items-center mb-4" role="alert" style="border-radius: 0.75rem; border-left: 4px solid var(--google-green, #34a853);">
        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="var(--google-green, #34a853)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="me-2">
            <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/>
            <polyline points="22,4 12,14.01 9,11.01"/>
        </svg>
        {{ session('status') }}
        <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    @endif

    <!-- Quick Actions -->
    <div class="row g-4">
        <!-- Stores Card -->
        <div class="col-md-4">
            <div class="card h-100" style="border: 1px solid var(--google-grey-200, #e8eaed); transition: all 0.2s ease;" onmouseover="this.style.boxShadow='var(--elevation-3, 0 2px 6px 2px rgba(60, 64, 67, 0.15))'; this.style.transform='translateY(-2px)'" onmouseout="this.style.boxShadow=''; this.style.transform=''">
                <div class="card-body text-center py-4">
                    <div class="mb-3">
                        <div class="d-inline-flex p-3 rounded-circle" style="background: var(--google-blue-50, #e8f0fe); color: var(--google-blue, #4285f4);">
                            <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M2 3h6a4 4 0 014 4v14a3 3 0 00-3-3H2z"/>
                                <path d="M22 3h-6a4 4 0 00-4 4v14a3 3 0 013-3h7z"/>
                            </svg>
                        </div>
                    </div>
                    <h3 style="font-family: 'Google Sans', sans-serif; font-size: 1.25rem; font-weight: 500; color: var(--google-grey-900, #202124); margin-bottom: 0.75rem;">Manage Stores</h3>
                    <p style="font-family: 'Google Sans', sans-serif; color: var(--google-grey-600, #5f6368); margin-bottom: 1.5rem; font-size: 0.875rem;">View and manage your restaurant locations, update store information, and track performance.</p>
                    <a href="{{ route('stores.index') }}" class="btn btn-primary btn-sm px-4">
                        View Stores
                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="ms-1">
                            <path d="M5 12h14M12 5l7 7-7 7"/>
                        </svg>
                    </a>
                </div>
            </div>
        </div>

        <!-- Daily Reports Card -->
        <div class="col-md-4">
            <div class="card h-100" style="border: 1px solid var(--google-grey-200, #e8eaed); transition: all 0.2s ease;" onmouseover="this.style.boxShadow='var(--elevation-3, 0 2px 6px 2px rgba(60, 64, 67, 0.15))'; this.style.transform='translateY(-2px)'" onmouseout="this.style.boxShadow=''; this.style.transform=''">
                <div class="card-body text-center py-4">
                    <div class="mb-3">
                        <div class="d-inline-flex p-3 rounded-circle" style="background: var(--google-green-50, #e6f4ea); color: var(--google-green, #34a853);">
                            <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/>
                                <polyline points="14,2 14,8 20,8"/>
                                <line x1="16" y1="13" x2="8" y2="13"/>
                                <line x1="16" y1="17" x2="8" y2="17"/>
                                <polyline points="10,9 9,9 8,9"/>
                            </svg>
                        </div>
                    </div>
                    <h3 style="font-family: 'Google Sans', sans-serif; font-size: 1.25rem; font-weight: 500; color: var(--google-grey-900, #202124); margin-bottom: 0.75rem;">Daily Reports</h3>
                    <p style="font-family: 'Google Sans', sans-serif; color: var(--google-grey-600, #5f6368); margin-bottom: 1.5rem; font-size: 0.875rem;">Create and view daily operational reports, track sales, and monitor your business performance.</p>
                    <a href="{{ route('daily-reports.index') }}" class="btn btn-success btn-sm px-4">
                        View Reports
                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="ms-1">
                            <path d="M5 12h14M12 5l7 7-7 7"/>
                        </svg>
                    </a>
                </div>
            </div>
        </div>

        <!-- Profile Settings Card -->
        <div class="col-md-4">
            <div class="card h-100" style="border: 1px solid var(--google-grey-200, #e8eaed); transition: all 0.2s ease;" onmouseover="this.style.boxShadow='var(--elevation-3, 0 2px 6px 2px rgba(60, 64, 67, 0.15))'; this.style.transform='translateY(-2px)'" onmouseout="this.style.boxShadow=''; this.style.transform=''">
                <div class="card-body text-center py-4">
                    <div class="mb-3">
                        <div class="d-inline-flex p-3 rounded-circle" style="background: #fff3e0; color: #f57c00;">
                            <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <circle cx="12" cy="7" r="4"/>
                                <path d="M6 21v-2a4 4 0 014-4h4a4 4 0 014 4v2"/>
                            </svg>
                        </div>
                    </div>
                    <h3 style="font-family: 'Google Sans', sans-serif; font-size: 1.25rem; font-weight: 500; color: var(--google-grey-900, #202124); margin-bottom: 0.75rem;">Profile Settings</h3>
                    <p style="font-family: 'Google Sans', sans-serif; color: var(--google-grey-600, #5f6368); margin-bottom: 1.5rem; font-size: 0.875rem;">Update your account information, change password, and manage your profile preferences.</p>
                    <a href="{{ route('profile.show') }}" class="btn btn-warning btn-sm px-4">
                        View Profile
                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="ms-1">
                            <path d="M5 12h14M12 5l7 7-7 7"/>
                        </svg>
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Stats Row -->
    <div class="row mt-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header border-0">
                    <h3 class="card-title" style="font-family: 'Google Sans', sans-serif; font-size: 1.125rem; font-weight: 500;">Quick Stats</h3>
                </div>
                <div class="card-body">
                    <div class="row text-center">
                        <div class="col-md-3">
                            <div class="mb-2">
                                <span style="font-size: 2rem; color: var(--google-blue, #4285f4); font-weight: 300;">{{ Auth::user()->stores()->count() }}</span>
                            </div>
                            <div style="font-family: 'Google Sans', sans-serif; color: var(--google-grey-600, #5f6368); font-size: 0.875rem;">Total Stores</div>
                        </div>
                        <div class="col-md-3">
                            <div class="mb-2">
                                <span style="font-size: 2rem; color: var(--google-green, #34a853); font-weight: 300;">{{ Auth::user()->dailyReports()->count() }}</span>
                            </div>
                            <div style="font-family: 'Google Sans', sans-serif; color: var(--google-grey-600, #5f6368); font-size: 0.875rem;">Total Reports</div>
                        </div>
                        <div class="col-md-3">
                            <div class="mb-2">
                                <span style="font-size: 2rem; color: #f57c00; font-weight: 300;">{{ Auth::user()->created_at->diffInDays() }}</span>
                            </div>
                            <div style="font-family: 'Google Sans', sans-serif; color: var(--google-grey-600, #5f6368); font-size: 0.875rem;">Days Active</div>
                        </div>
                        <div class="col-md-3">
                            <div class="mb-2">
                                <span style="font-size: 2rem; color: var(--google-red, #ea4335); font-weight: 300;">{{ Auth::user()->role->name ?? 'User' }}</span>
                            </div>
                            <div style="font-family: 'Google Sans', sans-serif; color: var(--google-grey-600, #5f6368); font-size: 0.875rem;">Account Type</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
