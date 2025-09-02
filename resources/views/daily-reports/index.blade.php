@extends('layouts.tabler')
@section('title', 'Daily Reports')
@section('content')

<div class="container mt-5">
    <div class="text-center mb-4">
        <h1 class="h3">📊 Daily Reports</h1>
        <p class="text-muted">Manage and view your restaurant's daily reports</p>
    </div>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    <div class="card mb-4">
        <div class="card-header">Search & Filter</div>
        <div class="card-body">
            <form method="GET" action="{{ route('daily-reports.index') }}" class="row g-3">
                <div class="col-md-4">
                    <input type="text" name="search" class="form-control" placeholder="Search..." value="{{ request('search') }}">
                </div>
                <div class="col-md-2">
                    <input type="date" name="date_from" class="form-control" value="{{ request('date_from') }}">
                </div>
                <div class="col-md-2">
                    <input type="date" name="date_to" class="form-control" value="{{ request('date_to') }}">
                </div>
                <div class="col-md-2">
                    <select name="store_id" class="form-select">
                        <option value="">All Stores</option>
                        @foreach($stores as $store)
                            <option value="{{ $store->id }}" {{ request('store_id') == $store->id ? 'selected' : '' }}>{{ $store->store_info }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary w-100">Search</button>
                </div>
            </form>
        </div>
    </div>

    @if($reports->count() > 0)
        <div class="card">
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Store</th>
                            <th>Gross Sales</th>
                            <th>Net Sales</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($reports as $report)
                            <tr>
                                <td>{{ $report->report_date->format('M d, Y') }}</td>
                                <td>{{ $report->store->store_info ?? 'N/A' }}</td>
                                <td>${{ number_format($report->gross_sales, 2) }}</td>
                                <td>${{ number_format($report->net_sales, 2) }}</td>
                                <td>
                                    <a href="{{ route('daily-reports.show', $report) }}" class="btn btn-sm btn-info">View</a>
                                    @if($report->status === 'draft')
                                        <a href="{{ route('daily-reports.edit', $report) }}" class="btn btn-sm btn-warning">Edit</a>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="card-footer d-flex justify-content-between">
                <div>Showing {{ $reports->firstItem() }} to {{ $reports->lastItem() }} of {{ $reports->total() }} reports</div>
                <div>{{ $reports->links() }}</div>
            </div>
        </div>
    @else
        <div class="text-center py-5">
            <h4>No Reports Found</h4>
            <p>Start by creating your first daily report.</p>
            {{-- <a href="{{ route('daily-reports.create') }}" class="btn btn-success">Create Report</a> --}}
        </div>
    @endif
</div>

@endsection