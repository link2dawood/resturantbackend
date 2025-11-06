@extends('layouts.tabler')

@section('title', 'Third-Party Platform Costs')

@section('content')
<div class="container-xl mt-4">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="mb-0" style="font-family: 'Google Sans', sans-serif; font-size: 1.75rem; font-weight: 400; color: var(--on-surface, #202124);">Third-Party Platform Costs</h1>
            <p class="text-muted mb-0" style="font-family: 'Google Sans', sans-serif; margin-top: 0.25rem;">Track Grubhub, UberEats, DoorDash fees and ROI</p>
        </div>
        <div class="btn-group">
            <button class="btn btn-primary" onclick="openUploadModal()">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/>
                    <polyline points="17 8 12 3 7 8"/>
                    <line x1="12" y1="3" x2="12" y2="15"/>
                </svg>
                Import Statement
            </button>
            <a href="{{ route('admin.merchant-fees.index') }}" class="btn btn-outline-secondary">
                Merchant Fees
            </a>
        </div>
    </div>

    <!-- Filters -->
    <div class="card mb-4">
        <div class="card-body">
            <form action="{{ route('admin.merchant-fees.third-party') }}" method="GET" class="row g-3">
                <div class="col-md-3">
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
                <div class="col-md-3">
                    <label class="form-label">Platform</label>
                    <select class="form-select" name="platform">
                        <option value="">All Platforms</option>
                        <option value="grubhub" {{ $platform == 'grubhub' ? 'selected' : '' }}>Grubhub</option>
                        <option value="ubereats" {{ $platform == 'ubereats' ? 'selected' : '' }}>UberEats</option>
                        <option value="doordash" {{ $platform == 'doordash' ? 'selected' : '' }}>DoorDash</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Start Date</label>
                    <input type="date" class="form-control" name="start_date" value="{{ $startDate }}">
                </div>
                <div class="col-md-2">
                    <label class="form-label">End Date</label>
                    <input type="date" class="form-control" name="end_date" value="{{ $endDate }}">
                </div>
                <div class="col-md-2">
                    <label class="form-label">&nbsp;</label>
                    <button type="submit" class="btn btn-secondary w-100">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="11" cy="11" r="8"/>
                            <path d="m21 21-4.35-4.35"/>
                        </svg> Apply
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Summary Cards -->
    <div class="row row-cards mb-4">
        <div class="col-sm-6 col-lg-3">
            <div class="card">
                <div class="card-body">
                    <div class="subheader">Total Gross Sales</div>
                    <div class="h1 mb-3 text-success">
                        ${{ number_format($summary['total_gross_sales'], 2) }}
                    </div>
                    <div class="d-flex align-items-center text-muted">All platforms</div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-lg-3">
            <div class="card">
                <div class="card-body">
                    <div class="subheader">Total Fees</div>
                    <div class="h1 mb-3 text-danger">
                        ${{ number_format($summary['total_fees'], 2) }}
                    </div>
                    <div class="d-flex align-items-center text-muted">Platform costs</div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-lg-3">
            <div class="card">
                <div class="card-body">
                    <div class="subheader">Net Deposits</div>
                    <div class="h1 mb-3 text-primary">
                        ${{ number_format($summary['total_net_deposit'], 2) }}
                    </div>
                    <div class="d-flex align-items-center text-muted">After fees</div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-lg-3">
            <div class="card">
                <div class="card-body">
                    <div class="subheader">Average Fee %</div>
                    <div class="h1 mb-3 text-warning">
                        {{ number_format($summary['average_fee_percentage'], 2) }}%
                    </div>
                    <div class="d-flex align-items-center text-muted">of gross sales</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Platform Breakdown -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Platform Breakdown</h3>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead style="background-color: var(--google-grey-50, #f8f9fa);">
                                <tr>
                                    <th>Platform</th>
                                    <th class="text-end">Gross Sales</th>
                                    <th class="text-end">Marketing Fees</th>
                                    <th class="text-end">Delivery Fees</th>
                                    <th class="text-end">Processing Fees</th>
                                    <th class="text-end">Total Fees</th>
                                    <th class="text-end">Net Deposits</th>
                                    <th class="text-end">Fee %</th>
                                    <th>Statements</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($platformBreakdown as $platform)
                                @php
                                    $feePct = $platform->total_gross_sales > 0 ? ($platform->total_fees / $platform->total_gross_sales * 100) : 0;
                                @endphp
                                <tr>
                                    <td><strong>{{ ucfirst($platform->platform) }}</strong></td>
                                    <td class="text-end">${{ number_format($platform->total_gross_sales, 2) }}</td>
                                    <td class="text-end">${{ number_format($platform->total_marketing_fees, 2) }}</td>
                                    <td class="text-end">${{ number_format($platform->total_delivery_fees, 2) }}</td>
                                    <td class="text-end">${{ number_format($platform->total_processing_fees, 2) }}</td>
                                    <td class="text-end"><strong class="text-danger">${{ number_format($platform->total_fees, 2) }}</strong></td>
                                    <td class="text-end"><strong class="text-success">${{ number_format($platform->total_net_deposit, 2) }}</strong></td>
                                    <td class="text-end"><strong>{{ number_format($feePct, 2) }}%</strong></td>
                                    <td>{{ $platform->statement_count }} statements</td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="9" class="text-center text-muted py-4">No data found</td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Import History -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Recent Imports</h3>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead style="background-color: var(--google-grey-50, #f8f9fa);">
                                <tr>
                                    <th>Date</th>
                                    <th>Platform</th>
                                    <th>Store</th>
                                    <th class="text-end">Gross Sales</th>
                                    <th class="text-end">Total Fees</th>
                                    <th class="text-end">Net Deposit</th>
                                    <th class="text-center">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($importHistory as $statement)
                                @php
                                    $totalFees = $statement->marketing_fees + $statement->delivery_fees + $statement->processing_fees;
                                @endphp
                                <tr>
                                    <td>{{ $statement->statement_date->format('M d, Y') }}</td>
                                    <td><span class="badge bg-info">{{ ucfirst($statement->platform) }}</span></td>
                                    <td>{{ $statement->store->store_info ?? 'N/A' }}</td>
                                    <td class="text-end">${{ number_format($statement->gross_sales, 2) }}</td>
                                    <td class="text-end text-danger">${{ number_format($totalFees, 2) }}</td>
                                    <td class="text-end text-success">${{ number_format($statement->net_deposit, 2) }}</td>
                                    <td class="text-center">
                                        <a href="/api/third-party/statements/{{ $statement->id }}" class="btn btn-sm btn-outline-primary">Details</a>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="7" class="text-center text-muted py-4">No imports yet</td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    <!-- Pagination -->
                    <div class="card-footer">
                        {{ $importHistory->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Upload Modal -->
<div class="modal fade" id="uploadModal" tabindex="-1" aria-labelledby="uploadModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="uploadModalLabel">Import Third-Party Statement</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="uploadForm" enctype="multipart/form-data">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="uploadPlatform" class="form-label">Platform <span class="text-danger">*</span></label>
                        <select class="form-select" id="uploadPlatform" name="platform" required>
                            <option value="">Select Platform</option>
                            <option value="grubhub">Grubhub (PDF)</option>
                            <option value="ubereats">UberEats (CSV)</option>
                            <option value="doordash">DoorDash (CSV)</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="uploadStore" class="form-label">Store <span class="text-danger">*</span></label>
                        <select class="form-select" id="uploadStore" name="store_id" required>
                            <option value="">Select Store</option>
                            @foreach($stores as $store)
                                <option value="{{ $store->id }}">{{ $store->store_info }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="uploadFile" class="form-label">Statement File <span class="text-danger">*</span></label>
                        <input type="file" class="form-control" id="uploadFile" name="file" accept=".pdf,.csv" required>
                        <small class="text-muted">Upload PDF or CSV file</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary" id="uploadBtn">
                        <span class="spinner-border spinner-border-sm d-none" role="status" aria-hidden="true"></span>
                        Import
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
function openUploadModal() {
    new bootstrap.Modal(document.getElementById('uploadModal')).show();
}

document.getElementById('uploadForm')?.addEventListener('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData();
    formData.append('platform', document.getElementById('uploadPlatform').value);
    formData.append('store_id', document.getElementById('uploadStore').value);
    formData.append('file', document.getElementById('uploadFile').files[0]);
    
    const spinner = document.getElementById('uploadBtn').querySelector('.spinner-border');
    spinner.classList.remove('d-none');
    document.getElementById('uploadBtn').disabled = true;
    
    fetch('/api/third-party/import', {
        method: 'POST',
        headers: {
            'Accept': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
        },
        credentials: 'same-origin',
        body: formData
    })
    .then(response => response.json())
    .then(result => {
        if (result.message) {
            alert('Success: ' + result.message);
        } else if (result.error) {
            alert('Error: ' + result.error);
        }
        spinner.classList.add('d-none');
        document.getElementById('uploadBtn').disabled = false;
        bootstrap.Modal.getInstance(document.getElementById('uploadModal')).hide();
        setTimeout(() => window.location.reload(), 500);
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error uploading statement');
        spinner.classList.add('d-none');
        document.getElementById('uploadBtn').disabled = false;
    });
});
</script>
@endpush
