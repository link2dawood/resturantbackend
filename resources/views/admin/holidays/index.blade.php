@extends('layouts.tabler')

@section('title', 'Holidays')

@section('content')
<div class="container-xl mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="mb-0">Holidays &amp; Special Events</h1>
            <p class="text-muted mb-0">Manage the list of holidays that appear in the daily report's Holiday/Special Event field.</p>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <div class="row g-4">
        {{-- Add form --}}
        <div class="col-lg-4">
            <div class="card">
                <div class="card-header"><h3 class="card-title mb-0">Add a Holiday</h3></div>
                <div class="card-body">
                    <form action="{{ route('admin.holidays.store') }}" method="POST">
                        @csrf
                        <div class="mb-3">
                            <label for="name" class="form-label">Holiday / Event Name <span class="text-danger">*</span></label>
                            <input type="text" id="name" name="name" class="form-control @error('name') is-invalid @enderror"
                                   value="{{ old('name') }}" maxlength="100" placeholder="e.g. Super Bowl Sunday" required autofocus>
                            @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="bi bi-plus-lg me-1"></i> Add Holiday
                        </button>
                    </form>
                </div>
            </div>
        </div>

        {{-- List --}}
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header"><h3 class="card-title mb-0">{{ $holidays->count() }} Holiday{{ $holidays->count() === 1 ? '' : 's' }}</h3></div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Holiday / Event</th>
                                    <th style="width:120px;">Status</th>
                                    <th class="text-end" style="width:120px;">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($holidays as $holiday)
                                    <tr>
                                        <td>
                                            <form action="{{ route('admin.holidays.update', $holiday) }}" method="POST" class="d-flex gap-2 align-items-center holiday-row-form">
                                                @csrf @method('PUT')
                                                <input type="text" name="name" value="{{ $holiday->name }}" class="form-control form-control-sm" maxlength="100" required>
                                                <input type="hidden" name="sort_order" value="{{ $holiday->sort_order }}">
                                                <div class="form-check form-switch mb-0" title="Active">
                                                    <input class="form-check-input holiday-active-toggle" type="checkbox" name="is_active" value="1" @checked($holiday->is_active)>
                                                </div>
                                                <button type="submit" class="btn btn-sm btn-outline-primary" title="Save"><i class="bi bi-check-lg"></i></button>
                                            </form>
                                        </td>
                                        <td>
                                            @if($holiday->is_active)
                                                <span class="badge bg-success">Active</span>
                                            @else
                                                <span class="badge bg-secondary">Hidden</span>
                                            @endif
                                        </td>
                                        <td class="text-end">
                                            <form action="{{ route('admin.holidays.destroy', $holiday) }}" method="POST" onsubmit="return confirm('Remove this holiday?');">
                                                @csrf @method('DELETE')
                                                <button type="submit" class="btn btn-sm btn-outline-danger" title="Delete"><i class="bi bi-trash"></i></button>
                                            </form>
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td colspan="3" class="text-center text-muted py-4">No holidays yet — add one on the left.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <p class="text-muted small mt-2">Editing a name and clicking the check saves it. Turn a holiday off to hide it from the daily report without deleting it.</p>
        </div>
    </div>
</div>
@endsection
