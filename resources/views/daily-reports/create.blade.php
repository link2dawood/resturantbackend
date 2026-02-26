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

    @if(isset($store) && isset($reportDate) && isset($prevDate) && isset($nextDate))
    <div class="row mb-3">
        <div class="col-12">
            <div class="d-flex flex-wrap align-items-center gap-2">
                @if(isset($prevReport) && $prevReport)
                    <a href="{{ route('daily-reports.show', $prevReport) }}" class="btn btn-outline-secondary btn-sm">← Previous Day</a>
                @else
                    <a href="{{ route('daily-reports.create-form', ['store_id' => $store->id, 'report_date' => $prevDate]) }}" class="btn btn-outline-secondary btn-sm">← Previous Day</a>
                @endif
                @if(isset($nextReport) && $nextReport)
                    <a href="{{ route('daily-reports.show', $nextReport) }}" class="btn btn-outline-secondary btn-sm">Next Day →</a>
                @else
                    <a href="{{ route('daily-reports.create-form', ['store_id' => $store->id, 'report_date' => $nextDate]) }}" class="btn btn-outline-secondary btn-sm">Next Day →</a>
                @endif
            </div>
        </div>
    </div>
    @endif

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
                        <div class="d-flex flex-wrap align-items-center gap-2">
                            @if(isset($prevReport) && $prevReport)
                                <a href="{{ route('daily-reports.show', $prevReport) }}" class="google-btn google-btn-outlined google-btn-small">← Previous Day</a>
                            @elseif(isset($prevDate))
                                <a href="{{ route('daily-reports.create-form', ['store_id' => $store->id, 'report_date' => $prevDate]) }}" class="google-btn google-btn-outlined google-btn-small">← Previous Day</a>
                            @endif
                            @if(isset($nextReport) && $nextReport)
                                <a href="{{ route('daily-reports.show', $nextReport) }}" class="google-btn google-btn-outlined google-btn-small">Next Day →</a>
                            @elseif(isset($nextDate))
                                <a href="{{ route('daily-reports.create-form', ['store_id' => $store->id, 'report_date' => $nextDate]) }}" class="google-btn google-btn-outlined google-btn-small">Next Day →</a>
                            @endif
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