@extends('layouts.tabler')

@section('title', 'Statement — ' . ucfirst($statement->platform))

@section('content')
<div class="container-xl mt-4">
    @php
        $adjustmentLabel = $statement->platform === 'ubereats' ? 'Amendments' : 'Adjustments';
        $netLabel = $statement->platform === 'ubereats' ? 'Net Total' : 'Net Deposit';
        $corePlatformFees = $statement->marketing_fees + $statement->delivery_fees + $statement->processing_fees;
        $adjSigned = (float) ($statement->adjustments ?? 0);
        // Grubhub: order-service fees only; account adjustments (signed) are not included in this total.
        $totalFeesCard = $statement->platform === 'grubhub' ? $corePlatformFees : $corePlatformFees + $adjSigned;
        $useGrubhubAdjustmentSplit = $statement->platform === 'grubhub';
        $grubhubPositiveAdjustments = $useGrubhubAdjustmentSplit ? max(0, $adjSigned) : 0.0;
        $grubhubNegativeAdjustments = $useGrubhubAdjustmentSplit ? max(0, -$adjSigned) : 0.0;
        $summaryColClass = $useGrubhubAdjustmentSplit ? 'col-sm-6 col-lg-3' : 'col-md-4';
    @endphp
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif
    <div class="mb-4">
        <a href="{{ route('admin.merchant-fees.third-party') }}" class="btn btn-outline-secondary btn-sm mb-2">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="me-1"><path d="M19 12H5M12 19l-7-7 7-7"/></svg>
            Back to Third-Party Platforms
        </a>
        <div class="d-flex justify-content-between align-items-start flex-wrap gap-2">
            <div>
                <h1 class="mb-1" style="font-family: 'Google Sans', sans-serif; font-size: 1.75rem; font-weight: 400; color: var(--on-surface, #202124);">
                    {{ ucfirst($statement->platform) }} Statement
                </h1>
                <p class="text-muted mb-0" style="font-family: 'Google Sans', sans-serif;">
                    {{ $statement->statement_date->format('F j, Y') }}
                    @if($statement->store)
                        · {{ $statement->store->store_info }}
                    @endif
                    @if($statement->file_name)
                        · {{ $statement->file_name }}
                    @endif
                </p>
            </div>
            <div class="d-flex align-items-center gap-2">
                <form action="{{ route('admin.merchant-fees.third-party.destroy', $statement) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete this statement, the stored file, and all related records (expenses, expected deposit)? This cannot be undone.');">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-danger">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="me-1"><path d="M3 6h18M19 6v14a2 2 0 01-2 2H7a2 2 0 01-2-2V6M8 6V4a2 2 0 012-2h4a2 2 0 012 2v2"/><line x1="10" y1="11" x2="10" y2="17"/><line x1="14" y1="11" x2="14" y2="17"/></svg>
                        Delete statement &amp; file
                    </button>
                </form>
                <span class="badge bg-info fs-6 px-3 py-2">{{ ucfirst($statement->platform) }}</span>
            </div>
        </div>
    </div>

    <!-- Summary cards -->
    <div class="row row-cards mb-4">
        <div class="col-sm-6 col-lg-3">
            <div class="card">
                <div class="card-body">
                    <div class="subheader text-muted">Gross Sales</div>
                    <div class="h2 mb-0 text-success">${{ number_format($statement->gross_sales, 2) }}</div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-lg-3">
            <div class="card">
                <div class="card-body">
                    <div class="subheader text-muted">Marketing Fees</div>
                    <div class="h2 mb-0 text-danger">${{ number_format($statement->marketing_fees, 2) }}</div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-lg-3">
            <div class="card">
                <div class="card-body">
                    <div class="subheader text-muted">Delivery Fees</div>
                    <div class="h2 mb-0 text-danger">${{ number_format($statement->delivery_fees, 2) }}</div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-lg-3">
            <div class="card">
                <div class="card-body">
                    <div class="subheader text-muted">Processing Fees</div>
                    <div class="h2 mb-0 text-danger">${{ number_format($statement->processing_fees, 2) }}</div>
                </div>
            </div>
        </div>
    </div>

    <div class="row row-cards mb-4 g-3">
        <div class="{{ $summaryColClass }}">
            <div class="card">
                <div class="card-body">
                    <div class="subheader text-muted">Total Fees</div>
                    <div class="h2 mb-0 text-danger">${{ number_format($totalFeesCard, 2) }}</div>
                    @if($statement->platform === 'grubhub')
                        <div class="text-muted small mt-1">Grubhub order services total (marketing + delivery + processing)</div>
                    @endif
                </div>
            </div>
        </div>
        @if($useGrubhubAdjustmentSplit)
        <div class="{{ $summaryColClass }}">
            <div class="card border-danger border-opacity-25">
                <div class="card-body">
                    <div class="subheader text-muted">Negative adjustments</div>
                    <div class="h2 mb-0 text-danger">${{ number_format($grubhubNegativeAdjustments, 2) }}</div>
                    <div class="text-muted small mt-1">Deducted from your payout (when the PDF shows a debit)</div>
                </div>
            </div>
        </div>
        <div class="{{ $summaryColClass }}">
            <div class="card border-success border-opacity-25">
                <div class="card-body">
                    <div class="subheader text-muted">Positive adjustments</div>
                    <div class="h2 mb-0 text-success">${{ number_format($grubhubPositiveAdjustments, 2) }}</div>
                    <div class="text-muted small mt-1">Added to your payout for this statement</div>
                </div>
            </div>
        </div>
        @else
        <div class="{{ $summaryColClass }}">
            <div class="card">
                <div class="card-body">
                    <div class="subheader text-muted">{{ $adjustmentLabel }}</div>
                    <div class="h2 mb-0 text-danger">${{ number_format($statement->adjustments ?? 0, 2) }}</div>
                </div>
            </div>
        </div>
        @endif
        <div class="{{ $summaryColClass }}">
            <div class="card">
                <div class="card-body">
                    <div class="subheader text-muted">{{ $netLabel }}</div>
                    <div class="h2 mb-0 text-primary">${{ number_format($statement->net_deposit, 2) }}</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Meta -->
    <div class="card mb-4">
        <div class="card-header">
            <h3 class="card-title">Statement details</h3>
        </div>
        <div class="card-body">
            <div class="row g-3">
                @if($statement->store)
                <div class="col-md-4">
                    <label class="form-label text-muted small">Store</label>
                    <div>{{ $statement->store->store_info }}</div>
                </div>
                @endif
                <div class="col-md-4">
                    <label class="form-label text-muted small">Statement date</label>
                    <div>{{ $statement->statement_date->format('M j, Y') }}</div>
                </div>
                @if($statement->importer)
                <div class="col-md-4">
                    <label class="form-label text-muted small">Imported by</label>
                    <div>{{ $statement->importer->name ?? $statement->importer->email }}</div>
                </div>
                @endif
                @if($statement->file_name)
                <div class="col-md-4">
                    <label class="form-label text-muted small">File</label>
                    <div class="text-break">{{ $statement->file_name }}</div>
                </div>
                @endif
                @if($statement->sales_tax_collected != 0)
                <div class="col-md-4">
                    <label class="form-label text-muted small">Sales tax collected</label>
                    <div>${{ number_format($statement->sales_tax_collected, 2) }}</div>
                </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Linked expense transactions -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Expense transactions from this statement</h3>
            <span class="badge bg-secondary">{{ $statement->expenses->count() }} entries</span>
        </div>
        <div class="card-body p-0">
            @if($statement->expenses->isEmpty())
                <div class="text-center text-muted py-5">No expense transactions linked to this statement.</div>
            @else
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead style="background-color: var(--google-grey-50, #f8f9fa);">
                            <tr>
                                <th>Date</th>
                                <th>Description</th>
                                <th>Vendor</th>
                                <th>Category (COA)</th>
                                <th class="text-end">Amount</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($statement->expenses as $exp)
                            <tr>
                                <td>{{ $exp->transaction_date ? \Carbon\Carbon::parse($exp->transaction_date)->format('M j, Y') : '—' }}</td>
                                <td class="text-break">{{ $exp->description ?? '—' }}</td>
                                <td>{{ $exp->vendor->vendor_name ?? '—' }}</td>
                                <td>{{ $exp->coa ? $exp->coa->account_code . ' ' . $exp->coa->account_name : '—' }}</td>
                                <td class="text-end">${{ number_format($exp->amount, 2) }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>

    <!-- Danger zone: delete statement and file -->
    <div class="card border-danger mt-4">
        <div class="card-header bg-danger bg-opacity-10 text-danger">
            <h3 class="card-title mb-0">Delete this import</h3>
        </div>
        <div class="card-body">
            <p class="text-muted mb-3">This will permanently delete the statement record, the stored file, all linked expense transactions, and the expected deposit. This cannot be undone.</p>
            <form action="{{ route('admin.merchant-fees.third-party.destroy', $statement) }}" method="POST" class="d-inline" onsubmit="return confirm('Permanently delete this statement, its file, and all related records? This cannot be undone.');">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn btn-danger">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="me-1"><path d="M3 6h18M19 6v14a2 2 0 01-2 2H7a2 2 0 01-2-2V6M8 6V4a2 2 0 012-2h4a2 2 0 012 2v2"/><line x1="10" y1="11" x2="10" y2="17"/><line x1="14" y1="11" x2="14" y2="17"/></svg>
                    Delete statement and file
                </button>
            </form>
        </div>
    </div>
</div>
@endsection
