@extends('layouts.tabler')

@section('title', 'Reports')

@section('content')
<div class="container-xl mt-4">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="mb-0" style="font-family: 'Google Sans', sans-serif; font-size: 1.75rem; font-weight: 400; color: var(--on-surface, #202124);">Reports</h1>
            <p class="text-muted mb-0" style="font-family: 'Google Sans', sans-serif; margin-top: 0.25rem;">Advanced reporting and analytics</p>
        </div>
    </div>

    <!-- Coming Soon Card -->
    <div class="row justify-content-center">
        <div class="col-md-8 col-lg-6">
            <div class="card text-center" style="border: none; box-shadow: var(--elevation-2, 0 1px 3px 0 rgba(60, 64, 67, 0.08)); border-radius: 1rem;">
                <div class="card-body py-5">
                    <!-- Icon -->
                    <div class="mb-4">
                        <div class="d-inline-flex align-items-center justify-content-center" style="width: 80px; height: 80px; background: linear-gradient(135deg, var(--google-blue-100, #d2e3fc), var(--google-blue-50, #e8f0fe)); border-radius: 50%; margin-bottom: 1rem;">
                            <svg xmlns="http://www.w3.org/2000/svg" width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="var(--google-blue, #4285f4)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/>
                                <polyline points="14,2 14,8 20,8"/>
                                <line x1="16" y1="13" x2="8" y2="13"/>
                                <line x1="16" y1="17" x2="8" y2="17"/>
                                <polyline points="10,9 9,9 8,9"/>
                            </svg>
                        </div>
                    </div>

                    <!-- Title -->
                    <h2 class="mb-3" style="font-family: 'Google Sans', sans-serif; font-size: 1.5rem; font-weight: 400; color: var(--on-surface, #202124);">
                        Reports Coming Soon
                    </h2>

                    <!-- Description -->
                    <p class="text-muted mb-4" style="font-family: 'Google Sans', sans-serif; font-size: 1rem; line-height: 1.5; max-width: 400px; margin: 0 auto 2rem;">
                        We're working hard to bring you comprehensive reporting features including sales analytics, performance metrics, and custom report generation.
                    </p>

                    <!-- Features List -->
                    <div class="row text-start mb-4">
                        <div class="col-12">
                            <div class="list-group list-group-flush" style="background: transparent;">
                                <div class="list-group-item d-flex align-items-center border-0 px-0 py-2" style="background: transparent;">
                                    <div class="me-3">
                                        <div class="d-inline-flex align-items-center justify-content-center" style="width: 24px; height: 24px; background: var(--google-green-100, #c8e6c9); border-radius: 50%;">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="var(--google-green, #34a853)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                <polyline points="20,6 9,17 4,12"/>
                                            </svg>
                                        </div>
                                    </div>
                                    <div>
                                        <div style="font-family: 'Google Sans', sans-serif; font-weight: 500; color: var(--google-grey-900, #202124); font-size: 0.875rem;">Sales Performance Analytics</div>
                                    </div>
                                </div>
                                <div class="list-group-item d-flex align-items-center border-0 px-0 py-2" style="background: transparent;">
                                    <div class="me-3">
                                        <div class="d-inline-flex align-items-center justify-content-center" style="width: 24px; height: 24px; background: var(--google-green-100, #c8e6c9); border-radius: 50%;">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="var(--google-green, #34a853)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                <polyline points="20,6 9,17 4,12"/>
                                            </svg>
                                        </div>
                                    </div>
                                    <div>
                                        <div style="font-family: 'Google Sans', sans-serif; font-weight: 500; color: var(--google-grey-900, #202124); font-size: 0.875rem;">Revenue Tracking & Forecasting</div>
                                    </div>
                                </div>
                                <div class="list-group-item d-flex align-items-center border-0 px-0 py-2" style="background: transparent;">
                                    <div class="me-3">
                                        <div class="d-inline-flex align-items-center justify-content-center" style="width: 24px; height: 24px; background: var(--google-green-100, #c8e6c9); border-radius: 50%;">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="var(--google-green, #34a853)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                <polyline points="20,6 9,17 4,12"/>
                                            </svg>
                                        </div>
                                    </div>
                                    <div>
                                        <div style="font-family: 'Google Sans', sans-serif; font-weight: 500; color: var(--google-grey-900, #202124); font-size: 0.875rem;">Custom Report Builder</div>
                                    </div>
                                </div>
                                <div class="list-group-item d-flex align-items-center border-0 px-0 py-2" style="background: transparent;">
                                    <div class="me-3">
                                        <div class="d-inline-flex align-items-center justify-content-center" style="width: 24px; height: 24px; background: var(--google-green-100, #c8e6c9); border-radius: 50%;">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="var(--google-green, #34a853)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                <polyline points="20,6 9,17 4,12"/>
                                            </svg>
                                        </div>
                                    </div>
                                    <div>
                                        <div style="font-family: 'Google Sans', sans-serif; font-weight: 500; color: var(--google-grey-900, #202124); font-size: 0.875rem;">Export to PDF & Excel</div>
                                    </div>
                                </div>
                                <div class="list-group-item d-flex align-items-center border-0 px-0 py-2" style="background: transparent;">
                                    <div class="me-3">
                                        <div class="d-inline-flex align-items-center justify-content-center" style="width: 24px; height: 24px; background: var(--google-green-100, #c8e6c9); border-radius: 50%;">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="var(--google-green, #34a853)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                <polyline points="20,6 9,17 4,12"/>
                                            </svg>
                                        </div>
                                    </div>
                                    <div>
                                        <div style="font-family: 'Google Sans', sans-serif; font-weight: 500; color: var(--google-grey-900, #202124); font-size: 0.875rem;">Real-time Data Visualization</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Status Badge -->
                    <div class="mb-4">
                        <span class="badge" style="background: var(--google-blue-50, #e8f0fe); color: var(--google-blue, #4285f4); font-family: 'Google Sans', sans-serif; font-size: 0.813rem; padding: 0.5rem 1rem; border-radius: 1rem;">
                            <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="me-1">
                                <circle cx="12" cy="12" r="3"/>
                                <path d="M12 1v6M12 17v6M4.22 4.22l4.24 4.24M15.54 15.54l4.24 4.24M1 12h6M17 12h6M4.22 19.78l4.24-4.24M15.54 8.46l4.24-4.24"/>
                            </svg>
                            In Development
                        </span>
                    </div>

                    <!-- Action Buttons -->
                    <div class="d-flex gap-2 justify-content-center">
                        <a href="{{ route('daily-reports.index') }}" class="btn btn-outline-primary d-flex align-items-center" style="gap: 0.5rem;">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/>
                                <polyline points="14,2 14,8 20,8"/>
                                <line x1="16" y1="13" x2="8" y2="13"/>
                                <line x1="16" y1="17" x2="8" y2="17"/>
                            </svg>
                            View Daily Reports
                        </a>
                        <a href="{{ route('home') }}" class="btn btn-outline-secondary d-flex align-items-center" style="gap: 0.5rem;">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M3 9l9-7 9 7v11a2 2 0 01-2 2H5a2 2 0 01-2-2z"/>
                                <polyline points="9,22 9,12 15,12 15,22"/>
                            </svg>
                            Back to Dashboard
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Additional Info -->
    <div class="row justify-content-center mt-4">
        <div class="col-md-8 col-lg-6">
            <div class="text-center">
                <p class="text-muted" style="font-family: 'Google Sans', sans-serif; font-size: 0.875rem;">
                    Need immediate reporting? You can still access your daily reports and export data for manual analysis.
                </p>
            </div>
        </div>
    </div>
</div>
@endsection