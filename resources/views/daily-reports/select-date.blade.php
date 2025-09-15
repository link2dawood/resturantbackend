@extends('layouts.tabler')
@section('title', 'Select Date - Daily Report')
@section('content')

<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="text-center mb-4">
                <h1 class="h3">📊 Create Daily Report</h1>
                <p class="text-muted">Step 2 of 3: Select a date for <strong>{{ $store->store_info }}</strong></p>

                <!-- Progress bar -->
                <div class="progress mb-4" style="height: 8px;">
                    <div class="progress-bar bg-primary" role="progressbar" style="width: 66%"></div>
                </div>
            </div>

            @if(session('success'))
                <div class="alert alert-success">{{ session('success') }}</div>
            @endif

            @if(session('error'))
                <div class="alert alert-danger">{{ session('error') }}</div>
            @endif

            @if(session('warning'))
                <div class="alert alert-warning">{{ session('warning') }}</div>
            @endif

            <div class="card">
                <div class="card-header">
                    <h4 class="card-title mb-0">
                        <i class="fas fa-calendar me-2"></i>Select Report Date
                    </h4>
                </div>
                <div class="card-body">
                    <div class="row mb-4">
                        <div class="col-md-12">
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle me-2"></i>
                                <strong>Selected Store:</strong> {{ $store->store_info }}<br>
                                <small class="text-muted">{{ $store->address }}, {{ $store->city }}, {{ $store->state }}</small>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <h5 class="mb-3">Quick Date Selection</h5>
                            <div class="list-group mb-4">
                                @php
                                    $today = \Carbon\Carbon::now();
                                    $yesterday = $today->copy()->subDay();
                                    $dayBeforeYesterday = $today->copy()->subDays(2);

                                    $quickDates = [
                                        ['date' => $today->format('Y-m-d'), 'label' => 'Today (' . $today->format('M j, Y') . ')', 'class' => 'success'],
                                        ['date' => $yesterday->format('Y-m-d'), 'label' => 'Yesterday (' . $yesterday->format('M j, Y') . ')', 'class' => 'primary'],
                                        ['date' => $dayBeforeYesterday->format('Y-m-d'), 'label' => $dayBeforeYesterday->format('M j, Y'), 'class' => 'secondary']
                                    ];
                                @endphp

                                @foreach($quickDates as $quickDate)
                                    @if(!in_array($quickDate['date'], $existingDates))
                                        <button type="button" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center quick-date-btn"
                                                data-date="{{ $quickDate['date'] }}">
                                            <div>
                                                <i class="fas fa-calendar-day me-2"></i>
                                                {{ $quickDate['label'] }}
                                            </div>
                                            <span class="badge bg-{{ $quickDate['class'] }}">Select</span>
                                        </button>
                                    @else
                                        <div class="list-group-item d-flex justify-content-between align-items-center text-muted">
                                            <div>
                                                <i class="fas fa-calendar-times me-2"></i>
                                                {{ $quickDate['label'] }}
                                            </div>
                                            <span class="badge bg-danger">Already Exists</span>
                                        </div>
                                    @endif
                                @endforeach
                            </div>
                        </div>

                        <div class="col-md-6">
                            <h5 class="mb-3">Custom Date</h5>
                            <div class="mb-3">
                                <label for="customDate" class="form-label">Select any date:</label>
                                <input type="date" id="customDate" class="form-control" max="{{ date('Y-m-d') }}">
                                <small class="form-text text-muted">Reports can only be created for current and past dates.</small>
                            </div>
                            <button type="button" id="selectCustomDate" class="btn btn-outline-primary w-100">
                                <i class="fas fa-calendar-check me-2"></i>Use Custom Date
                            </button>

                            @if(count($existingDates) > 0)
                                <div class="mt-4">
                                    <h6 class="text-muted">Existing Reports:</h6>
                                    <div class="existing-dates">
                                        @foreach(array_slice($existingDates, 0, 5) as $existingDate)
                                            <span class="badge bg-light text-dark me-1 mb-1">
                                                <i class="fas fa-file-alt me-1"></i>
                                                {{ \Carbon\Carbon::parse($existingDate)->format('M j, Y') }}
                                            </span>
                                        @endforeach
                                        @if(count($existingDates) > 5)
                                            <small class="text-muted d-block mt-2">
                                                ... and {{ count($existingDates) - 5 }} more
                                            </small>
                                        @endif
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>

                    <!-- Hidden form for submission -->
                    <form id="dateSelectionForm" method="GET" action="{{ route('daily-reports.create-form') }}">
                        <input type="hidden" name="store_id" value="{{ $store->id }}">
                        <input type="hidden" name="report_date" id="selectedDate">
                    </form>
                </div>
                <div class="card-footer d-flex justify-content-between">
                    <a href="{{ route('daily-reports.create') }}" class="btn btn-outline-secondary">
                        <i class="fas fa-arrow-left me-1"></i>Back to Store Selection
                    </a>
                    <a href="{{ route('daily-reports.index') }}" class="btn btn-outline-secondary">
                        <i class="fas fa-times me-1"></i>Cancel
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const existingDates = @json($existingDates);

    // Quick date selection
    document.querySelectorAll('.quick-date-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const selectedDate = this.getAttribute('data-date');
            selectDate(selectedDate);
        });
    });

    // Custom date selection
    document.getElementById('selectCustomDate').addEventListener('click', function() {
        const customDate = document.getElementById('customDate').value;
        if (!customDate) {
            alert('Please select a date first.');
            return;
        }

        // Check if date already exists
        if (existingDates.includes(customDate)) {
            alert('A report for this date already exists. Please select a different date.');
            return;
        }

        // Check if date is not in the future
        const selectedDate = new Date(customDate);
        const today = new Date();
        today.setHours(23, 59, 59, 999); // End of today

        if (selectedDate > today) {
            alert('Reports can only be created for current and past dates.');
            return;
        }

        selectDate(customDate);
    });

    // Update custom date validation on change
    document.getElementById('customDate').addEventListener('change', function() {
        const customDate = this.value;
        const selectBtn = document.getElementById('selectCustomDate');

        if (existingDates.includes(customDate)) {
            selectBtn.textContent = 'Date Already Exists';
            selectBtn.className = 'btn btn-danger w-100';
            selectBtn.disabled = true;
        } else {
            selectBtn.innerHTML = '<i class="fas fa-calendar-check me-2"></i>Use Custom Date';
            selectBtn.className = 'btn btn-outline-primary w-100';
            selectBtn.disabled = false;
        }
    });
});

function selectDate(date) {
    document.getElementById('selectedDate').value = date;
    document.getElementById('dateSelectionForm').submit();
}
</script>

@endsection