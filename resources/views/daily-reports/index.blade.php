@extends('layouts.tabler')
@section('title', 'Daily Reports')
@section('content')

<div class="page-header">
    <div class="page-title">
        <h1>Daily Reports</h1>
    </div>
    <div class="page-actions">
        <a href="{{ route('daily-reports.create') }}" class="btn btn-primary">
            <i class="ti ti-plus"></i> Create New Report
        </a>
    </div>
</div>

<div class="card">
    <div class="card-body">
        @if($reports->count() > 0)
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Restaurant</th>
                            <th>Gross Sales</th>
                            <th>Net Sales</th>
                            <th>Total Paid Outs</th>
                            <th>Cash to Account For</th>
                            <th>Created By</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($reports as $report)
                            <tr>
                                <td>{{ $report->report_date->format('M d, Y') }}</td>
                                <td>{{ $report->restaurant_name }}</td>
                                <td>${{ number_format($report->gross_sales, 2) }}</td>
                                <td>${{ number_format($report->net_sales, 2) }}</td>
                                <td>${{ number_format($report->total_paid_outs, 2) }}</td>
                                <td>${{ number_format($report->cash_to_account_for, 2) }}</td>
                                <td>{{ $report->creator->name }}</td>
                                <td>
                                    <div class="btn-group" role="group">
                                        <a href="{{ route('daily-reports.show', $report) }}" class="btn btn-sm btn-outline-primary">View</a>
                                        <a href="{{ route('daily-reports.edit', $report) }}" class="btn btn-sm btn-outline-warning">Edit</a>
                                        <form method="POST" action="{{ route('daily-reports.destroy', $report) }}" style="display: inline-block;">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-outline-danger" onclick="return confirm('Are you sure you want to delete this report?')">Delete</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            
            {{ $reports->links() }}
        @else
            <div class="text-center py-5">
                <h4>No daily reports found</h4>
                <p>Create your first daily report to get started.</p>
                <a href="{{ route('daily-reports.create') }}" class="btn btn-primary">Create New Report</a>
            </div>
        @endif
    </div>
</div>

@endsection