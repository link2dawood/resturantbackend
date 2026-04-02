@extends('layouts.tabler')

@section('title', 'Download/Upload Log')

@section('content')
<div class="container-xl mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <a href="{{ route('admin.merchant-fees.index') }}" class="btn btn-outline-secondary btn-sm mb-2">← Back to Merchant Fees</a>
            <h1 class="mb-0" style="font-family: 'Google Sans', sans-serif; font-size: 1.75rem; font-weight: 400; color: var(--on-surface, #202124);">Download/Upload Log</h1>
            <p class="text-muted mb-0" style="font-family: 'Google Sans', sans-serif; margin-top: 0.25rem;">History of credit card, bank, and online platform imports</p>
        </div>
    </div>

    <div class="card">
        <div class="table-responsive">
            <table class="table table-vcenter card-table table-striped">
                <thead>
                    <tr>
                        <th>Type</th>
                        <th>Date</th>
                        <th>File / Description</th>
                        <th>Store</th>
                        <th>Imported by</th>
                        <th>Rows</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($imports as $imp)
                        <tr>
                            <td>
                                @if($imp->type === 'Credit Card')
                                    <span class="badge bg-primary">Credit Card</span>
                                @elseif($imp->type === 'Bank')
                                    <span class="badge bg-info">Bank</span>
                                @else
                                    <span class="badge bg-success">Online Platform</span>
                                @endif
                            </td>
                            <td>{{ \Carbon\Carbon::parse($imp->date)->format(config('dates.display_datetime')) }}</td>
                            <td>
                                {{ $imp->file_name }}
                                @if(!empty($imp->detail))
                                    <span class="text-muted small">· {{ $imp->detail }}</span>
                                @endif
                            </td>
                            <td>{{ $imp->store ?? '—' }}</td>
                            <td>{{ $imp->user ?? '—' }}</td>
                            <td>{{ $imp->rows !== null ? number_format($imp->rows) : '—' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center text-muted py-4">No import history yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($imports->hasPages())
            <div class="card-footer d-flex align-items-center">
                {{ $imports->withQueryString()->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
