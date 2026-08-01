{{-- Recursive Chart-of-Accounts rows. The hierarchy is shown by indenting the
     ACCOUNT NAME by depth, bolding categories, and shading the top levels so
     Type → Category → Sub-account reads clearly. --}}
@foreach($nodes as $node)
    @php
        $hasKids = $node->childNodes->isNotEmpty();
        $rowBg = $node->depth === 0 ? '#eef2f7' : ($node->depth === 1 ? '#f7f9fc' : '');
    @endphp
    <tr @if($rowBg) style="background: {{ $rowBg }};" @endif>
        <td style="white-space:nowrap;"><strong>{{ $node->account_code }}</strong></td>
        <td>
            <span style="display:inline-block; width: {{ $node->depth * 1.75 }}rem;"></span>
            @if($node->depth >= 2)
                <span class="text-muted me-1" style="opacity:.6;">↳</span>
            @endif
            <span @if($node->depth <= 1 || $hasKids) style="font-weight:600;" @endif>{{ $node->account_name }}</span>
            @if($hasKids)
                <span class="badge bg-blue-lt ms-2">{{ $node->childNodes->count() }} sub-account{{ $node->childNodes->count() === 1 ? '' : 's' }}</span>
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
                <a href="{{ route('coa.create', ['parent' => $node->id]) }}" class="btn btn-sm btn-outline-primary" title="Add a sub-account under {{ $node->account_name }}">
                    <i class="bi bi-plus"></i> Sub
                </a>
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
