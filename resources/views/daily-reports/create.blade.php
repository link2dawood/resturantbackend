@extends('layouts.tabler')
@section('title', 'Create Daily Report')
@section('content')

<div class="container">
    <div class="page-header">
        <div class="page-title">
            <h1>Create New Daily Report</h1>
        </div>
        <div class="page-actions">
            <a href="{{ route('daily-reports.quick-entry') }}" class="btn btn-warning">⚡ Quick Entry Mode</a>
            <a href="{{ route('daily-reports.index') }}" class="btn btn-secondary">← Back to Reports</a>
        </div>
    </div>
</div>

@include('daily-reports.form')

@endsection