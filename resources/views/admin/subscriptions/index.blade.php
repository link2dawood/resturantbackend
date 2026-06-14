@extends('layouts.tabler')

@section('title', 'Subscriptions')

@section('content')
<div class="container-xl py-4">
    <h2 class="mb-1">Subscriptions</h2>
    <p class="text-muted">Active subscriptions, recurring revenue and churn for the {{ $planName }} plan (${{ number_format($monthlyAmount, 2) }}/mo).</p>

    {{-- KPI cards --}}
    <div class="row row-cards mb-4">
        <div class="col-sm-6 col-lg-3">
            <div class="card"><div class="card-body">
                <div class="text-muted">Active subscriptions</div>
                <div class="h1 mb-0">{{ number_format($activeCount) }}</div>
            </div></div>
        </div>
        <div class="col-sm-6 col-lg-3">
            <div class="card"><div class="card-body">
                <div class="text-muted">MRR</div>
                <div class="h1 mb-0">${{ number_format($mrr, 2) }}</div>
            </div></div>
        </div>
        <div class="col-sm-6 col-lg-3">
            <div class="card"><div class="card-body">
                <div class="text-muted">ARR (run-rate)</div>
                <div class="h1 mb-0">${{ number_format($arr, 2) }}</div>
            </div></div>
        </div>
        <div class="col-sm-6 col-lg-3">
            <div class="card"><div class="card-body">
                <div class="text-muted">Churn (30d)</div>
                <div class="h1 mb-0">{{ $churnRate }}%</div>
                <div class="text-muted small">{{ $cancelledLast30 }} cancelled</div>
            </div></div>
        </div>
    </div>

    {{-- Active subscriptions table --}}
    <div class="card">
        <div class="card-header"><h3 class="card-title">Active subscriptions</h3></div>
        <div class="table-responsive">
            <table class="table table-vcenter card-table">
                <thead>
                    <tr>
                        <th>Customer</th>
                        <th>Email</th>
                        <th>Status</th>
                        <th>Started</th>
                        <th>Ends / renews</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($subscriptions as $sub)
                        <tr>
                            <td>{{ $sub->user->name ?? '—' }}</td>
                            <td class="text-muted">{{ $sub->user->email ?? '—' }}</td>
                            <td>
                                <span class="badge bg-{{ $sub->onGracePeriod() ? 'warning' : 'success' }}-lt">
                                    {{ $sub->onGracePeriod() ? 'Cancelling' : ucfirst($sub->stripe_status) }}
                                </span>
                            </td>
                            <td>{{ $sub->created_at?->format('M j, Y') }}</td>
                            <td>
                                {{ $sub->ends_at ? 'Ends '.$sub->ends_at->format('M j, Y') : 'Renews 1st of month' }}
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-center text-muted py-4">No active subscriptions yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($subscriptions->hasPages())
            <div class="card-footer">{{ $subscriptions->links() }}</div>
        @endif
    </div>
</div>
@endsection
