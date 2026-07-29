@extends('layouts.tabler')

@section('title', 'Chart of Accounts Report')

@section('content')
<div class="container-xl mt-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h1 class="mb-0">Chart of Accounts Report</h1>
            <p class="text-muted mb-0">
                {{ $type ? $type.' accounts' : 'Entire chart' }} — by category and sub-account.
            </p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('coa.index') }}" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left me-1"></i> Back
            </a>
            <button type="button" class="btn btn-outline-secondary" onclick="window.print()">
                <i class="bi bi-printer me-1"></i> Print
            </button>
            <a href="{{ route('coa.export.csv', request()->only('account_type')) }}" class="btn btn-outline-primary">
                <i class="bi bi-filetype-csv me-1"></i> CSV
            </a>
            <a href="{{ route('coa.export.pdf', request()->only('account_type')) }}" class="btn btn-primary">
                <i class="bi bi-filetype-pdf me-1"></i> PDF
            </a>
        </div>
    </div>

    {{-- Filter by type (or show everything). --}}
    <form method="GET" action="{{ route('coa.report') }}" class="d-print-none mb-3 d-flex gap-2 align-items-end" style="max-width:360px;">
        <div class="flex-grow-1">
            <label for="account_type" class="form-label mb-1">Account Type</label>
            <select name="account_type" id="account_type" class="form-select" onchange="this.form.submit()">
                <option value="">Entire chart (all types)</option>
                @foreach($accountTypes as $t)
                    <option value="{{ $t }}" @selected($type === $t)>{{ $t }}</option>
                @endforeach
            </select>
        </div>
    </form>

    <div class="card">
        <div class="card-body coa-report">
            @include('admin.coa._report-body')
        </div>
    </div>
</div>

<style>
    .coa-report-type { font-size: 1.1rem; margin: 1rem 0 .5rem; border-bottom: 2px solid #dee2e6; padding-bottom: .25rem; }
    .coa-report-group:first-child .coa-report-type { margin-top: 0; }
    .coa-report-table { width: 100%; border-collapse: collapse; margin-bottom: 1.25rem; }
    .coa-report-table th { text-align: left; font-size: .75rem; text-transform: uppercase; color: #6c757d; border-bottom: 1px solid #dee2e6; padding: .35rem .5rem; }
    .coa-report-table td { padding: .3rem .5rem; border-bottom: 1px solid #f1f3f5; font-size: .9rem; }
    @media print {
        .d-print-none, .navbar, .btn { display: none !important; }
        .card { border: none; box-shadow: none; }
        .card-body { padding: 0; }
    }
</style>
@endsection
