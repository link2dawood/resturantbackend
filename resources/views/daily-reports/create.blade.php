@extends('layouts.tabler')
@section('title', 'Create Daily Report')
@section('content')

<div class="container">
    <div class="page-header">
        <div class="page-title">
            <h1>📊 Create New Daily Report</h1>
            <p class="text-muted">Step 3 of 3: Enter report details</p>
        </div>
        <div class="page-actions">
            <a href="{{ route('daily-reports.index') }}" class="google-btn google-btn-outlined">← Back to Reports</a>
        </div>
    </div>

    <!-- Progress bar -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="progress" style="height: 8px;">
                <div class="progress-bar bg-success" role="progressbar" style="width: 100%"></div>
            </div>
        </div>
    </div>

    <!-- Selected store and date info -->
    @if(isset($store) && isset($reportDate))
        <div class="row mb-4">
            <div class="col-12">
                <div class="alert alert-success">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <i class="fas fa-check-circle me-2"></i>
                            <strong>Store:</strong> {{ $store->store_info }} |
                            <strong>Date:</strong> {{ \Carbon\Carbon::parse($reportDate)->format('l, M j, Y') }}
                        </div>
                        <div>
                            <a href="{{ route('daily-reports.select-date', ['store_id' => $store->id]) }}" class="google-btn google-btn-outlined google-btn-small">
                                <span class="material-symbols-outlined" style="font-size: 16px;">edit</span>Change Date
                            </a>
                            <a href="{{ route('daily-reports.create') }}" class="google-btn google-btn-outlined google-btn-small">
                                <span class="material-symbols-outlined" style="font-size: 16px;">store</span>Change Store
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>

@include('daily-reports.form')

@endsection