{{-- Recursive Chart-of-Accounts rows. The full hierarchy is shown, indented by
     depth (categories flush, sub-accounts nested underneath). --}}
@foreach($nodes as $node)
    @php $hasKids = $node->childNodes->isNotEmpty(); @endphp
    <tr>
        <td>
            <span style="display:inline-block; width: {{ $node->depth * 1.5 }}rem;"></span>
            <strong>{{ $node->account_code }}</strong>
        </td>
        <td>
            {{ $node->account_name }}
            @if($hasKids)
                <span class="badge bg-blue-lt ms-1">{{ $node->childNodes->count() }} sub-account{{ $node->childNodes->count() === 1 ? '' : 's' }}</span>
            @endif
        </td>
        <td><span class="badge bg-secondary">{{ $node->account_type }}</span></td>
        <td>
            @if($node->stores->isEmpty())
                <span class="text-muted">All Stores</span>
            @else
                <span class="text-muted">{{ $node->stores->pluck('store_info')->implode(', ') }}</span>
            @endif
        </td>
        <td>
            @if($node->is_active)
                <span class="badge bg-success">Active</span>
            @else
                <span class="badge bg-danger">Inactive</span>
            @endif
        </td>
        <td class="text-end">
            <div class="d-flex gap-1 justify-content-end">
                @if(\App\Models\ChartOfAccount::childCodeRangeForParent((string) $node->account_code) !== null)
                    <a href="{{ route('coa.create', ['parent' => $node->id]) }}" class="btn btn-sm btn-outline-primary" title="Add a sub-account under {{ $node->account_name }}">
                        <i class="bi bi-plus"></i> Sub
                    </a>
                @endif
                <x-button-view href="{{ route('coa.show', $node) }}" iconOnly="true" />
                @if($node->canBeManagedBy(auth()->user()))
                    <x-button-edit href="{{ route('coa.edit', $node) }}" iconOnly="true" />
                    <x-button-delete
                        action="{{ route('coa.destroy', $node) }}"
                        iconOnly="true"
                        confirmMessage="Are you sure you want to delete this account? This action cannot be undone." />
                @endif
            </div>
        </td>
    </tr>
    @if($hasKids)
        @include('admin.coa._tree-rows', ['nodes' => $node->childNodes])
    @endif
@endforeach
