@extends('layouts.tabler')

@section('title', 'P&L Drill-Down')

@section('content')
<div class="container-xl mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="mb-0">Transaction Details</h1>
            <p class="text-muted mb-0">{{ $data['coa']['account_name'] ?? 'N/A' }}</p>
        </div>
        <a href="{{ route('admin.reports.profit-loss.index') }}" class="btn btn-outline-secondary">Back to P&L</a>
    </div>

    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Transactions</h3>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead style="background-color: var(--google-grey-50, #f8f9fa);">
                        <tr>
                            <th>Date</th>
                            <th>Store</th>
                            <th>Vendor</th>
                            <th>Description</th>
                            <th class="text-end">Amount</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($data['transactions']['data'] as $transaction)
                        <tr>
                            <td>{{ $transaction['transaction_date'] }}</td>
                            <td>{{ $transaction['store']['store_info'] ?? 'N/A' }}</td>
                            <td>{{ $transaction['vendor']['vendor_name'] ?? 'N/A' }}</td>
                            <td>{{ $transaction['description'] ?? '-' }}</td>
                            <td class="text-end">${{ number_format($transaction['amount'], 2) }}</td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="5" class="text-center text-muted py-4">No transactions found</td>
                        </tr>
                        @endforelse
                    </tbody>
                    <tfoot>
                        <tr style="background-color: #f8f9fa; font-weight: 600;">
                            <td colspan="4" class="text-end">Total:</td>
                            <td class="text-end">${{ number_format($data['total'] ?? 0, 2) }}</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
        @if(isset($data['transactions']['data']) && count($data['transactions']['data']) > 0)
        <div class="card-footer">
            <small class="text-muted">Showing {{ count($data['transactions']['data']) }} transactions</small>
        </div>
        @endif
    </div>
</div>
@endsection

