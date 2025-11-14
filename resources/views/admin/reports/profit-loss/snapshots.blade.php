@extends('layouts.tabler')

@section('title', 'P&L Snapshots')

@section('content')
<div class="container-xl mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="mb-0">P&L Snapshots</h1>
            <p class="text-muted mb-0">Saved historical P&L reports</p>
        </div>
        <a href="{{ route('admin.reports.profit-loss.index') }}" class="btn btn-outline-secondary">Back to P&L</a>
    </div>

    <!-- Filters -->
    <div class="card mb-4">
        <div class="card-body">
            <form action="{{ route('admin.reports.profit-loss.snapshots') }}" method="GET" class="row g-3">
                <div class="col-md-4">
                    <label class="form-label">Store</label>
                    <select class="form-select" name="store_id">
                        <option value="">All Stores</option>
                        @foreach($stores as $store)
                            <option value="{{ $store->id }}" {{ $storeId == $store->id ? 'selected' : '' }}>
                                {{ $store->store_info }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-8">
                    <label class="form-label">&nbsp;</label>
                    <button type="submit" class="btn btn-secondary w-100">Filter</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Snapshots List -->
    <div class="card">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead style="background-color: var(--google-grey-50, #f8f9fa);">
                        <tr>
                            <th>Name</th>
                            <th>Store</th>
                            <th>Date Range</th>
                            <th>Revenue</th>
                            <th>Net Profit</th>
                            <th>Created</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($snapshots as $snapshot)
                        <tr>
                            <td><strong>{{ $snapshot->name }}</strong></td>
                            <td>{{ $snapshot->store->store_info ?? 'All Stores' }}</td>
                            <td>{{ $snapshot->start_date }} to {{ $snapshot->end_date }}</td>
                            <td>${{ number_format($snapshot->pl_data['revenue']['total'] ?? 0, 2) }}</td>
                            <td class="{{ ($snapshot->pl_data['net_profit'] ?? 0) >= 0 ? 'text-success' : 'text-danger' }}">
                                ${{ number_format($snapshot->pl_data['net_profit'] ?? 0, 2) }}
                            </td>
                            <td>{{ $snapshot->created_at->format('M d, Y H:i') }}</td>
                            <td>
                                <button class="btn btn-sm btn-outline-primary" onclick="viewSnapshot({{ $snapshot->id }})">View</button>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="7" class="text-center text-muted py-4">No snapshots found</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if($snapshots->hasPages())
        <div class="card-footer">
            {{ $snapshots->links() }}
        </div>
        @endif
    </div>
</div>
@endsection




