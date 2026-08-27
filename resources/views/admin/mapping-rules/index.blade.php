@extends('layouts.tabler')

@section('title', 'Categorization Rules')

@section('content')
<div class="container-xl mt-4">
    <div class="mb-4">
        <h1 class="mb-0">Categorization Rules</h1>
        <p class="text-muted mb-0">Learned rules that auto-suggest a Chart of Accounts code for imported statement lines. Rules are learned per client.</p>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <div class="card mb-4">
        <div class="card-header"><h3 class="card-title mb-0">{{ $rules->total() }} Rule{{ $rules->total() === 1 ? '' : 's' }}</h3></div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Description pattern</th>
                            <th style="width:220px;">Maps to (COA)</th>
                            <th style="width:130px;">Client</th>
                            <th class="text-center" style="width:110px;" title="Confidence / used / correct-incorrect">Conf · Use</th>
                            <th class="text-center" style="width:80px;">Active</th>
                            <th class="text-end" style="width:150px;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($rules as $rule)
                            @php($formId = 'rule-update-'.$rule->id)
                            <tr>
                                <td>
                                    <input type="text" name="description_pattern" value="{{ $rule->description_pattern }}"
                                           form="{{ $formId }}" class="form-control form-control-sm" maxlength="255" required>
                                </td>
                                <td>
                                    <select name="coa_id" form="{{ $formId }}" class="form-select form-select-sm">
                                        @foreach($accounts as $account)
                                            <option value="{{ $account->id }}" @selected($account->id === $rule->coa_id)>
                                                {{ $account->account_code }} — {{ $account->account_name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </td>
                                <td>
                                    @if($rule->owner)
                                        <span class="badge bg-azure-lt">{{ $rule->owner->name }}</span>
                                    @else
                                        <span class="badge bg-secondary-lt" title="Applies to all clients as a fallback">Global</span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    <span class="fw-bold">{{ number_format((float) $rule->confidence_score * 100) }}%</span>
                                    <div class="small text-muted">{{ $rule->times_used }}× · {{ $rule->times_correct }}✓/{{ $rule->times_incorrect }}✗</div>
                                </td>
                                <td class="text-center">
                                    <div class="form-check form-switch d-inline-block mb-0">
                                        <input class="form-check-input" type="checkbox" name="is_active" value="1"
                                               form="{{ $formId }}" @checked($rule->is_active)>
                                    </div>
                                </td>
                                <td class="text-end">
                                    <form id="{{ $formId }}" action="{{ route('admin.mapping-rules.update', $rule) }}" method="POST" class="d-inline">
                                        @csrf @method('PUT')
                                        <button type="submit" class="btn btn-sm btn-outline-primary" title="Save"><i class="bi bi-check-lg"></i></button>
                                    </form>
                                    <form action="{{ route('admin.mapping-rules.destroy', $rule) }}" method="POST" class="d-inline"
                                          onsubmit="return confirm('Delete this rule? Imports will stop auto-suggesting from it.');">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger" title="Delete"><i class="bi bi-trash"></i></button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="text-center text-muted py-4">No rules learned yet. They are created when you categorize imported transactions and choose "remember for future uploads".</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if($rules->hasPages())
            <div class="card-footer">{{ $rules->links() }}</div>
        @endif
    </div>

    {{-- Decision audit feed --}}
    <div class="card">
        <div class="card-header"><h3 class="card-title mb-0">Recent categorization decisions</h3></div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-vcenter mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>When</th>
                            <th>Description</th>
                            <th>Decision</th>
                            <th>Source</th>
                            <th>COA</th>
                            <th class="text-end">Confidence</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($recentDecisions as $d)
                            <tr>
                                <td class="text-muted">{{ $d->created_at?->diffForHumans() }}</td>
                                <td>{{ \Illuminate\Support\Str::limit($d->description, 40) }}</td>
                                <td><span class="badge bg-blue-lt">{{ $d->decision }}</span></td>
                                <td class="text-muted">{{ $d->source }}</td>
                                <td>{{ optional($d->chosenCoa ?? $d->suggestedCoa)->account_code ?? '—' }}</td>
                                <td class="text-end">{{ $d->confidence !== null ? number_format((float) $d->confidence).'%' : '—' }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="text-center text-muted py-4">No categorization decisions logged yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
